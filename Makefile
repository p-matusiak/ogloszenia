# Zunto — install / build / deploy / diagnostics
#
# All commands run inside Docker (no local PHP/Node required).
# Works in any checkout (dev or prod) — container names and ports come from ./.env,
# which is both the Compose env file and the Laravel env file.
#
#   make help      list targets
#   make install   first-time bootstrap (dev, with demo data)
#   make deploy    update a running production checkout
#   make doctor    diagnose 500/502 and other "site is down" symptoms

SHELL := /bin/bash
.DEFAULT_GOAL := help

DC      := docker compose
PHP     := $(DC) exec -T php
ARTISAN := $(PHP) php artisan
# `run` (not `exec`): node is behind the `vite` profile and is not up by default.
NODE    := $(DC) run --rm --no-deps -T node sh -c
PSQL    := $(DC) exec -T postgres sh -c

# Pinned buildx used by `make buildx` when the plugin is missing.
BUILDX_VERSION := v0.31.1

BACKUP_DIR := storage/backups
STAMP      := $(shell date +%Y%m%d-%H%M%S)

# docker/php/Dockerfile hardcodes `USER app` = uid/gid 1000, so every directory
# Laravel writes to must be owned by 1000 — including on a root-owned checkout.
RUN_UID  := 1000
RUN_GID  := 1000
WRITABLE := bootstrap/cache storage

