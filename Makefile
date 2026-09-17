.PHONY: help up down restart build rebuild logs shell migrate migrate-fresh migrate-rollback seed tenants-migrate tinker artisan composer npm db-shell redis-shell cache-clear route-clear config-clear view-clear clear-all queue storage-link test pint fe-dev fe-dev-clean docker-reset docker-prune docker-check db-pull db-pull-dump ssl-renew

COMPOSE = docker compose -f docker-compose.local.yml

# ── Help ──────────────────────────────────────────────────────────────────────
help:
	@echo ""
	@echo "── Containers ───────────────────────────────────────────────────────"
	@echo "  make up                  - Start all containers (detached)"
	@echo "  make down                - Stop all containers"
	@echo "  make restart             - Restart all containers"
	@echo "  make build               - Rebuild image from scratch"
	@echo "  make rebuild             - Stop, rebuild and restart"
	@echo "  make logs                - Tail all container logs"
	@echo "  make shell               - Shell into the app container"
	@echo ""
	@echo "── Database ─────────────────────────────────────────────────────────"
	@echo "  make migrate             - Run central DB migrations"
	@echo "  make migrate-fresh       - Fresh central DB (wipes data)"
	@echo "  make migrate-rollback    - Rollback last migration"
	@echo "  make seed                - Seed the central DB"
	@echo "  make tenants-migrate     - Run migrations on all tenant DBs"
	@echo "  make db-shell            - PostgreSQL shell (central DB)"
	@echo "  make redis-shell         - Redis CLI"
	@echo ""
	@echo "── Laravel ──────────────────────────────────────────────────────────"
	@echo "  make tinker              - Laravel Tinker"
	@echo "  make queue               - Start queue worker"
	@echo "  make storage-link        - Create storage symlink"
	@echo "  make cache-clear         - Clear application cache"
	@echo "  make route-clear         - Clear route cache"
	@echo "  make config-clear        - Clear config cache"
	@echo "  make view-clear          - Clear view cache"
	@echo "  make clear-all           - Clear all caches"
	@echo "  make artisan CMD='...'   - Run any artisan command"
	@echo "  make composer CMD='...'  - Run composer command"
	@echo "  make npm CMD='...'       - Run npm command"
	@echo ""
	@echo "── Frontend ─────────────────────────────────────────────────────────"
	@echo "  make fe-dev              - Start Nuxt dev server (local, no Docker)"
	@echo "  make fe-dev-clean        - Clean Nuxt cache and start dev server"
	@echo ""
	@echo "── Testing & Quality ────────────────────────────────────────────────"
	@echo "  make test                - Run PHPUnit tests"
	@echo "  make pint                - Run Laravel Pint (code style)"
	@echo ""
	@echo "── Docker Utils ─────────────────────────────────────────────────────"
	@echo "  make docker-reset        - Stop + remove all containers and volumes"
	@echo "  make docker-prune        - Full Docker system cleanup"
	@echo "  make docker-check        - Show Docker status"
	@echo ""
	@echo "── Production ───────────────────────────────────────────────────────"
	@echo "  make db-pull             - Pull production DB into local"
	@echo "  make ssl-renew           - Renew SSL on VPS"
	@echo ""

# ── Containers ────────────────────────────────────────────────────────────────
up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

restart:
	$(COMPOSE) restart

build:
	$(COMPOSE) build --no-cache

rebuild:
	$(COMPOSE) down
	$(COMPOSE) build --no-cache
	$(COMPOSE) up -d

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec app sh

# ── Database ──────────────────────────────────────────────────────────────────
migrate:
	$(COMPOSE) exec app php artisan migrate --force

migrate-fresh:
	$(COMPOSE) exec app php artisan migrate:fresh --force

migrate-rollback:
	$(COMPOSE) exec app php artisan migrate:rollback

seed:
	$(COMPOSE) exec app php artisan db:seed --force

tenants-migrate:
	$(COMPOSE) exec app php artisan tenants:migrate --force

db-shell:
	$(COMPOSE) exec postgres psql -U webnova -d webnova_central

redis-shell:
	$(COMPOSE) exec redis redis-cli

# ── Laravel ───────────────────────────────────────────────────────────────────
tinker:
	$(COMPOSE) exec app php artisan tinker

queue:
	$(COMPOSE) exec app php artisan queue:work

storage-link:
	$(COMPOSE) exec app php artisan storage:link

cache-clear:
	$(COMPOSE) exec app php artisan cache:clear

route-clear:
	$(COMPOSE) exec app php artisan route:clear

config-clear:
	$(COMPOSE) exec app php artisan config:clear

view-clear:
	$(COMPOSE) exec app php artisan view:clear

clear-all: cache-clear route-clear config-clear view-clear
	@echo "All caches cleared"

artisan:
	$(COMPOSE) exec app php artisan $(CMD)

composer:
	$(COMPOSE) exec app composer $(CMD)

npm:
	$(COMPOSE) exec app npm $(CMD)

test:
	$(COMPOSE) exec app php artisan test

pint:
	$(COMPOSE) exec app ./vendor/bin/pint

# ── Frontend (local, outside Docker) ─────────────────────────────────────────
fe-dev:
	cd frontend && npm run dev

fe-dev-clean:
	cd frontend && rm -rf .nuxt .output node_modules/.cache && npm run dev

# ── Docker Utils ──────────────────────────────────────────────────────────────
docker-reset:
	@echo "Stopping all containers..."
	docker stop $$(docker ps -aq) 2>/dev/null || true
	docker rm $$(docker ps -aq) 2>/dev/null || true
	docker volume prune -f
	@echo "Done. Run 'make build' to rebuild."

docker-prune:
	docker system prune -af --volumes
	@echo "Docker system cleaned."

docker-check:
	@docker --version
	@docker info | grep "Server Version" || echo "Docker daemon not running!"
	@echo "\nRunning containers:"
	@docker ps -a

# ── Production ────────────────────────────────────────────────────────────────
db-pull-dump:
	@echo "Downloading production database..."
	@ssh sysadmin@169.239.249.15 "docker exec webnova_mysql mysqldump -u webnova_user -p'WebnovaUserPass2024SecureDB!' webnova_db --single-transaction --quick --lock-tables=false --no-tablespaces" > backend/storage/app/production-db.sql 2>/dev/null || true
	@echo "Dump saved to backend/storage/app/production-db.sql (size: $$(du -h backend/storage/app/production-db.sql | cut -f1))"

db-pull: db-pull-dump
	@echo "Importing into local PostgreSQL..."
	$(COMPOSE) exec postgres psql -U webnova -d webnova_central < backend/storage/app/production-db.sql
	@rm backend/storage/app/production-db.sql
	@echo "Done."

ssl-renew:
	@echo "Renewing SSL certificates..."
	@sudo certbot renew --webroot -w /var/www/webnova/certbot/www --quiet
	@docker restart webnova_nginx
	@echo "SSL renewed."