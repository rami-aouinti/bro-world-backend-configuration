# Configuration API Reference

This document describes every public HTTP endpoint exposed by the configuration service.
All routes are registered under the `/api` prefix via attribute routing, and responses are serialized as JSON by default.【F:config/routes.yaml†L1-L8】

## Conventions

- **Base URL** – When running locally with Docker the API is available at `http://localhost/api`.
- **Authentication** – Every endpoint documented below expects a JWT bearer token issued by the security stack powered by `lexik/jwt-authentication-bundle`. Supply it via `Authorization: Bearer <token>` unless stated otherwise.【F:composer.json†L51-L52】
- **Content type** – Send JSON payloads encoded as `application/json`. Symfony converts the request body into the parameter bag consumed by the controllers.
- **Identifiers** – Resource identifiers use UUID v1 strings. Back office endpoints also scope configuration rows by their `configurationKey`, `contextKey`, and `workplaceId` combination enforced through a unique constraint in the database.【F:src/Configuration/Domain/Entity/Configuration.php†L38-L116】
- **Configuration payload** – Domain entities wrap user-defined values inside a `_value` envelope to support optional encryption of protected system flags.【F:src/Configuration/Domain/Entity/Configuration.php†L178-L189】【F:src/Configuration/Domain/Entity/Configuration.php†L241-L349】 Expect the `_value` wrapper in responses and provide either a primitive or structured JSON object in requests.
- **Caching** – Read controllers hydrate responses from Redis-backed caches keyed by `configurations_<userId>` (per-user data) and `system_configurations` (admin catalogue). Items live for one hour and are purged automatically by write operations before persisting changes.【F:src/Configuration/Transport/Controller/Api/Frontend/GetConfigurationsController.php†L43-L86】【F:src/Configuration/Transport/Controller/Api/Frontend/GetConfigurationController.php†L63-L91】【F:src/Configuration/Transport/Controller/Api/Backend/GetConfigurationsController.php†L43-L86】【F:src/Configuration/Transport/Controller/Api/Backend/GetConfigurationController.php†L63-L88】【F:src/Configuration/Transport/Controller/Api/Frontend/PostConfigurationController.php†L62-L63】【F:src/Configuration/Transport/Controller/Api/Backend/PostConfigurationController.php†L62-L63】

## Data Model

Configuration entries share the following schema across every endpoint:

| Field | Type | Notes |
| --- | --- | --- |
| `id` | UUID | Generated server side and returned in responses.【F:src/Configuration/Domain/Entity/Configuration.php†L54-L148】 |
| `configurationKey` | string | Required, max 255 characters. Identifies the logical configuration entry.【F:src/Configuration/Domain/Entity/Configuration.php†L76-L188】 |
| `configurationValue._value` | JSON value | Stores the JSON payload associated with the key. Objects and primitives are accepted.【F:src/Configuration/Domain/Entity/Configuration.php†L178-L189】 |
| `contextKey` | string | Required contextual scope (for example `global` or a feature identifier).【F:src/Configuration/Domain/Entity/Configuration.php†L93-L199】 |
| `contextId` | UUID | Optional UUID that aligns the configuration with a context owner (for example a user or tenant).【F:src/Configuration/Domain/Entity/Configuration.php†L102-L209】 |
| `workplaceId` | UUID | Optional workspace identifier used in unique key enforcement.【F:src/Configuration/Domain/Entity/Configuration.php†L110-L218】 |
| `flags` | string[] | Each flag equals a value from the `FlagType` enum. User endpoints persist the `USER` flag, while admin endpoints persist `PROTECTED_SYSTEM`.【F:src/Configuration/Domain/Entity/Configuration.php†L118-L229】【F:src/Configuration/Transport/Controller/Api/Frontend/PostConfigurationController.php†L70-L78】【F:src/Configuration/Transport/Controller/Api/Backend/PostConfigurationController.php†L69-L77】 |

## Platform Endpoints (`/v1/platform/configuration`)

These routes operate on the authenticated user’s personal configurations. They require an authenticated user (`IS_AUTHENTICATED_FULLY`).

### `GET /v1/platform/configuration`

