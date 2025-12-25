# API Authentication

Token-based API authentication is now available for integrations. Use the endpoints under `/api/auth` to issue and revoke tokens, then include the bearer token in the `Authorization` header when calling protected API routes.

## Endpoints

| Endpoint | Method | Description | Middleware |
| --- | --- | --- | --- |
| `/api/auth/token` | POST | Exchange phpIP credentials for a bearer token. | `throttle:api-auth` |
| `/api/auth/me` | GET | Return the authenticated user's profile. | `auth:api`, `throttle:api` |
| `/api/auth/logout` | POST | Revoke the currently used token. | `auth:api`, `throttle:api` |
| `/api/ping` | GET | Simple authenticated health probe. | `auth:api`, `throttle:api` |

## Request examples

### Issue a token

```bash
curl -X POST https://your-domain.example.com/api/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "login": "phpipuser",
    "password": "changeme",
    "device_name": "integration-script"
  }'
```

### Call a protected endpoint

```bash
curl https://your-domain.example.com/api/auth/me \
  -H "Authorization: Bearer <token-from-response>"
```

### Revoke the current token

```bash
curl -X POST https://your-domain.example.com/api/auth/logout \
  -H "Authorization: Bearer <token-from-response>"
```

## Configuration

Adjust these environment variables to tune rate limits and token lifetime:

- `API_RATE_LIMIT` – maximum API calls per minute (default: 120)
- `API_AUTH_RATE_LIMIT` – maximum token issuance attempts per minute (default: 10)
- `API_TOKEN_EXPIRATION` – token expiration in minutes; set to `0` or leave empty for non-expiring tokens

## OpenAPI

An OpenAPI specification for the authentication endpoints lives at `docs/openapi/api-authentication.yaml`.
