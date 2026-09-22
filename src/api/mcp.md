# PiAlert MCP

`mcp.php` is a stateless [MCP 2026-07-28](https://modelcontextprotocol.io/specification/2026-07-28) Streamable HTTP endpoint. It exposes AlertGroups and their source Alerts for reading and supports writing the AI comment (`comment_ai`) of an AlertGroup. Authentication also updates user statistics.

This is the current stateless MCP protocol, not a custom transport. It does
**not** use the legacy `initialize` / `notifications/initialized` handshake or
`MCP-Session-Id`. Each request is self-describing. `server/discover` is the
standard optional discovery RPC; a client may also call `tools/list` directly.

## Access

Create a regular PiAlert user, assign it access to the Dashboard page and, if needed, to the required Pi systems. Use the user's e-mail and password as HTTP Basic Auth credentials. The endpoint applies the same system restriction as `AuthorizationAdmin`: if the user has no records in `user_systems`, all systems are available.

The same Dashboard and system permissions apply to reading groups and writing AI comments; there is no separate MCP write permission. A group outside the user's accessible systems cannot be read or updated.

Endpoint: `https://<PiAlert-host>/src/api/mcp.php`

The client must send `Content-Type: application/json`, an `Accept` header that
includes `application/json` and `text/event-stream`, and HTTP Basic Auth. Each
POST must include these standard MCP 2026-07-28 fields:

```http
MCP-Protocol-Version: 2026-07-28
Mcp-Method: server/discover
```

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "server/discover",
  "params": {
    "_meta": {
      "io.modelcontextprotocol/protocolVersion": "2026-07-28",
      "io.modelcontextprotocol/clientInfo": {"name": "example", "version": "1.0"},
      "io.modelcontextprotocol/clientCapabilities": {}
    }
  }
}
```

For `tools/call`, `resources/read`, and `prompts/get`, also send the standard
`Mcp-Name` header matching `params.name` or `params.uri`.

Example configuration for an MCP client that supports HTTP headers:

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

### Find groups without an AI comment

`list_alert_groups` accepts the optional boolean `empty_comment_ai` (default `false`). When `true`, it returns only groups where `comment_ai` is `NULL`, an empty string. When omitted or `false`, there is no AI-comment filter. It combines with `pi_system_name`, `status`, `search`, and `active_only`; filtering happens before `limit` and `offset` pagination.

Example `tools/call` request (send `Mcp-Method: tools/call` and `Mcp-Name: list_alert_groups` along with the common headers):

```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/call",
  "params": {
    "name": "list_alert_groups",
    "arguments": {"empty_comment_ai": true, "active_only": true, "limit": 25},
    "_meta": {
      "io.modelcontextprotocol/protocolVersion": "2026-07-28",
      "io.modelcontextprotocol/clientCapabilities": {}
    }
  }
}
```

### Write an AI comment

`set_alert_group_comment_ai` requires:

- `group_id`: a positive integer identifying an accessible group.
- `comment_ai`: a UTF-8 string of at most 2000 characters, or `null`. The value replaces the existing AI comment; `null` or `""` clears it. Omitting this argument is an error.

The tool updates only `comment_ai`. The human comment, its timestamp, group status, assignment, and last-user-action fields remain unchanged. Repeating a write with the same arguments is idempotent. The tool advertises `readOnlyHint: false`, `destructiveHint: true` (it can overwrite an existing comment), `idempotentHint: true`, and `openWorldHint: false`.

Example request (send `Mcp-Method: tools/call` and `Mcp-Name: set_alert_group_comment_ai` along with the common headers):

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
    },
    "_meta": {
      "io.modelcontextprotocol/protocolVersion": "2026-07-28",
      "io.modelcontextprotocol/clientCapabilities": {}
    }
  }
}
```

The result contains a short text summary in `content` and the updated AlertGroup in `structuredContent`. All tools returning groups include the nullable `comment_ai` field. Invalid arguments (including comments over 2000 characters), missing groups, and inaccessible groups return JSON-RPC error `-32602` with HTTP 400.

When processing a queue with `empty_comment_ai: true`, writing a nonempty comment removes that group from subsequent results. Fetch the next batch with `offset: 0` to avoid skipping groups as the queue shrinks.
