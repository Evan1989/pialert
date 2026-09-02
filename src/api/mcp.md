# PiAlert MCP

`mcp.php` is a read-only, stateless [MCP 2026-07-28](https://modelcontextprotocol.io/specification/2026-07-28) Streamable HTTP endpoint. It exposes AlertGroups and their source Alerts; it never changes PiAlert data except User statistics.

This is the current stateless MCP protocol, not a custom transport. It does
**not** use the legacy `initialize` / `notifications/initialized` handshake or
`MCP-Session-Id`. Each request is self-describing. `server/discover` is the
standard optional discovery RPC; a client may also call `tools/list` directly.

## Access

Create a regular PiAlert user, assign it access to the Dashboard page and, if needed, to the required Pi systems. Use the user's e-mail and password as HTTP Basic Auth credentials. The endpoint applies the same system restriction as `AuthorizationAdmin`: if the user has no records in `user_systems`, all systems are available.

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
- `get_alerts_by_group` — recent source Alerts in a group.
- `get_alert_group_statistics` — aggregate and daily Alert counts.
- `find_similar_alert_groups` — groups with the same main error part, exactly as the Dashboard's “Find similar errors” action.

Status codes use the existing PiAlert values: `0` new, `1` ignore, `2` manual, `3` wait, `4` close, `5` reopen.
