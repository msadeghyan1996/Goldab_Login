# Deployment Guide (Docker Compose)

Follow these steps to run the project in production mode using Docker Compose.

## Prerequisites

- **Docker Engine** and the **Docker Compose plugin** must be installed.
- Verify installation:
  ```bash
  docker compose version
  ```

> Run all commands from the **project root**.

## 1) Configure Environment

- Ensure a root `.env` file exists and adjust any values you need (e.g., **ports**, **service/container names**, database settings). Docker Compose will load variables from this file automatically.
- Prepare the **landing** environment file:
  ```bash
  cp ./landing/.env.example ./landing/.env
  # Open ./landing/.env and edit values as needed
  ```

## 2) Build Images

Build the images defined in the production compose file:

```bash
docker compose -f ./docker-compose.prod.yml build
```

## 3) Start Services

Start all services in the background (detached mode):

```bash
docker compose -f ./docker-compose.prod.yml up -d
```

## 4) Run Database Migrations (API)

Open a shell inside the `api` service container and run Laravel migrations:

```bash
docker compose exec api /bin/sh
php artisan migrate
# exit when done
```

## 5) Now Enjoy Logging In!

Open your favorite browser, and open this link (You may have changed some ports. Assuming you haven't):

```
http://127.0.0.1:9010/auth
```

You can also open PHPMyAdmin here:

```
http://127.0.0.1:9015
```

Default credentials to login to PMA is "goldab_admin" and "secret"

## Notes

- You can change **ports** and **names** from the root `.env`. If you change these, rebuild/restart as needed:
  ```bash
  docker compose -f ./docker-compose.prod.yml build
  docker compose -f ./docker-compose.prod.yml up -d
  ```
- View logs:
  ```bash
  docker compose -f ./docker-compose.prod.yml logs -f
  ```
- Stop and remove containers, networks, and volumes created by this file:
  ```bash
  docker compose -f ./docker-compose.prod.yml down
  ```
