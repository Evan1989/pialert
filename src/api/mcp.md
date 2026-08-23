# PiAlert MCP

`mcp.php` is a read-only, stateless HTTP MCP endpoint. It exposes AlertGroups and their source Alerts; it never changes PiAlert data except User statistics

## Access

Create a regular PiAlert user, assign it access to the Dashboard page and, if needed, to the required Pi systems. Use the user's e-mail and password as HTTP Basic Auth credentials. The endpoint applies the same system restriction as `AuthorizationAdmin`: if the user has no records in `user_systems`, all systems are available.

Endpoint: `https://<PiAlert-host>/src/api/mcp.php`

The client must send `Content-Type: application/json` and HTTP Basic Auth. The endpoint implements MCP Streamable HTTP requests over `POST`; no MCP session is persisted.

Example configuration for an MCP client that supports custom headers:

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
