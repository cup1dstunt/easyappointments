# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A fork of Easy!Appointments (PHP >= 8.2, CodeIgniter 3 with custom `EA_*` core overrides, MySQL/MariaDB, jQuery/Bootstrap front end). The fork adds "LNU" features (see below) and a production Docker/Unraid deployment.

## Commands

- Install PHP deps: `composer install`; JS deps: `npm install`
- Build front end: `npx gulp build` (alias `npm run build`). `npm start` runs gulp in watch mode. Compiles `assets/js/**` (Babel + minify to `.min.js`), SCSS to CSS, and copies `node_modules` into `assets/vendor` (`gulp vendor`).
- Tests (PHPUnit 12, only `tests/Unit`): `composer test` or `APP_ENV=testing vendor/bin/phpunit`. Single test: `APP_ENV=testing vendor/bin/phpunit --filter ArrayHelperTest` or pass a file path.
- Format: Prettier with `@prettier/plugin-php` (`.prettierrc.json`), 4-space indent.
- CLI (CodeIgniter console controller): `php index.php console <command>`, e.g. `migrate`, `migrate_lnu_down [name]`; run `php index.php console` for the list.
- Local dev stack: root `docker-compose.yml` + `docker/` (nginx, php-fpm, mysql). Configuration is `config.php` (copy from `config-sample.php`).
- Production: root `Dockerfile` + `deploy/docker-entrypoint.sh` (generates `config.php` from env vars) and `deploy/unraid/` (compose stack + German guide). CI (`.github/workflows/ci.yml`) runs PHPUnit; `docker-image.yml` publishes the image to GHCR on push to main.

## Architecture

- Standard CI3 MVC under `application/`: `controllers/` (one per backend page, plus `Booking*` public pages), `models/`, `views/`, `libraries/`, `helpers/`, `core/` (`EA_Controller`, `EA_Model`, `EA_Migration`, etc. extend/replace CI core classes), `hooks/` (security headers, storage cleanup).
- REST API: `controllers/api/v1/*_api_v1.php`. Routes are registered in `config/routes.php` via `route_api_resource()` (in `helpers/routes_helper.php`), which maps resource names to `<resource>_api_v1` controllers. `routes.php` also sets security/CORS headers and enforces allowed HTTP methods.
- Business logic lives in `libraries/` (e.g. `Availability`, `Synchronization`, `Google_sync`, `Caldav_sync`, `Notifications`, `Webhooks_client`, `Permissions`) and models; controllers are thin.
- Front end: source in `assets/js` (`pages/`, `components/`, `http/` clients, `utils/`, `layouts/`), compiled in place to `.min.js` by gulp. Views reference the minified files, so rebuild after JS changes. Do not commit `assets/vendor` / `node_modules` output unless it is already tracked.
- Migrations: sequential numbered files in `application/migrations/` (version in `config/migration.php` is overwritten by the installer/updater). Existing migrations guard their changes (`field_exists()`/`table_exists()`).

## Fork-specific: LNU migrations

Files named `application/migrations/lnu_*.php` are not versioned. `EA_Migration::run_lnu_migrations()` runs every one on every migrate, so each must be idempotent (check before altering). They can be rolled back with `console migrate_lnu_down`. LNU migration output must stay CLI-only (guard with `is_cli()`), otherwise it corrupts the web installer's JSON responses.

## Dependency pitfalls

Do not bump `@babel/core` / `@babel/preset-env` to 8: `babel-preset-minify` is incompatible and `gulp build` would stop producing `.min.js`. `del` is v8 (use `deleteSync`, as in `gulpfile.js`).

## Repo conventions (from `.github/copilot-instructions.md`)

- Follow existing style; keep changes scoped to the task.
- Update `CHANGELOG.md` for each completed task (Added/Fixed/Improved under the current version).
- Commit with conventional-style messages (`feat:`, `fix:`, `chore:`, `refactor:`); the repo guidance says to skip the "Co-authored-by" line.
- Update README/docs when behavior changes.