Returns the list of configuration entries owned by the caller.

- **Response 200** – Array of configuration objects filtered by the user’s UUID.【F:src/Configuration/Transport/Controller/Api/Frontend/GetConfigurationsController.php†L51-L86】
- **Caching** – Responses are served from the cached map when available; cache misses trigger a repository lookup and update the entry for future calls.【F:src/Configuration/Transport/Controller/Api/Frontend/GetConfigurationsController.php†L43-L86】

```bash
curl -H "Authorization: Bearer <token>" \
     http://localhost/api/v1/platform/configuration
```

**Example response**

```json
[
  {
    "id": "11111111-2222-4333-8444-555555555555",
    "configurationKey": "ui.theme",
    "configurationValue": {
      "_value": {
        "mode": "dark",
        "accentColor": "#0B7285"
      }
    },
    "contextKey": "global",
    "contextId": "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
    "workplaceId": null,
    "flags": ["USER"],
    "createdAt": "2024-03-18T10:17:24+00:00",
    "updatedAt": "2024-03-19T08:02:11+00:00"
  }
]
```

### `GET /v1/platform/configuration/{configurationKey}`

Fetches a single configuration entry selected by `configurationKey` and scoped to the current user.

- **Response 200** – Configuration object matching the key. Cached entries are reused until the TTL expires or a write invalidates the stored map.【F:src/Configuration/Transport/Controller/Api/Frontend/GetConfigurationController.php†L63-L91】
- **Response 401/403** – Emitted when the token is missing or the user lacks permission.【F:src/Configuration/Transport/Controller/Api/Frontend/GetConfigurationController.php†L42-L107】

```bash
curl -H "Authorization: Bearer <token>" \
     http://localhost/api/v1/platform/configuration/ui.theme
```

**Example response**

```json
{
  "id": "11111111-2222-4333-8444-555555555555",
  "configurationKey": "ui.theme",
  "configurationValue": {
    "_value": {
      "mode": "dark",
      "accentColor": "#0B7285"
    }
  },
  "contextKey": "global",
  "contextId": "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
  "workplaceId": null,
  "flags": ["USER"],
  "createdAt": "2024-03-18T10:17:24+00:00",
  "updatedAt": "2024-03-19T08:02:11+00:00"
}
```

### `POST /v1/platform/configuration`

Creates or replaces a configuration entry for the authenticated user. The controller upserts by `configurationKey` and rewrites the cache entry for that user.【F:src/Configuration/Transport/Controller/Api/Frontend/PostConfigurationController.php†L62-L96】

- **Required body fields**
  - `configurationKey` (string)
  - `contextKey` (string)
  - `configurationValue` (object or primitive; the service wraps it automatically)
- **Implicit values** – `userId`, `contextId`, and `workplaceId` default to the caller’s UUID, and the `USER` flag is applied.【F:src/Configuration/Transport/Controller/Api/Frontend/PostConfigurationController.php†L70-L78】

```bash
curl -X POST http://localhost/api/v1/platform/configuration \
     -H "Authorization: Bearer <token>" \
     -H "Content-Type: application/json" \
     -d '{
           "configurationKey": "ui.theme",
           "contextKey": "global",
           "configurationValue": {"mode": "dark"}
         }'
```

**Example response**

```json
{
  "id": "11111111-2222-4333-8444-555555555555",
  "configurationKey": "ui.theme",
  "configurationValue": {
    "_value": {
      "mode": "dark"
    }
  },
  "contextKey": "global",
  "contextId": "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
  "workplaceId": null,
  "flags": ["USER"],
  "createdAt": "2024-03-18T10:17:24+00:00",
  "updatedAt": "2024-03-19T08:02:11+00:00"
}
```

### `DELETE /v1/platform/configuration/{configurationKey}`

Removes a configuration entry owned by the authenticated user and clears their cache entry.【F:src/Configuration/Transport/Controller/Api/Frontend/DeleteConfigurationController.php†L46-L70】

```bash
curl -X DELETE -H "Authorization: Bearer <token>" \
     http://localhost/api/v1/platform/configuration/ui.theme
```

## Admin Endpoints (`/v1/admin/configuration`)

