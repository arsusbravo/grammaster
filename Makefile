COMPOSE = docker compose
PHP     = $(COMPOSE) exec php
ARTISAN = $(PHP) php artisan

.PHONY: up down build restart logs shell node-shell migrate fresh seed test lint composer artisan npm dev preview

up:
	$(COMPOSE) up -d --build
	@$(PHP) sh -c '[ -f .env ] || cp .env.example .env'
	@$(PHP) sh -c '[ -d vendor ] || composer install --no-interaction'
	@$(PHP) sh -c 'grep -q "^APP_KEY=base64" .env || php artisan key:generate --force'
	@echo ""
	@echo "  Student app  http://localhost:$${NGINX_PORT:-8088}/student"
	@echo "  Admin        http://localhost:$${NGINX_PORT:-8088}/admin"
	@echo "  Mailpit      http://localhost:$${MAILPIT_UI_PORT:-8025}"
	@echo ""

# The app as a student's phone gets it: production build with the service worker (offline, install).
preview:
	APP_MODE=preview $(COMPOSE) up -d --force-recreate node
	@echo "  Production build of the app at http://localhost:$${NGINX_PORT:-8088}/student (ready in about a minute; 'make dev' goes back)"

# Back to the dev server with hot reload.
dev:
	$(COMPOSE) up -d --force-recreate node

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build

restart: down up

logs:
	$(COMPOSE) logs -f --tail=100

shell:
	$(COMPOSE) exec php bash

node-shell:
	$(COMPOSE) exec node sh

migrate:
	$(ARTISAN) migrate --force

fresh:
	$(ARTISAN) migrate:fresh --seed --force

seed:
	$(ARTISAN) db:seed --force

test:
	$(PHP) php artisan test
	$(COMPOSE) exec node npm run test

lint:
	$(PHP) ./vendor/bin/pint --test
	$(COMPOSE) exec node npm run lint

# Usage: make composer c="require foo/bar"
composer:
	$(PHP) composer $(c)

# Usage: make artisan c="make:model Foo"
artisan:
	$(ARTISAN) $(c)

# Usage: make npm c="install foo"
npm:
	$(COMPOSE) exec node npm $(c)
