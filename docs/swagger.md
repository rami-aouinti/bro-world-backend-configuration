# Swagger

The configuration service ships with [NelmioApiDocBundle](https://symfony.com/doc/current/bundles/NelmioApiDocBundle/index.html) so you can explore every route graphically.

- **Swagger UI** – http://localhost/api/doc renders the OpenAPI definition generated from the controller attributes.【F:config/routes/nelmio_api_doc.yaml†L1-L15】
- **Swagger JSON** – http://localhost/api/doc.json returns the raw OpenAPI payload for importing into tooling.【F:config/routes/nelmio_api_doc.yaml†L1-L7】
- **Reference material** – The [Configuration API reference](./configuration-api.md) expands the auto-generated specification with conventions, role requirements, and payload examples.

If the UI loads without any endpoints, ensure that the application is running in the `dev` environment; the documentation routes are only enabled for development and test contexts.【F:config/routes/nelmio_api_doc.yaml†L1-L15】
