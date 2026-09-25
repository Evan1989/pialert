# PiAlert MCP

`mcp.php` is a stateless [MCP 2025-11-25](https://modelcontextprotocol.io/specification/2025-11-25) Streamable HTTP endpoint. It exposes AlertGroups and their source Alerts for reading and supports writing the AI comment (`comment_ai`) of an AlertGroup. Authentication also updates user statistics.

The endpoint uses the standard MCP `initialize` / `notifications/initialized` lifecycle. It does not create an `MCP-Session-Id`; every HTTP request is authenticated and handled independently. The endpoint supports JSON responses and does not open an SSE stream.

## Access

Create a regular PiAlert user, assign it access to the Dashboard page and, if needed, to the required Pi systems. Use the user's e-mail and password as HTTP Basic Auth credentials. The endpoint applies the same system restriction as `AuthorizationAdmin`: if the user has no records in `user_systems`, all systems are available.

The same Dashboard and system permissions apply to reading groups and writing AI comments; there is no separate MCP write permission. A group outside the user's accessible systems cannot be read or updated.

Endpoint: `https://<PiAlert-host>/src/api/mcp.php`

Every POST must include:

```http
Content-Type: application/json
Accept: application/json, text/event-stream
Authorization: Basic <base64(email:password)>
```

After initialization, every POST must also include the negotiated protocol version:

```http
MCP-Protocol-Version: 2025-11-25
```

The server does not use `Mcp-Method`, `Mcp-Name`, or protocol metadata in `params._meta`.

## Initialization

Send the standard initialization request without `MCP-Protocol-Version`:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {
    "protocolVersion": "2025-11-25",
    "capabilities": {},
    "clientInfo": {"name": "example", "version": "1.0"}
  }
}
```

The response advertises the `tools` capability and protocol version `2025-11-25`. The client must then send:

```json
{
  "jsonrpc": "2.0",
  "method": "notifications/initialized"
}
```

The notification receives HTTP `202 Accepted` with no response body. The same applies to other accepted notifications.

Example configuration for an MCP client:

```json
{
  "mcpServers": {
    "pialert": {
      "url": "https://pialert.example.com/src/api/mcp.php",
      "headers": {
        "Authorization": "Basic <base64(email:password)>"
      }
    }
  }
}
```

## Tools

- `list_alert_groups` — filtered, paginated list of accessible groups.
- `get_alert_group` — group details by `group_id`.
- `set_alert_group_comment_ai` — replace or clear the AI comment of an accessible group and return the updated group.
- `get_alerts_by_group` — recent source Alerts in a group.
- `get_alert_group_statistics` — aggregate and daily Alert counts.
- `find_similar_alert_groups` — groups with the same main error part, exactly as the Dashboard's “Find similar errors” action.

Status codes use the existing PiAlert values: `0` new, `1` ignore, `2` manual, `3` wait, `4` close, `5` reopen.

Each successful tool result contains a short summary in `content` and machine-readable data in `structuredContent`. MCP requires `structuredContent` to be an object, so tools returning lists use these properties:

- `list_alert_groups` and `find_similar_alert_groups`: `structuredContent.alert_groups`;
- `get_alerts_by_group`: `structuredContent.alerts`.

The other tools return their existing fields directly in `structuredContent`.

### Find groups without an AI comment

`list_alert_groups` accepts the optional boolean `empty_comment_ai` (default `false`). When `true`, it returns only groups where `comment_ai` is `NULL` or an empty string. When omitted or `false`, there is no AI-comment filter. It combines with `pi_system_name`, `status`, `search`, and `active_only`; filtering happens before `limit` and `offset` pagination.

Example `tools/call` request:

```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/call",
  "params": {
    "name": "list_alert_groups",
    "arguments": {"empty_comment_ai": true, "active_only": true, "limit": 25}
  }
}
```

Example result shape:

```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "result": {
    "content": [{"type": "text", "text": "Found 1 alert group(s)."}],
    "structuredContent": {
      "alert_groups": [{"group_id": 123, "comment_ai": null}]
    }
  }
}
```

The example abbreviates the AlertGroup object; `tools/list` provides its complete output schema.

### Write an AI comment

`set_alert_group_comment_ai` requires:

- `group_id`: a positive integer identifying an accessible group;
- `comment_ai`: a UTF-8 string of at most 2000 characters, or `null`. The value replaces the existing AI comment; `null` or `""` clears it. Omitting this argument is an error.

The tool updates only `comment_ai`. The human comment, its timestamp, group status, assignment, and last-user-action fields remain unchanged. Repeating a write with the same arguments is idempotent. The tool advertises `readOnlyHint: false`, `destructiveHint: true`, `idempotentHint: true`, and `openWorldHint: false`.

Example request:

```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "set_alert_group_comment_ai",
    "arguments": {
      "group_id": 123,
      "comment_ai": "Likely connection timeout. Check the receiver availability and retry settings."
    }
  }
}
```

Invalid tool input, missing groups, inaccessible groups, and execution failures return a normal `tools/call` result with `isError: true` and a short explanation in `content`. An unknown tool or malformed `tools/call` request returns JSON-RPC error `-32602`.

When processing a queue with `empty_comment_ai: true`, writing a nonempty comment removes that group from subsequent results. Fetch the next batch with `offset: 0` to avoid skipping groups as the queue shrinks.