These routes are designed for administrative workflows. They are annotated as admin-only operations and work on system-level configurations marked with the `PROTECTED_SYSTEM` flag.

### `GET /v1/admin/configuration`

Returns every configuration entry flagged as `PROTECTED_SYSTEM`. The controller filters results after loading all rows, which allows administrators to review the protected catalogue.【F:src/Configuration/Transport/Controller/Api/Backend/GetConfigurationsController.php†L51-L75】

- **Caching** – Cached list lookups eliminate repeated serialization work and reduce load on the repository. A miss repopulates the cache and stores entries keyed by `configurationKey` for direct reuse by single-fetch operations.【F:src/Configuration/Transport/Controller/Api/Backend/GetConfigurationsController.php†L43-L86】

**Example response**

```json
[
  {
    "id": "99999999-aaaa-bbbb-cccc-dddddddddddd",
    "configurationKey": "billing.fraudThreshold",
    "configurationValue": {
      "_value": {
        "maxAttempts": 5,
        "lockMinutes": 15
      }
    },
    "contextKey": "global",
    "contextId": null,
    "workplaceId": null,
    "flags": ["PROTECTED_SYSTEM"],
    "createdAt": "2024-02-01T12:00:00+00:00",
    "updatedAt": "2024-03-05T09:30:00+00:00"
  }
]
```

### `GET /v1/admin/configuration/{configurationKey}`

Fetches a specific configuration by key. Because these entries are global, the lookup does not scope by user identifier.【F:src/Configuration/Transport/Controller/Api/Backend/GetConfigurationController.php†L42-L88】

- **Caching** – Individual configuration reads share the cached catalogue created by the list endpoint, so subsequent requests avoid round-trips to Doctrine unless writes clear the cache.【F:src/Configuration/Transport/Controller/Api/Backend/GetConfigurationController.php†L63-L88】

**Example response**

```json
{
  "id": "99999999-aaaa-bbbb-cccc-dddddddddddd",
  "configurationKey": "billing.fraudThreshold",
  "configurationValue": {
    "_value": {
      "maxAttempts": 5,
      "lockMinutes": 15
    }
  },
  "contextKey": "global",
  "contextId": null,
  "workplaceId": null,
  "flags": ["PROTECTED_SYSTEM"],
  "createdAt": "2024-02-01T12:00:00+00:00",
  "updatedAt": "2024-03-05T09:30:00+00:00"
}
```

### `POST /v1/admin/configuration`

Creates or updates a protected configuration. The controller invalidates the `system_configurations` cache key before persisting changes and guarantees that the `PROTECTED_SYSTEM` flag is stored.【F:src/Configuration/Transport/Controller/Api/Backend/PostConfigurationController.php†L62-L96】

- **Required body fields**
  - `configurationKey` (string)
  - `contextKey` (string)
  - `configurationValue` (object or primitive)
- **Implicit values** – `userId`, `contextId`, and `workplaceId` default to the admin user’s UUID; the `PROTECTED_SYSTEM` flag is set automatically.【F:src/Configuration/Transport/Controller/Api/Backend/PostConfigurationController.php†L69-L77】

```bash
curl -X POST http://localhost/api/v1/admin/configuration \
     -H "Authorization: Bearer <token>" \
     -H "Content-Type: application/json" \
     -d '{
           "configurationKey": "billing.fraudThreshold",
           "contextKey": "global",
           "configurationValue": {"maxAttempts": 5, "lockMinutes": 15}
         }'
```

**Example response**

```json
{
  "id": "99999999-aaaa-bbbb-cccc-dddddddddddd",
  "configurationKey": "billing.fraudThreshold",
  "configurationValue": {
    "_value": {
      "maxAttempts": 5,
      "lockMinutes": 15
    }
  },
  "contextKey": "global",
  "contextId": null,
  "workplaceId": null,
  "flags": ["PROTECTED_SYSTEM"],
  "createdAt": "2024-02-01T12:00:00+00:00",
  "updatedAt": "2024-03-05T09:30:00+00:00"
}
```

### `DELETE /v1/admin/configuration/{configurationKey}`

