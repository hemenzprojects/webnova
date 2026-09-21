.PHONY: help up down restart build rebuild logs shell migrate migrate-fresh migrate-rollback seed tenants-migrate tinker artisan composer npm db-shell redis-shell cache-clear route-clear config-clear view-clear clear-all queue storage-link test pint fe-dev fe-dev-clean docker-reset docker-prune docker-check db-pull fly-deploy fly-logs fly-shell fly-migrate fly-certs-add fly-certs-show

COMPOSE    = docker compose -f docker-compose.local.yml
FLY_CONFIG = deploy/fly/fly.toml
FLY_DB_APP = cms-postgres

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
	@echo "  make db-pull             - Pull production Fly Postgres into local"
	@echo "                             Requires: export FLY_DB_URL=<url>"
	@echo "                             Get url:  fly ssh console --config $(FLY_CONFIG) -C 'echo \$$DATABASE_URL'"
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
	@echo "  make docker-reset        - Stop + remove this project's containers and volumes"
	@echo "  make docker-prune        - Full Docker system cleanup"
	@echo "  make docker-check        - Show Docker status"
	@echo ""
	@echo "── Fly.io ───────────────────────────────────────────────────────────"
	@echo "  make fly-deploy          - Deploy to Fly.io"
	@echo "  make fly-logs            - Stream Fly.io logs"
	@echo "  make fly-shell           - SSH into the Fly.io app"
	@echo "  make fly-migrate         - Run central DB migrations on Fly.io"
	@echo "  make fly-certs-add DOMAIN=school.edu.gh   - Add custom domain + SSL"
	@echo "  make fly-certs-show DOMAIN=school.edu.gh  - Check SSL cert status"
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

db-pull:
	@[ -n "$(FLY_DB_URL)" ] || (echo "Error: FLY_DB_URL not set."; echo "Get it: fly ssh console --config $(FLY_CONFIG) -C 'echo \$$DATABASE_URL'"; exit 1)
	@echo "Dumping production Fly Postgres..."
	pg_dump "$(FLY_DB_URL)" --no-owner --no-privileges \
		| $(COMPOSE) exec -T postgres psql -U webnova webnova_central
	@echo "Done."

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
	$(COMPOSE) down -v
	@echo "Done. Run 'make build' to rebuild."

docker-prune:
	docker system prune -af --volumes
	@echo "Docker system cleaned."

docker-check:
	@docker --version
	@docker info | grep "Server Version" || echo "Docker daemon not running!"
	@echo "\nRunning containers:"
	@docker ps -a

# ── Fly.io ────────────────────────────────────────────────────────────────────
fly-deploy:
	fly deploy --config $(FLY_CONFIG)

fly-logs:
	fly logs --config $(FLY_CONFIG)

fly-shell:
	fly ssh console --config $(FLY_CONFIG)

fly-migrate:
	fly ssh console --config $(FLY_CONFIG) -C "php artisan migrate --force"

fly-certs-add:
	@[ -n "$(DOMAIN)" ] || (echo "Usage: make fly-certs-add DOMAIN=school.edu.gh"; exit 1)
	fly certs add $(DOMAIN) --config $(FLY_CONFIG)

fly-certs-show:
	@[ -n "$(DOMAIN)" ] || (echo "Usage: make fly-certs-show DOMAIN=school.edu.gh"; exit 1)
	fly certs show $(DOMAIN) --config $(FLY_CONFIG)