<?php

namespace EvanPiAlert\Util;

use EvanPiAlert\Util\essence\PiAlert;
use EvanPiAlert\Util\essence\PiAlertGroup;
use InvalidArgumentException;
use LogicException;
use Throwable;

/** Read-only MCP protocol adapter for PiAlert domain objects. */
class McpServer {

    protected const string PROTOCOL_VERSION = '2025-06-18';
    protected const int DASHBOARD_MENU_ID = 1;
    protected const int MAX_LIST_LIMIT = 100;
    protected const int MAX_ALERTS_LIMIT = 300;

    public function handle(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            $this->httpError(405, 'Only POST is supported by this stateless MCP endpoint.');
        }
        $identity = $this->authenticate();
        $request = json_decode(file_get_contents('php://input'), true);
        if (!is_array($request) || ($request['jsonrpc'] ?? null) !== '2.0' || !isset($request['method'])) {
            $this->error(null, -32600, 'Invalid JSON-RPC request.');
            return;
        }
        $id = $request['id'] ?? null;
        if ( !array_key_exists('id', $request) ) {
            // No responses for notifications in RPC/MCP, so early answer for readonly API
            http_response_code(202);
            return;
        }
        $params = is_array($request['params'] ?? null) ? $request['params'] : [];
        try {
            $result = $this->dispatch($request['method'], $params, $identity['systems']);
            $this->response($id, $result);
        } catch (InvalidArgumentException $exception) {
            $this->error($id, -32602, $exception->getMessage());
        } catch (LogicException $exception) {
            $this->error($id, -32601, $exception->getMessage());
        } catch (Throwable) {
            $this->error($id, -32603, 'Internal server error.');
        }
    }

    /** @return array{user_id: int, systems: array<int, string>} */
    protected function authenticate(): array {
        $this->readBasicAuthorizationHeader();
        $email = $_SERVER['PHP_AUTH_USER'] ?? '';
        $password = $_SERVER['PHP_AUTH_PW'] ?? '';
        if ($email === '' || $password === '') {
            $this->unauthorized();
        }
        $authorization = new AuthorizationAdmin();
        if (!$authorization->loginByEmailAndPassword($email, $password)) {
            $this->unauthorized();
        }
        if (!$authorization->checkAccessToMenu(self::DASHBOARD_MENU_ID)) {
            $this->httpError(403, 'The PiAlert user has no access to the dashboard.');
        }
        return ['user_id' => $authorization->getUserId(), 'systems' => $authorization->getAccessedSystemNames()];
    }

    protected function readBasicAuthorizationHeader(): void {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ((empty($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) && str_starts_with($header, 'Basic ')) {
            $credentials = base64_decode(substr($header, 6), true);
            if ($credentials !== false && str_contains($credentials, ':')) {
                [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']] = explode(':', $credentials, 2);
            }
        }
    }

    protected function dispatch(string $method, array $params, array $systems): array|null {
        return match ($method) {
            'initialize' => ['protocolVersion' => self::PROTOCOL_VERSION, 'capabilities' => ['tools' => ['listChanged' => false]], 'serverInfo' => ['name' => 'pialert', 'version' => SystemVersion::getCodeVersion()], 'instructions' => 'Read-only access to PiAlert AlertGroups and source Alerts.'],
            'notifications/initialized' => null,
            'ping' => [],
            'tools/list' => ['tools' => $this->tools()],
            'tools/call' => $this->callTool($params, $systems),
            default => throw new LogicException('Method not found: ' . $method),
        };
    }

    protected function callTool(array $params, array $systems): array {
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
        $data = match ($params['name'] ?? '') {
            'list_alert_groups' => $this->listGroups($arguments, $systems),
            'get_alert_group' => $this->accessibleGroup($this->requiredId($arguments), $systems)->toArray(),
            'get_alerts_by_group' => $this->alertsByGroup($arguments, $systems),
            'get_alert_group_statistics' => $this->groupStatistics($arguments, $systems),
            'find_similar_alert_groups' => $this->similarGroups($arguments, $systems),
            default => throw new InvalidArgumentException('Unknown tool: ' . ($params['name'] ?? '')),
        };
        return ['content' => [['type' => 'text', 'text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]]];
    }

    protected function listGroups(array $args, array $systems): array {
        [$filter, $params] = $this->systemFilter($systems);
        $conditions = [$filter];
        if (($args['pi_system_name'] ?? '') !== '') {
            $conditions[] = 'piSystemName = ?';
            $params[] = $args['pi_system_name'];
        }
        if (isset($args['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $this->status($args['status']);
        }
        if (!empty($args['search'])) {
            $conditions[] = '(errText LIKE ? OR errTextMask LIKE ? OR interface LIKE ? OR channel LIKE ?)';
            $search = '%' . $args['search'] . '%';
            array_push($params, $search, $search, $search, $search);
        }
        if (!empty($args['active_only'])) {
            $conditions[] = 'status NOT IN (?, ?)';
            array_push($params, PiAlertGroup::IGNORE, PiAlertGroup::CLOSE);
        }
        $query = DB::prepare('SELECT * FROM alert_group WHERE ' . implode(' AND ', $conditions) . ' ORDER BY last_alert DESC LIMIT ' . $this->limit($args['limit'] ?? null, 25, self::MAX_LIST_LIMIT) . ' OFFSET ' . $this->offset($args['offset'] ?? null));
        $query->execute($params);
        return array_map(static fn(array $row): array => new PiAlertGroup($row)->toArray(), $query->fetchAll());
    }

    protected function alertsByGroup(array $args, array $systems): array {
        $groupId = $this->requiredId($args);
        $this->accessibleGroup($groupId, $systems);
        $query = DB::prepare('SELECT * FROM alerts WHERE group_id = ? ORDER BY timestamp DESC LIMIT ' . $this->limit($args['limit'] ?? null, 50, self::MAX_ALERTS_LIMIT));
        $query->execute([$groupId]);
        return array_map(static fn(array $row): array => new PiAlert($row)->toArray(), $query->fetchAll());
    }

    protected function groupStatistics(array $args, array $systems): array {
        $group = $this->accessibleGroup($this->requiredId($args), $systems);
        return [
            'group_id' => $group->group_id,
            'total' => $group->getAlertCount(),
            'last_24_hours' => $group->getAlertCount(ONE_DAY),
            'last_7_days' => $group->getAlertCount(ONE_WEEK),
            'last_month' => $group->getAlertCount(ONE_MONTH),
            'daily_last_31_days' => $group->getAlertCountForDiagram(31 * ONE_DAY)->fetchAll(),
        ];
    }

    protected function similarGroups(array $args, array $systems): array {
        $group = $this->accessibleGroup($this->requiredId($args), $systems);
        [$filter, $params] = $this->systemFilter($systems);
        // This is intentionally the same grouping criterion as dashboard.php?showSameErrors.
        $query = DB::prepare("SELECT * FROM alert_group WHERE $filter AND errTextMainPart = (SELECT errTextMainPart FROM alert_group WHERE group_id = ?)");
        $query->execute(array_merge($params, [$group->group_id]));
        return array_map(static fn(array $row): array => new PiAlertGroup($row)->toArray(), $query->fetchAll());
    }

    protected function accessibleGroup(int $groupId, array $systems): PiAlertGroup {
        [$filter, $params] = $this->systemFilter($systems);
        $query = DB::prepare("SELECT * FROM alert_group WHERE group_id = ? AND $filter");
        $query->execute(array_merge([$groupId], $params));
        $row = $query->fetch();
        if (!$row) { throw new InvalidArgumentException('Alert group not found or not accessible.'); }
        return new PiAlertGroup($row);
    }

    /** @return array{0: string, 1: array<int, string>} */
    protected function systemFilter(array $systems): array {
        return [PiAlertGroup::getSqlSystemFilter($systems), array_values($systems)];
    }

    protected function requiredId(array $args): int {
        $value = filter_var($args['group_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value === false) { throw new InvalidArgumentException('group_id must be a positive integer.'); }
        return $value;
    }

    protected function status(mixed $value): int {
        $status = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => PiAlertGroup::NEW, 'max_range' => PiAlertGroup::REOPEN]]);
        if ($status === false) { throw new InvalidArgumentException('status must be an integer from 0 to 5.'); }
        return $status;
    }

    protected function limit(mixed $value, int $default, int $max): int {
        $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $max]]);
        return $value === false ? $default : $value;
    }

    protected function offset(mixed $value): int {
        $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        return $value === false ? 0 : $value;
    }

    protected function tools(): array {
        return [
            ['name' => 'list_alert_groups', 'description' => 'Lists visible AlertGroups, newest first.', 'inputSchema' => ['type' => 'object', 'properties' => ['pi_system_name' => ['type' => 'string'], 'status' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 5], 'search' => ['type' => 'string'], 'active_only' => ['type' => 'boolean'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIST_LIMIT], 'offset' => ['type' => 'integer', 'minimum' => 0]]]],
            ['name' => 'get_alert_group', 'description' => 'Returns one AlertGroup.', 'inputSchema' => ['type' => 'object', 'required' => ['group_id'], 'properties' => ['group_id' => ['type' => 'integer', 'minimum' => 1]]]],
            ['name' => 'get_alerts_by_group', 'description' => 'Returns recent source Alerts in an AlertGroup.', 'inputSchema' => ['type' => 'object', 'required' => ['group_id'], 'properties' => ['group_id' => ['type' => 'integer', 'minimum' => 1], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_ALERTS_LIMIT]]]],
            ['name' => 'get_alert_group_statistics', 'description' => 'Returns aggregate and daily counts for an AlertGroup.', 'inputSchema' => ['type' => 'object', 'required' => ['group_id'], 'properties' => ['group_id' => ['type' => 'integer', 'minimum' => 1]]]],
            ['name' => 'find_similar_alert_groups', 'description' => 'Returns groups with the same main error part, as Dashboard showSameErrors.', 'inputSchema' => ['type' => 'object', 'required' => ['group_id'], 'properties' => ['group_id' => ['type' => 'integer', 'minimum' => 1]]]],
        ];
    }

    protected function response(mixed $id, mixed $result): void {
        header('Content-Type: application/json; charset=utf-8'); header('MCP-Protocol-Version: ' . self::PROTOCOL_VERSION);
        echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function error(mixed $id, int $code, string $message): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function unauthorized(): never { header('WWW-Authenticate: Basic realm="PiAlert MCP", charset="UTF-8"'); $this->httpError(401, 'Authentication is required.'); }
    protected function httpError(int $status, string $message): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['error' => $message]); exit; }
}