Deletes a protected configuration entry and purges the shared cache entry.【F:src/Configuration/Transport/Controller/Api/Backend/DeleteConfigurationController.php†L45-L69】

## Configuration Management Controller (`/v1/configuration`)

The `ConfigurationController` exposes a generic REST surface backed by reusable traits. It supports listing, retrieving, and mutating configuration rows via DTO-backed workflows and is primarily intended for automation or internal tooling.

- **List/Count/IDs** – `GET /v1/configuration`, `GET /v1/configuration/count`, and `GET /v1/configuration/ids` use reusable traits that provide search (`where`), ordering (`order`), pagination (`limit`/`offset`), and free-text (`search`) parameters processed by the shared `RequestHandler`.【F:src/Configuration/Transport/Controller/Api/ConfigurationController.php†L31-L37】【F:src/General/Transport/Rest/RequestHandler.php†L24-L125】
- **Retrieve** – `GET /v1/configuration/{id}` expects a UUID identifier and returns one entity.【F:src/Configuration/Transport/Controller/Api/ConfigurationController.php†L31-L37】【F:src/General/Transport/Rest/Traits/Actions/Admin/FindOneAction.php†L33-L65】
- **Create** – `POST /v1/configuration` consumes the `ConfigurationCreate` DTO and is restricted to root-level operators. Payload fields mirror the data model table above.【F:src/Configuration/Transport/Controller/Api/ConfigurationController.php†L35-L45】【F:src/Configuration/Application/DTO/Configuration/ConfigurationCreate.php†L10-L11】
- **Update** – `PUT /v1/configuration/{id}` consumes the `ConfigurationUpdate` DTO; `PATCH /v1/configuration/{id}` consumes the `ConfigurationPatch` DTO. Both accept partial updates based on visited fields and require root-level privileges.【F:src/Configuration/Transport/Controller/Api/ConfigurationController.php†L35-L45】【F:src/General/Transport/Rest/Traits/Actions/Root/UpdateAction.php†L33-L64】【F:src/General/Transport/Rest/Traits/Actions/Root/PatchAction.php†L33-L64】

### Query Parameters Supported by List/Count/Ids

| Parameter | Description |
| --- | --- |
| `where` | JSON object describing exact-match filters (supports dot notation and `IN` expressions).【F:src/General/Transport/Rest/RequestHandler.php†L30-L73】 |
| `order` | Comma separated string or associative array defining sort order per field.【F:src/General/Transport/Rest/RequestHandler.php†L76-L119】 |
| `limit` / `offset` | Pagination controls applied to collections.【F:src/General/Transport/Rest/RequestHandler.php†L121-L144】 |
| `search` | Free-text search criteria supporting AND/OR groups.【F:src/General/Transport/Rest/RequestHandler.php†L146-L210】 |
| `tenant` | Optional Doctrine entity manager name for multi-tenant scenarios.【F:src/General/Transport/Rest/RequestHandler.php†L132-L144】 |

### DTO Fields

The DTO layer validates incoming REST payloads and maps them onto entities. Every DTO inherits from `Configuration`, which defines validation rules for user profile metadata such as `title`, `description`, social URLs, and optional external identifiers.【F:src/Configuration/Application/DTO/Configuration/Configuration.php†L21-L118】 When composing `POST`, `PUT`, or `PATCH` payloads through `/v1/configuration`, include the fields you want to set following those constraints.

## Error Handling

- Validation issues return `400 Bad Request` with descriptive messages produced by Symfony’s validator component.
- Missing resources trigger `404 Not Found` through the shared REST method helper logic.【F:src/General/Transport/Rest/Traits/RestMethodHelper.php†L90-L192】
- Authorization problems result in `401` or `403` responses depending on the authentication state. The OpenAPI attributes embedded in the controllers surface these codes in Swagger UI.【F:src/Configuration/Transport/Controller/Api/Frontend/GetConfigurationController.php†L46-L86】

## Related Tooling

- Swagger UI is available at `http://localhost/api/doc` when running the Docker environment and automatically reflects the annotations used above.【F:docs/swagger.md†L1-L5】
- Postman collections can be found in the `docs/postman` directory for interactive exploration.
