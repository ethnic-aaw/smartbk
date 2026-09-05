DC := docker compose
APP_URL := http://localhost:9000

.PHONY: help install up down restart rebuild logs ps sh db-reset lint test orbit-install orbit-dev orbit-build orbit-build-dash clean

help: ## tampilkan bantuan
	@grep -E '^[a-zA-Z0-9_.-]+:.*##' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*##"} {printf "  \033[36m%-18s\033[0m %s\n",$$1,$$2}'

install: ## setup awal: .env + composer + orbit deps
	@test -f .env || (cp .env.example .env && echo "created .env — edit DB_PASS/GOOGLE_*")
	composer install
	$(MAKE) orbit-install

orbit-install: ## npm install di orbit/
	cd orbit && npm install

up: ## docker up (port 9000)
	$(DC) up -d --build
	@echo "→ $(APP_URL)"

down: ## docker down
	$(DC) down

restart: ## restart app
	$(DC) restart app

rebuild: ## reset DB volume & re-import sql/smart_bk.sql
	$(DC) down -v
	$(DC) up -d --build

logs: ## tail app logs
	$(DC) logs -f app

ps: ## status container
	$(DC) ps

sh: ## shell ke container app
	$(DC) exec app bash

db-reset: rebuild ## alias rebuild

lint: ## php -l semua file non-vendor
	@find . -name "*.php" -not -path "./vendor/*" -not -path "./orbit/*" -print0 | xargs -0 -n1 php -l 1>/dev/null && echo "lint OK"

test: ## phpunit (butuh vendor)
	./vendor/bin/phpunit --colors=always

orbit-dev: ## vite dev (orbit/)
	cd orbit && npm run dev

orbit-build: ## vite build (orbit/)
	cd orbit && npm run build

orbit-build-dash: ## build dashboard.js untuk dashboard.php (DASH=1)
	cd orbit && npm run build:dash

clean: ## hapus build orbit
	rm -rf orbit/dist orbit/dist-dash
