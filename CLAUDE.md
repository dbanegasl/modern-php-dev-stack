# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Repository Is

A decoupled Docker-based local development stack (a modern replacement for XAMPP) that hosts multiple independent web projects under `src/`. The infrastructure (this repo) and the apps it serves (subdirectories of `src/`) are separate concerns: most day-to-day coding happens inside a `src/<project>/` directory, while `docker-compose.yml` and `docker/` define the shared environment.

## Architecture

One central **PHP 8.3 FPM** container (Alpine, with gd/zip/intl/opcache/apcu/pdo_mysql/mysqli/xdebug + Composer) is fronted by **two concurrent web servers** — Nginx and Apache — both serving the same `./src` mount at `/var/www/html` over HTTP and HTTPS (self-signed certs in `docker/ssl/`, gitignored — generate with `sh docker/ssl/generate.sh [extra IPs/hosts]`; Nginx/Apache will not start without them). Supporting services: MariaDB 11.5, phpMyAdmin, Mailpit (mail catcher), and a Node 22 container for frontend asset builds. All containers share the `modern-xampp-net` bridge network and the `America/Guayaquil` timezone.

| Service | Host port(s) | Notes |
|---|---|---|
| Nginx | 8801 HTTP, 8811 HTTPS | Config: `docker/nginx/default.conf` |
| Apache | 8802 HTTP, 8812 HTTPS | Config: `docker/apache/httpd.conf`, supports `.htaccess` |
| MariaDB | 8803 | Hostname `db` (port 3306) from inside the network; utf8mb4/utf8mb4_unicode_ci |
| phpMyAdmin | 8804 | |
| Mailpit | 8805 UI, 8825 SMTP | PHP sends mail to `mailpit:1025` inside the network |
| Node/Vite | 8831 → 5173 | Container idles (`tail -f`); run commands via `docker compose exec node` |

Port mappings and PHP version live in `.env` (gitignored; template in `.env.example`). A project is reachable at `http://localhost:8801/<dirname>/` (or any of the other three web endpoints).

### Secrets

DB passwords are Docker Secrets read from `.secrets/*.txt` (gitignored) — never in env vars or code. PHP connects via `_FILE`-style env vars:

```php
$db_password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));
```

Changing a secret requires `docker compose down && docker compose up -d`.

### Nginx routing (`docker/nginx/default.conf`)

HTTP and HTTPS server blocks are near-duplicates — **generic edits must be applied to both**. Key behaviors: `try_files` falls back to `/index.php?$query_string`, PHP goes to `php:9000` via FastCGI (with `try_files $uri =404` guard), `absolute_redirect off` preserves the external port mapping, `client_max_body_size 20M`, deny-rules for dotfiles and sensitive extensions (`.env/.log/.sql/.lock/.sh/.bak/.ini/.dist/.md`), and security headers (HSTS on HTTPS only).

### Per-project rules (`docker/*/conf.d/`)

`default.conf` stays generic. Project-specific rules (SPA routing, upload-dir PHP blocking, private directories) live in `docker/nginx/conf.d/<project>.conf`, mounted at `/etc/nginx/extra.d` and injected with `include /etc/nginx/extra.d/*.conf;` into **both** server blocks, before the generic PHP location (Nginx uses the first matching regex, so deny rules must precede it; use `^~` prefixes for locations that must beat the PHP regex). Apache does the same with `docker/apache/conf.d/*.conf` (`IncludeOptional conf/extra.d/*.conf` inside both VirtualHosts).

Only `*.conf.example` templates are versioned; real `*.conf` files are gitignored. To enable a project: copy the example, edit it, then `docker compose exec nginx nginx -t && docker compose exec nginx nginx -s reload` (Apache: `docker compose restart apache`). No container recreation needed.

## Commands

All from the repo root:

```bash
docker compose up -d --build     # build + start the whole stack
docker compose stop / start      # pause / resume (keeps containers)
docker compose down              # tear down (DB volume survives)
docker compose down -v           # tear down INCLUDING the database volume

# PHP / Composer (run inside the php container, working dir /var/www/html = src/):
docker compose exec php composer install
docker compose exec php php somefile.php

# Node / npm (node container, same working dir):
docker compose exec node npm install
docker compose exec node npm run dev     # Vite dev server, exposed on host 8831 (PORT_VITE); Astro on 8832 (PORT_ASTRO)
docker compose exec node npm run build
```

There are no stack-level tests or linters; each project under `src/` brings its own tooling.

## Debugging

Xdebug 3 is preconfigured (mode via `XDEBUG_MODE` in `.env`, port 9003, `host.docker.internal` loopback). Use the "Listen for Xdebug (Docker)" launch config in `.vscode/` — path mappings from `/var/www/html` to `./src` are already set. Nginx FastCGI timeouts are raised to 3600s so debug sessions don't 504.

## Projects in `src/`

Each subdirectory is an independent PHP site/app (e.g. `mediabc-app`, several `web-*` client sites). `src/index.php` is the stack's status dashboard (macOS-Finder-style project list, Xdebug trigger button). Before working on a project, check for its own `CLAUDE.md`/docs inside its directory. Non-PHP projects do not belong in this stack — they live as sibling repos under `~/dev/docker/` (e.g. `appGameMemory`, an Angular + NestJS app with its own docker-compose).

## Documentation

`docs/` contains ten numbered guides (quickstart, structure, Xdebug, secrets, timezone/charset, frontend assets, Nginx/Apache, Mailpit, database, troubleshooting) — consult these before re-deriving how the stack works.
