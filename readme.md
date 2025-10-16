# Bro World Configuration Service

Bro World Configuration Service is a Dockerised Symfony 7 application that centralises configuration management for the Bro World platform. It exposes a JWT-protected REST API that allows the platform to retrieve user-specific preferences, administrative system settings, and generic configuration workflows from a single source of truth.

The repository bundles everything you need to develop, test, and ship the service:

- PHP 8.4 runtime with Symfony 7, Messenger, Doctrine, and API Platform.
- A MySQL 8 database with repeatable migrations managed through Symfony.
- Supporting services for asynchronous processing and observability (RabbitMQ, Elasticsearch, Kibana, Redis, Mailpit).
- An opinionated developer toolchain (PHPStan, Rector, ECS, PHPUnit, Infection, PHP Insights, etc.) exposed through the included `Makefile` targets.

---

## Table of Contents

1. [Documentation](#documentation)
2. [Requirements](#requirements)
3. [Project Layout](#project-layout)
4. [Local Development](#local-development)
5. [Running Quality Tooling](#running-quality-tooling)
6. [Staging Environment](#staging-environment)
7. [Production Environment](#production-environment)
8. [Troubleshooting](#troubleshooting)
9. [Next Steps](#next-steps)

---

## Documentation

- [Configuration API reference](docs/configuration-api.md) – exhaustive endpoint catalogue with request/response examples.
- [Swagger UI](http://localhost/api/doc) – live, auto-generated OpenAPI definition when the stack runs locally.
- Additional guides live under the [`docs/`](docs) folder (development environment setup, testing strategies, Xdebug, Postman collections, and more).

## Requirements

Before you start, ensure the host machine provides:

- Docker Engine 23.0+
- Docker Compose 2.0+
- GNU Make
- An editor or IDE with PHP support
- MySQL Workbench or TablePlus (optional but recommended for database inspection)

> **Tip:** On Linux add your user to the `docker` group (`sudo usermod -aG docker $USER`). On macOS enable `virtiofs` in Docker Desktop 4.22+ to improve volume performance.

## Project Layout

```
├── bin/                # Symfony console and helper executables
├── config/             # Framework and bundle configuration
├── docs/               # How-to guides, API reference, Postman collections
├── docker/             # Environment-specific Docker assets (dev, staging, prod)
├── migrations/         # Doctrine migrations for the database schema
├── public/             # Front controller (`index.php`) exposed by the web server
├── src/                # Application code (entities, controllers, services, etc.)
├── templates/          # Twig templates for API documentation and emails
├── tests/              # PHPUnit and integration tests
└── var/                # Generated cache, logs, and database volumes (ignored)
```

Key entrypoints:

- `Makefile` — main automation hub for Docker, Composer, testing, and QA tasks.
- `compose*.yaml` files — Docker Compose configurations for local, staging, prod, and CI use cases.
- `encryption.php` / `encryption.key` — helpers for the attribute-based encryption features.

## Local Development

1. **Clone or scaffold the project**
   ```bash
   git clone https://github.com/systemsdk/docker-symfony-api.git
   # or
   composer create-project systemsdk/docker-symfony-api bro-world-backend-configuration
   ```

2. **Configure secrets and environment**
   - Update the `APP_SECRET` value in `.env.prod` and `.env.staging`.
   - Do not commit `.env.local.php` in development or test environments.
   - Optional overrides (ports, Xdebug settings, mail sink, etc.) belong in `.env.local`.
   - JWT certificates live in `config/jwt/`; regenerate them with `make generate-jwt-keys` when onboarding new environments.

3. **Clean stale state**
   - Remove `var/mysql-data` before the first boot or when recreating containers to avoid mismatched schemas.

4. **Host entries**
   ```
   127.0.0.1    localhost
   ```
   Add custom domains here if you wish to expose the API behind a vanity URL.

5. **Configure Xdebug (optional)**
   - Linux/Windows: edit `docker/dev/xdebug-main.ini`.
   - macOS: edit `docker/dev/xdebug-osx.ini`.
   - Toggle `xdebug.start_with_request` depending on whether you want to capture every request or only those triggered via an IDE key.

6. **Elasticsearch bootstrap user**
   ```
   user: elastic
   password: changeme
   ```
   Replace the default password for staging and production deployments.

7. **Build and start the stack**
   ```bash
   make build
   make start
   make composer-install
   make generate-jwt-keys
   ```
   Use `make stop` and `make destroy` to halt or tear down the stack when needed.

8. **Initialise application data**
   ```bash
   make migrate
   make create-roles-groups
   make migrate-cron-jobs
   make messenger-setup-transports
   make elastic-create-or-update-template
   ```
   These commands provision database schema, seed fixtures, configure Messenger transports, and register Elasticsearch templates.

9. **Developer dashboards**
   - API documentation: http://localhost/api/doc
   - RabbitMQ: http://localhost:15672
   - Kibana: http://localhost:5601
   - Mailpit: http://localhost:8025

## Running Quality Tooling

Common QA workflows are pre-wired in the `Makefile`:

```bash
make phpstan           # Static analysis
make phpunit           # Full PHPUnit test suite
make phpunit-coverage  # Generates coverage report under var/tests/coverage
make ecs               # Coding standards (EasyCodingStandard)
make rector            # Automated refactoring suggestions
make phpinsights       # Code quality metrics
make infection         # Mutation testing (ensure Xdebug is enabled)
```

Use `make help` to list all available targets and short descriptions.

## Staging Environment

Follow the same steps as local development with these adjustments:

- Run `make build-staging` and `make start-staging`.
- Use `make migrate-no-test` in place of `make migrate`.
- Regenerate JWT keys with `make generate-jwt-keys` and distribute them securely.
- Review `compose-staging.yaml` for resource sizing and service credentials before deployment.

## Production Environment

1. Provision the project as described above.
2. Edit `compose-prod.yaml` and `.env.prod` with the desired MySQL, RabbitMQ, Redis, and Elasticsearch credentials.
3. Remove `var/mysql-data` if it exists to prevent reusing development data.
4. Use the production Make targets:
   ```bash
   make build-prod
   make start-prod
   ```
5. Configure persistent storage for MySQL, Redis, and Elasticsearch volumes according to your hosting provider.
6. Set up automated backups for the MySQL database and encryption keys.

## Troubleshooting

| Symptom | Resolution |
| --- | --- |
| Containers restart continuously | Run `docker compose logs <service>` to inspect errors, then clean volumes with `make destroy` and rebuild. |
| API returns 401 responses | Ensure `config/jwt/*.pem` files exist. Regenerate with `make generate-jwt-keys` if necessary. |
| Elasticsearch fails to start | Verify that the host machine has at least 4GB RAM allocated to Docker and that the `vm.max_map_count` kernel setting is >= 262144. |
| PHPUnit cannot connect to the database | Execute `make migrate -- env=test` to refresh the test schema or recreate the `var/mysql-data` volume. |

## Next Steps

- Review the [`Makefile`](Makefile) to discover additional automation (running test suites, static analysis, linting, etc.).
- Import the Postman collection under `docs/postman` for quick manual testing.
- Check `docs/testing.md` for information about PHPUnit, integration tests, coverage tooling, and recommended testing conventions.
- Explore `docs/development-environment.md` for advanced Docker usage and IDE integration tips.

