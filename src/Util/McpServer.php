<?php

namespace EvanPiAlert\Util;

use EvanPiAlert\Util\essence\PiAlert;
use EvanPiAlert\Util\essence\PiAlertGroup;
use InvalidArgumentException;
use LogicException;
use Throwable;

/** Read-only MCP protocol adapter for PiAlert domain objects. */
class McpServer {

    protected const string PROTOCOL_VERSION = '2026-07-28';
    protected const int DASHBOARD_MENU_ID = 1;
    protected const int MAX_LIST_LIMIT = 100;
    protected const int MAX_ALERTS_LIMIT = 300;

    public function handle(): void {
        $this->validateOrigin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            $this->httpError(405, 'Only POST is supported by this stateless MCP endpoint.');
        }
        $request = json_decode(file_get_contents('php://input'), true);
        if (!is_array($request) || ($request['jsonrpc'] ?? null) !== '2.0' || !isset($request['method'])) {
            $this->error(null, -32600, 'Invalid JSON-RPC request.', 400);
            return;
        }
        $id = $request['id'] ?? null;
        if (!$this->validateRequestMetadata($request, $id)) {
            return;
        }
        $identity = $this->authenticate();
        if ( !array_key_exists('id', $request) ) {
            // No responses for notifications in RPC/MCP.
            http_response_code(202);
            return;
        }
        $params = is_array($request['params'] ?? null) ? $request['params'] : [];
        try {
            $result = $this->dispatch($request['method'], $params, $identity['systems']);
            $this->response($id, $result);
        } catch (InvalidArgumentException $exception) {
            $this->error($id, -32602, $exception->getMessage(), 400);
        } catch (LogicException $exception) {
            $this->error($id, -32601, $exception->getMessage(), 404);
        } catch (Throwable) {
            $this->error($id, -32603, 'Internal server error.');
        }
    }

    /**
     * The Origin header is optional for non-browser clients. When it is sent,
     * Streamable HTTP requires it to identify this endpoint's own origin.
     */
    protected function validateOrigin(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin === '') {
            return;
        }
        $originParts = parse_url($origin);
        // Do not use HTTP_HOST here: an attacker can control it during a DNS
        // rebinding attempt. SERVER_NAME is the endpoint's configured name.
        $host = $_SERVER['SERVER_NAME'] ?? '';
        $requestParts = $host === '' ? false : parse_url('//' . $host);
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
            ? 'https'
            : 'http';
        if (is_array($requestParts) && !isset($requestParts['port']) && isset($_SERVER['SERVER_PORT'])) {
            $requestParts['port'] = (int) $_SERVER['SERVER_PORT'];
        }

        if (!is_array($originParts) || !is_array($requestParts)
            || !isset($originParts['scheme'], $originParts['host'], $requestParts['host'])
            || isset($originParts['user'], $originParts['pass'], $originParts['query'], $originParts['fragment'])
            || (($originParts['path'] ?? '') !== '')
            || !in_array(strtolower($originParts['scheme']), ['http', 'https'], true)
            || strtolower($originParts['scheme']) !== $scheme
            || strtolower($originParts['host']) !== strtolower($requestParts['host'])
            || $this->normalizedPort($originParts, strtolower($originParts['scheme'])) !== $this->normalizedPort($requestParts, $scheme)) {
            $this->httpError(403, 'The Origin header is not allowed for this MCP endpoint.');
        }
    }

    protected function normalizedPort(array $parts, string $scheme): int {
        return isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
    }

    /** @param array<string, mixed> $request */
    protected function validateRequestMetadata(array $request, mixed $id): bool {
        $params = $request['params'] ?? null;
        $meta = is_array($params) ? ($params['_meta'] ?? null) : null;
        $headerVersion = $_SERVER['HTTP_MCP_PROTOCOL_VERSION'] ?? '';
        if ($headerVersion === '' || ($_SERVER['HTTP_MCP_METHOD'] ?? '') !== $request['method']) {
            $this->headerMismatch($id, 'MCP-Protocol-Version or Mcp-Method header is missing or does not match the request body.');
            return false;
        }
        if ($headerVersion !== self::PROTOCOL_VERSION) {
            $this->error($id, -32022, 'Unsupported protocol version', 400, [
                'supported' => [self::PROTOCOL_VERSION],
                'requested' => $headerVersion,
            ]);
            return false;
        }
        if (is_array($meta) && ($meta['io.modelcontextprotocol/protocolVersion'] ?? null) !== $headerVersion) {
            $this->headerMismatch($id, 'MCP-Protocol-Version header does not match the request metadata.');
            return false;
        }
        if (!is_array($meta)
            || !array_key_exists('io.modelcontextprotocol/protocolVersion', $meta)
            || !array_key_exists('io.modelcontextprotocol/clientCapabilities', $meta)
            || !is_array($meta['io.modelcontextprotocol/clientCapabilities'])) {
            $this->error($id, -32602, 'Missing or invalid required MCP request metadata.', 400);
            return false;
        }
        if (in_array($request['method'], ['tools/call', 'resources/read', 'prompts/get'], true)
            && ($_SERVER['HTTP_MCP_NAME'] ?? '') !== ($params['name'] ?? $params['uri'] ?? null)) {
            $this->headerMismatch($id, 'Mcp-Name header is missing or does not match the request body.');
            return false;
        }
        return true;
    }

    protected function headerMismatch(mixed $id, string $message): void {
        $this->error($id, -32020, 'Header mismatch: ' . $message, 400);
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
            'server/discover' => $this->discover(),
            'ping' => [],
            'tools/list' => ['tools' => $this->tools()],
            'tools/call' => $this->callTool($params, $systems),
            default => throw new LogicException('Method not found: ' . $method),
        };
    }

    protected function discover(): array {
        return [
            'resultType' => 'complete',
            'supportedVersions' => [self::PROTOCOL_VERSION],
            'capabilities' => ['tools' => ['listChanged' => false]],
            '_meta' => ['io.modelcontextprotocol/serverInfo' => ['name' => 'pialert', 'version' => SystemVersion::getCodeVersion()]],
            'instructions' => 'Read-only access to PiAlert AlertGroups and source Alerts.',
        ];
    }

    protected function callTool(array $params, array $systems): array {
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
        $name = $params['name'] ?? '';
        $data = match ($name) {
            'list_alert_groups' => $this->listGroups($arguments, $systems),
            'get_alert_group' => $this->accessibleGroup($this->requiredId($arguments), $systems)->toArray(),
            'get_alerts_by_group' => $this->alertsByGroup($arguments, $systems),
            'get_alert_group_statistics' => $this->groupStatistics($arguments, $systems),
            'find_similar_alert_groups' => $this->similarGroups($arguments, $systems),
            default => throw new InvalidArgumentException('Unknown tool: ' . $name),
        };
        return [
            'content' => [['type' => 'text', 'text' => $this->toolSummary($name, $data)]],
            'structuredContent' => $data,
        ];
    }

    /** @param array<string, mixed>|array<int, array<string, mixed>> $data */
    protected function toolSummary(string $name, array $data): string {
        return match ($name) {
            'list_alert_groups' => sprintf('Found %d alert group(s).', count($data)),
            'get_alert_group' => sprintf('Retrieved alert group %s.', $data['group_id'] ?? 'unknown'),
            'get_alerts_by_group' => sprintf('Found %d alert(s).', count($data)),
            'get_alert_group_statistics' => sprintf('Retrieved statistics for alert group %s.', $data['group_id'] ?? 'unknown'),
            'find_similar_alert_groups' => sprintf('Found %d similar alert group(s).', count($data)),
            default => 'Tool call completed.',
        };
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
            'daily_last_31_days' => array_map(static fn(array $row): array => [
                'date' => $row['date'],
                'count' => (int) $row['count'],
            ], $group->getAlertCountForDiagram(31 * ONE_DAY)->fetchAll()),
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
            ['name' => 'list_alert_groups', 'description' => 'Lists visible AlertGroups, newest first.', 'inputSchema' => ['type' => 'object', 'properties' => ['pi_system_name' => ['type' => 'string'], 'status' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 5], 'search' => ['type' => 'string'], 'active_only' => ['type' => 'boolean'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIST_LIMIT], 'offset' => ['type' => 'integer', 'minimum' => 0]]], 'outputSchema' => ['type' => 'array', 'description' => 'Alert groups matching the supplied filters.', 'items' => $this->alertGroupOutputSchema()]],
            ['name' => 'get_alert_group', 'description' => 'Returns one AlertGroup.', 'inputSchema' => ['type' => 'object', 'required' => ['group_id'], 'properties' => ['group_id' => ['type' => 'integer', 'minimum' => 1]]], 'outputSchema' => $this->alertGroupOutputSchema()],
            ['name' => 'get_alerts_by_group', 'description' => 'Returns recent source Alerts in an AlertGroup.', 'inputSchema' => ['type' => 'object', 'required' => ['group_id'], 'properties' => ['group_id' => ['type' => 'integer', 'minimum' => 1], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_ALERTS_LIMIT]]], 'outputSchema' => ['type' => 'array', 'description' => 'Source alerts in descending timestamp order.', 'items' => $this->alertOutputSchema()]],
            ['name' => 'get_alert_group_statistics', 'description' => 'Returns aggregate and daily counts for an AlertGroup.', 'inputSchema' => ['type' => 'object', 'required' => ['group_id'], 'properties' => ['group_id' => ['type' => 'integer', 'minimum' => 1]]], 'outputSchema' => $this->statisticsOutputSchema()],
            ['name' => 'find_similar_alert_groups', 'description' => 'Returns groups with the same main error part, as Dashboard showSameErrors.', 'inputSchema' => ['type' => 'object', 'required' => ['group_id'], 'properties' => ['group_id' => ['type' => 'integer', 'minimum' => 1]]], 'outputSchema' => ['type' => 'array', 'description' => 'Accessible groups with the same normalized main error text.', 'items' => $this->alertGroupOutputSchema()]],
        ];
    }

    /** JSON Schema for a PiAlert AlertGroup returned by this MCP server. */
    protected function alertGroupOutputSchema(): array {
        $groupStatusesText = '';
        foreach (PiAlertGroup::getStatusName() as $key => $value) {
            $groupStatusesText .= $key.' '.$value.'. ';
        }
        return [
            'type' => 'object',
            'description' => 'A PiAlert AlertGroup: related alerts that share the same error.',
            'required' => ['group_id', 'status', 'comment', 'comment_datetime', 'assigned_user_id', 'last_user_id', 'pi_system_name', 'from_system', 'to_system', 'channel', 'interface', 'multi_interface', 'error_text', 'error_mask', 'first_alert', 'last_alert', 'last_user_action', 'maybe_needs_union', 'alert_link'],
            'properties' => [
                'group_id' => ['type' => 'integer', 'description' => 'Unique AlertGroup identifier.'],
                'status' => ['type' => 'object', 'description' => 'Current group status.', 'required' => ['code', 'name'], 'properties' => ['code' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 5, 'description' => 'PiAlert status code: '.$groupStatusesText], 'name' => ['type' => 'string', 'description' => 'Localized name of the status.']]],
                'comment' => $this->nullableStringSchema('User comment on the group.'),
                'comment_datetime' => $this->nullableStringSchema('MySQL DATETIME when the comment was last changed.'),
                'assigned_user_id' => $this->nullableIntegerSchema('ID of the user currently assigned to the group.'),
                'last_user_id' => $this->nullableIntegerSchema('ID of the user who last changed the group.'),
                'pi_system_name' => ['type' => 'string', 'description' => 'System that produced the alert.'],
                'from_system' => ['type' => 'string', 'description' => 'Source system.'],
                'to_system' => ['type' => 'string', 'description' => 'Target system.'],
                'channel' => ['type' => 'string', 'description' => 'Integration channel name.'],
                'interface' => ['type' => 'string', 'description' => 'Integration interface.'],
                'multi_interface' => ['type' => 'boolean', 'description' => 'Whether alerts in the group have different interfaces.'],
                'error_text' => ['type' => 'string', 'description' => 'Original error text.'],
                'error_mask' => ['type' => 'string', 'description' => 'Error text after masking variable parts.'],
                'first_alert' => ['type' => 'string', 'description' => 'MySQL DATETIME of the first alert in the group.'],
                'last_alert' => ['type' => 'string', 'description' => 'MySQL DATETIME of the most recent alert in the group.'],
                'last_user_action' => $this->nullableStringSchema('MySQL DATETIME of the last user action.'),
                'maybe_needs_union' => ['type' => 'boolean', 'description' => 'Whether the group may need to be merged with another group.'],
                'alert_link' => $this->nullableStringSchema('Optional links to the alert in an external systems like Jira SM or Jira Software.'),
            ],
        ];
    }

    /** JSON Schema for a source Alert returned by this MCP server. */
    protected function alertOutputSchema(): array {
        return [
            'type' => 'object',
            'description' => 'One source alert belonging to an AlertGroup.',
            'required' => ['id', 'group_id', 'alert_rule_id', 'pi_system_name', 'priority', 'timestamp', 'message_id', 'from_system', 'to_system', 'adapter_type', 'channel', 'interface', 'namespace', 'monitoring_url', 'error_category', 'error_code', 'error_text', 'uds_attributes'],
            'properties' => [
                'id' => $this->nullableIntegerSchema('Unique source Alert identifier.'),
                'group_id' => ['type' => 'integer', 'description' => 'Identifier of the parent AlertGroup.'],
                'alert_rule_id' => $this->nullableStringSchema('Rule identifier reported by the source system.'),
                'pi_system_name' => ['type' => 'string', 'description' => 'System that produced the alert.'],
                'priority' => $this->nullableStringSchema('Severity or priority reported by the source system.'),
                'timestamp' => ['type' => 'string', 'description' => 'MySQL DATETIME when the alert was produced.'],
                'message_id' => $this->nullableStringSchema('Source system message identifier.'),
                'from_system' => $this->nullableStringSchema('Source system.'),
                'to_system' => $this->nullableStringSchema('Target system.'),
                'adapter_type' => $this->nullableStringSchema('Source adapter type.'),
                'channel' => $this->nullableStringSchema('Integration channel name.'),
                'interface' => $this->nullableStringSchema('Integration interface.'),
                'namespace' => $this->nullableStringSchema('Integration namespace.'),
                'monitoring_url' => $this->nullableStringSchema('Optional URL in the source monitoring system.'),
                'error_category' => $this->nullableStringSchema('Source error category.'),
                'error_code' => $this->nullableStringSchema('Source error code.'),
                'error_text' => ['type' => 'string', 'description' => 'Original error text.'],
                'uds_attributes' => $this->nullableStringSchema('UDS attributes, serialized as a string when present.'),
            ],
        ];
    }

    /** JSON Schema for statistics returned for an AlertGroup. */
    protected function statisticsOutputSchema(): array {
        return [
            'type' => 'object',
            'description' => 'Alert counts for one AlertGroup.',
            'required' => ['group_id', 'total', 'last_24_hours', 'last_7_days', 'last_month', 'daily_last_31_days'],
            'properties' => [
                'group_id' => ['type' => 'integer', 'description' => 'Identifier of the AlertGroup these statistics describe.'],
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Total number of source alerts in the group.'],
                'last_24_hours' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Number of source alerts from last 24 hours.'],
                'last_7_days' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Number of source alerts from last 7 days.'],
                'last_month' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Number of source alerts from last month.'],
                'daily_last_31_days' => ['type' => 'array', 'description' => 'One entry for each day with at least one alert during the previous 31 days.', 'items' => ['type' => 'object', 'required' => ['date', 'count'], 'properties' => ['date' => ['type' => 'string', 'description' => 'Calendar date in YYYY-MM-DD format.'], 'count' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Number of source alerts on this date.']]]],
            ],
        ];
    }

    protected function nullableStringSchema(string $description): array {
        return ['type' => ['string', 'null'], 'description' => $description];
    }

    protected function nullableIntegerSchema(string $description): array {
        return ['type' => ['integer', 'null'], 'description' => $description];
    }

    protected function response(mixed $id, mixed $result): void {
        if (is_array($result)) {
            $result['resultType'] ??= 'complete';
            $result['_meta']['io.modelcontextprotocol/serverInfo'] ??= ['name' => 'pialert', 'version' => SystemVersion::getCodeVersion()];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function error(mixed $id, int $code, string $message, int $status = 200, ?array $data = null): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $error = ['code' => $code, 'message' => $message];
        if ($data !== null) {
            $error['data'] = $data;
        }
        echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'error' => $error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function unauthorized(): never {
        header('WWW-Authenticate: Basic realm="PiAlert MCP", charset="UTF-8"');
        $this->httpError(401, 'Authentication is required.');
    }

    protected function httpError(int $status, string $message): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }
}
