# Bro World Configuration Service

A Dockerized Symfony 7 application that centralises configuration management for the Bro World platform. The service exposes a JWT-protected REST API for user-specific preferences, administrative system settings, and generic configuration maintenance workflows.

- Built with PHP 8.4, Symfony 7, and MySQL 8.
- Includes supporting services such as RabbitMQ, Elasticsearch, Kibana, Redis, and Mailpit for local development.
- Ships with an opinionated toolchain (PHPStan, Rector, ECS, PHPUnit, etc.) wired through the included `Makefile` targets.

## Documentation

- [Configuration API reference](docs/configuration-api.md) – exhaustive endpoint catalogue with request/response examples.
- [Swagger UI](http://localhost/api/doc) – live, auto-generated OpenAPI definition when the stack runs locally.
- Additional guides live under the [`docs/`](docs) folder (development environment, testing, Xdebug, Postman collections, and more).

## Requirements

Before you start, make sure the host machine provides:

- Docker Engine 23.0+
- Docker Compose 2.0+
- An editor or IDE with PHP support
- MySQL Workbench (optional but recommended for database inspection)

> **Tip:** On Linux add your user to the `docker` group (`sudo usermod -aG docker $USER`). On macOS enable `virtiofs` in Docker Desktop 4.22+ to improve volume performance.

## Local Development

1. **Clone or scaffold the project**
   ```bash
   git clone https://github.com/systemsdk/docker-symfony-api.git
   # or
   composer create-project systemsdk/docker-symfony-api bro-world-backend-configuration
   ```

2. **Secrets and environment**
   - Update the `APP_SECRET` value in `.env.prod` and `.env.staging`.
   - Do not commit `.env.local.php` in development or test environments.
   - Optional overrides (ports, Xdebug) belong in `.env.local`.

3. **Clean stale state**
   - Remove `var/mysql-data` before the first boot or when recreating containers.

4. **Host entries**
   ```
   127.0.0.1    localhost
   ```

5. **Xdebug configuration (optional)**
   - Linux/Windows: edit `docker/dev/xdebug-main.ini`.
   - macOS: edit `docker/dev/xdebug-osx.ini`.
   - Toggle `xdebug.start_with_request` depending on whether you want to capture every request or only those triggered via an IDE key.

6. **Elasticsearch bootstrap user**
   ```
   user: elastic
   password: changeme
   ```
   (Replace the password for staging and production deployments.)

7. **Build and start the stack**
   ```bash
   make build
   make start
   make composer-install
   make generate-jwt-keys
   ```

8. **Initialise application data**
   ```bash
   make migrate
   make create-roles-groups
   make migrate-cron-jobs
   make messenger-setup-transports
   make elastic-create-or-update-template
   ```

9. **Developer dashboards**
   - API documentation: http://localhost/api/doc
   - RabbitMQ: http://localhost:15672
   - Kibana: http://localhost:5601
   - Mailpit: http://localhost:8025

## Staging Environment

Follow the same steps as local development with these adjustments:

- Run `make build-staging` and `make start-staging`.
- Use `make migrate-no-test` in place of `make migrate`.
- Regenerate JWT keys with `make generate-jwt-keys`.

## Production Environment

1. Provision the project as described above.
2. Edit `compose-prod.yaml` and `.env.prod` with the desired MySQL and RabbitMQ credentials.
3. Remove `var/mysql-data` if it exists.
4. Use the production Make targets:
   ```bash
   make build-prod
   make start-prod
   ```

## Next Steps

- Review the [`Makefile`](Makefile) to discover additional automation (running test suites, static analysis, linting, etc.).
- Import the Postman collection under `docs/postman` for quick manual testing.
- Check `docs/testing.md` for information about PHPUnit, integration tests, and coverage tooling.