C_OK   := \033[0;32m
C_WARN := \033[0;33m
C_ERR  := \033[0;31m
C_OFF  := \033[0m

.PHONY: help
help: ## List available targets
	@echo "Zunto — make targets"
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ---------------------------------------------------------------------------
# Install
# ---------------------------------------------------------------------------

.PHONY: install
install: env permissions images up wait-db composer key migrate seed storage-link frontend cache ## Full bootstrap for DEV (includes demo ads)
	@printf "$(C_OK)==> dev install done$(C_OFF)\n"
	@$(MAKE) --no-print-directory doctor

.PHONY: install-prod
install-prod: env permissions images up wait-db composer-prod key migrate-force seed-categories storage-link frontend cache ## Full bootstrap for PROD (categories only, no demo ads)
	@printf "$(C_OK)==> prod install done$(C_OFF)\n"
	@$(MAKE) --no-print-directory doctor

.PHONY: env
env: ## Create .env from .env.example if missing
	@test -f .env || { cp .env.example .env; \
		printf "$(C_WARN)created ./.env — set APP_ENV/APP_URL/APP_DEBUG, DB_PASSWORD, container names and ports$(C_OFF)\n"; }

.PHONY: permissions
permissions: ## Make bootstrap/cache + storage writable by the container user (uid 1000)
	@mkdir -p bootstrap/cache \
		storage/app/public storage/app/private \
		storage/framework/cache/data storage/framework/sessions \
		storage/framework/testing storage/framework/views \
		storage/logs
	@if chown -R $(RUN_UID):$(RUN_GID) $(WRITABLE) 2>/dev/null; then \
		printf "$(C_OK)bootstrap/cache + storage -> $(RUN_UID):$(RUN_GID)$(C_OFF)\n"; \
	elif sudo -n chown -R $(RUN_UID):$(RUN_GID) $(WRITABLE) 2>/dev/null; then \
		printf "$(C_OK)bootstrap/cache + storage -> $(RUN_UID):$(RUN_GID) (sudo)$(C_OFF)\n"; \
	else \
		printf "$(C_ERR)cannot chown $(WRITABLE) to $(RUN_UID):$(RUN_GID)$(C_OFF)\n"; \
		printf "$(C_ERR)  composer and artisan will fail with \"bootstrap/cache must be writable\"$(C_OFF)\n"; \
		printf "$(C_ERR)  run: sudo chown -R $(RUN_UID):$(RUN_GID) $(WRITABLE)$(C_OFF)\n"; \
		exit 1; \
	fi
	@chmod -R ug+rwX $(WRITABLE) 2>/dev/null || true

.PHONY: images
images: buildx ## Build the PHP image (php, worker, scheduler share it)
	@# Only the `php` target: all three services declare the same `image:` tag,
	@# and buildx builds them in parallel, so exporting three identical images to
	@# one tag races and fails with `image ... already exists`. worker and
	@# scheduler pick the tag up once it exists.
	$(DC) build php

.PHONY: buildx
buildx: ## Ensure the docker buildx plugin exists (installs into ~/.docker/cli-plugins if not)
	@set -e; \
	if docker buildx version >/dev/null 2>&1; then exit 0; fi; \
	printf "$(C_WARN)buildx plugin missing — Compose cannot build without it, installing$(C_OFF)\n"; \
	case "$$(uname -m)" in \
		x86_64) arch=amd64;; aarch64|arm64) arch=arm64;; \
		*) printf "$(C_ERR)unsupported arch $$(uname -m) — install docker-buildx-plugin manually$(C_OFF)\n"; exit 1;; esac; \
	dir="$$HOME/.docker/cli-plugins"; mkdir -p "$$dir"; \
	base="https://github.com/docker/buildx/releases/download/$(BUILDX_VERSION)"; \
	file="buildx-$(BUILDX_VERSION).linux-$$arch"; \
	tmp="$$dir/.docker-buildx.tmp"; \
	if command -v curl >/dev/null 2>&1; then dl="curl -fsSL -o"; \
	elif command -v wget >/dev/null 2>&1; then dl="wget -qO"; \
	else printf "$(C_ERR)neither curl nor wget available$(C_OFF)\n"; exit 1; fi; \
	printf "  %s/%s\n" "$$base" "$$file"; \
	$$dl "$$tmp" "$$base/$$file"; \
	if command -v sha256sum >/dev/null 2>&1 && $$dl "$$tmp.sums" "$$base/checksums.txt" 2>/dev/null; then \
		want=$$(sed -n 's/^\([0-9a-f]*\) [ *]\{0,1\}'"$$file"'$$/\1/p' "$$tmp.sums"); \
		got=$$(sha256sum "$$tmp" | awk '{print $$1}'); \
		rm -f "$$tmp.sums"; \
		if [ "$$want" != "$$got" ]; then \
			rm -f "$$tmp"; \
			printf "$(C_ERR)checksum mismatch for %s — refusing to install$(C_OFF)\n" "$$file"; exit 1; fi; \
		printf "$(C_OK)sha256 verified$(C_OFF)\n"; \
	else \
		printf "$(C_WARN)could not verify checksum (no sha256sum or checksums.txt unreachable)$(C_OFF)\n"; \
	fi; \
	chmod +x "$$tmp"; mv "$$tmp" "$$dir/docker-buildx"; \
	printf "$(C_OK)installed: %s$(C_OFF)\n" "$$(docker buildx version)"

.PHONY: composer
composer: ## Install PHP dependencies (with dev tooling)
	$(PHP) composer install --no-interaction --prefer-dist

.PHONY: composer-prod
composer-prod: ## Install PHP dependencies (production, optimized autoloader)
	$(PHP) composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

.PHONY: key
key: ## Generate APP_KEY only if .env has none
	@grep -qE '^APP_KEY=base64:' .env || $(ARTISAN) key:generate --force

.PHONY: storage-link
storage-link: ## Link public/storage -> storage/app/public
	$(ARTISAN) storage:link || true

# ---------------------------------------------------------------------------
# Build
# ---------------------------------------------------------------------------

.PHONY: build
build: frontend cache ## Build assets + warm Laravel caches (run after every code change on prod)

.PHONY: frontend
frontend: ## Build Vite assets into public/build (Blade @vite reads the manifest)
	$(NODE) "npm ci && npm run build"
	@test -f public/build/manifest.json \
		|| { printf "$(C_ERR)public/build/manifest.json missing — build failed$(C_OFF)\n"; exit 1; }
	@# public/hot makes @vite point every asset at the Vite dev server; on a
	@# production host that is a site-wide outage, so drop the leftover.
	@test -f public/hot && { rm -f public/hot; printf "$(C_WARN)removed stale public/hot$(C_OFF)\n"; } || true

.PHONY: cache
cache: ## Rebuild config/route/view/event caches
	$(ARTISAN) optimize:clear
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache
	$(ARTISAN) event:cache

.PHONY: clear
clear: ## Drop all Laravel caches (use when config changes are ignored)
	$(ARTISAN) optimize:clear

# ---------------------------------------------------------------------------
# Deploy
# ---------------------------------------------------------------------------

.PHONY: deploy
deploy: ## Pull, install, migrate, rebuild assets + caches, restart workers (PROD)
	git pull --ff-only
	$(MAKE) --no-print-directory composer-prod
	$(MAKE) --no-print-directory migrate-force
	$(MAKE) --no-print-directory frontend
	$(MAKE) --no-print-directory cache
	$(DC) restart php worker scheduler
	@$(MAKE) --no-print-directory doctor

# ---------------------------------------------------------------------------
# Database
# ---------------------------------------------------------------------------

.PHONY: wait-db
wait-db: ## Block until PostgreSQL accepts connections
	@printf "waiting for postgres"
	@for i in $$(seq 1 60); do \
		if $(PSQL) 'pg_isready -U "$$POSTGRES_USER" -d "$$POSTGRES_DB"' >/dev/null 2>&1; then \
			printf " $(C_OK)ready$(C_OFF)\n"; exit 0; fi; \
		printf "."; sleep 2; \
	done; printf " $(C_ERR)timeout$(C_OFF)\n"; exit 1

.PHONY: migrate
migrate: ## Run pending migrations
	$(ARTISAN) migrate

.PHONY: migrate-force
migrate-force: ## Run pending migrations without the production prompt
	$(ARTISAN) migrate --force

.PHONY: migrate-status
migrate-status: ## Show which migrations have run
	$(ARTISAN) migrate:status

.PHONY: seed
seed: ## Seed everything, including demo ads and the seeded admin (DEV ONLY)
	$(ARTISAN) db:seed --force

.PHONY: seed-categories
seed-categories: ## Seed only the category tree — safe on production
	$(ARTISAN) db:seed --force --class=Database\\Seeders\\CategorySeeder

.PHONY: fresh
fresh: ## DESTRUCTIVE: drop all tables, migrate, seed demo data. Requires CONFIRM=yes
	@test "$(CONFIRM)" = "yes" \
		|| { printf "$(C_ERR)refusing: this drops every table. Re-run: make fresh CONFIRM=yes$(C_OFF)\n"; exit 1; }
	@$(MAKE) --no-print-directory db-backup
	$(ARTISAN) migrate:fresh --seed --force

.PHONY: fresh-prod
fresh-prod: ## DESTRUCTIVE: drop all tables, migrate, categories only (no demo). Requires CONFIRM=yes
	@test "$(CONFIRM)" = "yes" \
		|| { printf "$(C_ERR)refusing: this drops every table. Re-run: make fresh-prod CONFIRM=yes$(C_OFF)\n"; exit 1; }
	@$(MAKE) --no-print-directory db-backup
	$(ARTISAN) migrate:fresh --force
	@$(MAKE) --no-print-directory seed-categories
	@$(MAKE) --no-print-directory clear

.PHONY: db-backup
db-backup: ## Dump the database to storage/backups/
	@mkdir -p $(BACKUP_DIR)
	@$(PSQL) 'pg_dump -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" --clean --if-exists' \
		| gzip > $(BACKUP_DIR)/zunto-$(STAMP).sql.gz
	@printf "$(C_OK)==> $(BACKUP_DIR)/zunto-$(STAMP).sql.gz$(C_OFF)\n"
	@ls -lh $(BACKUP_DIR)/zunto-$(STAMP).sql.gz

.PHONY: db-restore
db-restore: ## Restore a dump: make db-restore FILE=storage/backups/xxx.sql.gz
	@test -n "$(FILE)" || { printf "$(C_ERR)usage: make db-restore FILE=path/to/dump.sql.gz$(C_OFF)\n"; exit 1; }
	@test -f "$(FILE)" || { printf "$(C_ERR)no such file: $(FILE)$(C_OFF)\n"; exit 1; }
	gunzip -c "$(FILE)" | $(PSQL) 'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB"'
	@$(MAKE) --no-print-directory clear

.PHONY: psql
psql: ## Open a psql shell on the database
	$(DC) exec postgres sh -c 'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB"'

# ---------------------------------------------------------------------------
# Containers
# ---------------------------------------------------------------------------

.PHONY: up
up: ## Start all services (php, nginx, postgres, redis, worker, scheduler)
	$(DC) up -d

.PHONY: down
down: ## Stop services. Never removes volumes — use `docker compose down -v` knowingly
	$(DC) down

.PHONY: restart
restart: ## Restart PHP, worker, scheduler and nginx
	$(DC) restart php worker scheduler nginx

.PHONY: ps
ps: ## Show container status
	$(DC) ps

.PHONY: logs
logs: ## Tail all container logs
	$(DC) logs -f --tail=100

.PHONY: log-app
log-app: ## Tail the Laravel log
	$(PHP) tail -f -n 100 storage/logs/laravel.log

.PHONY: sh
sh: ## Shell inside the php container
	$(DC) exec php sh

.PHONY: vite
vite: ## Start the Vite dev server (profile vite) — DEV ONLY, creates public/hot
	$(DC) --profile vite up -d node

.PHONY: vite-stop
vite-stop: ## Stop Vite and remove public/hot so built assets are served again
	$(DC) --profile vite stop node
	@rm -f public/hot
	@printf "$(C_OK)public/hot removed — Blade serves public/build again$(C_OFF)\n"

# ---------------------------------------------------------------------------
# Quality gate
# ---------------------------------------------------------------------------

.PHONY: qa
qa: qa-backend qa-frontend ## Full quality gate (backend + frontend)

.PHONY: qa-backend
qa-backend: ## composer validate, pint, phpstan, tests
	$(PHP) composer validate --strict
	$(PHP) vendor/bin/pint --test
	$(PHP) vendor/bin/phpstan analyse --level=6
	$(ARTISAN) test

.PHONY: qa-frontend
qa-frontend: ## eslint, vue-tsc, vitest, vite build
	$(NODE) "npm ci && npm run lint && npm run typecheck && npm run test:unit && npm run build"

.PHONY: test
test: ## Backend test suite only
	$(ARTISAN) test

.PHONY: e2e
e2e: ## Playwright API smoke tests (no browser needed)
	$(NODE) "npm run test:e2e"

# ---------------------------------------------------------------------------
# Diagnostics
# ---------------------------------------------------------------------------

.PHONY: doctor
doctor: ## Diagnose 500/502: containers, DB, assets, env, recent errors
	@printf "\n=== 1. containers ===\n"
	@$(DC) ps --format 'table {{.Service}}\t{{.Status}}' || true
	@printf "\n=== 2. php-fpm reachable from nginx (502 = no) ===\n"
	@if $(DC) exec -T nginx sh -c 'nc -z php 9000' >/dev/null 2>&1; then \
		printf "$(C_OK)php:9000 reachable$(C_OFF)\n"; \
	else \
		printf "$(C_ERR)php:9000 UNREACHABLE -> nginx will return 502. Check: make logs$(C_OFF)\n"; fi
	@printf "\n=== 3. HTTP ===\n"
	@port=$$($(DC) port nginx 80 2>/dev/null | sed 's/.*://'); \
	if [ -z "$$port" ]; then printf "$(C_ERR)nginx container not publishing a port$(C_OFF)\n"; else \
		for path in /up / /api/v1/categories /api/v1/ads; do \
			code=$$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$$port$$path"); \
			case "$$code" in 2*) col="$(C_OK)";; 5*) col="$(C_ERR)";; *) col="$(C_WARN)";; esac; \
			printf "  %-22s $$col%s$(C_OFF)\n" "$$path" "$$code"; \
		done; fi
	@printf "\n=== 4. database ===\n"
	@if ! $(PSQL) 'pg_isready -U "$$POSTGRES_USER" -d "$$POSTGRES_DB"' >/dev/null 2>&1; then \
		printf "$(C_ERR)postgres not accepting connections$(C_OFF)\n"; else \
		tables=$$($(PSQL) 'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" -tAc "select count(*) from pg_tables where schemaname='"'"'public'"'"'"' 2>/dev/null | tr -d "[:space:]"); \
		if [ "$$tables" -lt 5 ] 2>/dev/null; then \
			printf "$(C_ERR)$$tables tables — the schema is missing. Every API call returns 500.$(C_OFF)\n"; \
			printf "$(C_ERR)  fix: make migrate-force seed-categories   (or make db-restore FILE=...)$(C_OFF)\n"; \
		else printf "$(C_OK)$$tables tables$(C_OFF)\n"; \
		  $(PSQL) 'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" -tAc "select count(*) from ads"' 2>/dev/null \
			| xargs -I{} printf "  ads rows: %s\n" {} || true; fi; fi
	@printf "\n=== 5. redis ===\n"
	@$(DC) exec -T redis redis-cli ping 2>/dev/null || printf "$(C_ERR)redis not responding — cache/queue/session are down$(C_OFF)\n"
	@printf "\n=== 6. build artifacts ===\n"
	@test -d vendor && printf "$(C_OK)vendor present$(C_OFF)\n" \
		|| printf "$(C_ERR)vendor MISSING -> 500 on every request. fix: make composer-prod$(C_OFF)\n"
	@test -f public/build/manifest.json && printf "$(C_OK)public/build built$(C_OFF)\n" \
		|| printf "$(C_ERR)public/build/manifest.json MISSING -> Vite manifest error on every page. fix: make frontend$(C_OFF)\n"
	@test -f public/hot \
		&& printf "$(C_ERR)public/hot EXISTS -> @vite points assets at the Vite dev server. On prod that breaks every page. fix: make vite-stop$(C_OFF)\n" \
		|| printf "$(C_OK)no public/hot (built assets are served)$(C_OFF)\n"
	@test -L public/storage && printf "$(C_OK)storage symlink present$(C_OFF)\n" \
		|| printf "$(C_WARN)storage symlink missing -> photos 404. fix: make storage-link$(C_OFF)\n"
	@if $(PHP) sh -c 'test -w bootstrap/cache && test -w storage/logs && test -w storage/framework' 2>/dev/null; then \
		printf "$(C_OK)bootstrap/cache + storage writable by the container user$(C_OFF)\n"; \
	else \
		printf "$(C_ERR)bootstrap/cache or storage NOT writable by uid $(RUN_UID) -> composer/artisan fail, 500$(C_OFF)\n"; \
		printf "$(C_ERR)  fix: make permissions$(C_OFF)\n"; fi
	@printf "\n=== 7. .env sanity ===\n"
	@grep -qE '^APP_KEY=base64:' .env && printf "$(C_OK)APP_KEY set$(C_OFF)\n" \
		|| printf "$(C_ERR)APP_KEY missing -> 500. fix: make key$(C_OFF)\n"
	@env=$$(sed -n 's/^APP_ENV=//p' .env | tail -1); \
	debug=$$(sed -n 's/^APP_DEBUG=//p' .env | tail -1); \
	url=$$(sed -n 's/^APP_URL=//p' .env | tail -1); \
	proxies=$$(sed -n 's/^TRUSTED_PROXIES=//p' .env | tail -1); \
	printf "  APP_ENV=%s APP_DEBUG=%s APP_URL=%s\n" "$$env" "$$debug" "$$url"; \
	case "$$url" in http*://zunto*|https://*) \
		if [ "$$env" != "production" ]; then printf "$(C_WARN)  public URL but APP_ENV != production$(C_OFF)\n"; fi; \
		if [ "$$debug" = "true" ]; then printf "$(C_ERR)  APP_DEBUG=true on a public site — stack traces are served to visitors$(C_OFF)\n"; fi; \
		if [ -z "$$proxies" ]; then printf "$(C_WARN)  TRUSTED_PROXIES empty — rate limiting keys on the proxy IP, not the client$(C_OFF)\n"; fi;; \
	esac
	@printf "\n=== 8. last Laravel errors ===\n"
	@grep -h 'ERROR' storage/logs/laravel.log 2>/dev/null | tail -5 | cut -c1-200 \
		|| printf "no laravel.log\n"
	@printf "\n"
