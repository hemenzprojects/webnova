#!/bin/bash

###############################################################################
# WEBNOVA Production Deployment Script
# Builds and deploys the WEBNOVA application
#
# Usage: bash 4-deploy.sh
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

DEPLOY_DIR="/var/www/webnova"

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}    WEBNOVA Production Deployment${NC}"
echo -e "${BLUE}================================================${NC}"

cd "$DEPLOY_DIR"

# Pre-flight checks
echo -e "\n${YELLOW}Running pre-flight checks...${NC}"

if [ ! -f ".env.production" ]; then
    echo -e "${RED}Error: .env.production not found!${NC}"
    echo -e "Run: ${YELLOW}bash 2-configure-env.sh${NC} first"
    exit 1
fi

if [ ! -f "backend/.env" ]; then
    echo -e "${RED}Error: backend/.env not found!${NC}"
    echo -e "Run: ${YELLOW}bash 2-configure-env.sh${NC} first"
    exit 1
fi

echo -e "${GREEN}✓ Environment files present${NC}"

# Load environment variables
set -a
source .env.production
set +a

# Build and deploy
echo -e "\n${GREEN}[1/6] Building Docker containers...${NC}"
echo -e "${YELLOW}This may take several minutes...${NC}"
docker compose -f docker-compose.prod.yml build --no-cache
echo -e "${GREEN}✓ Containers built${NC}"

echo -e "\n${GREEN}[2/6] Stopping old containers...${NC}"
docker compose -f docker-compose.prod.yml down || true
echo -e "${GREEN}✓ Old containers stopped${NC}"

echo -e "\n${GREEN}[3/6] Starting new containers...${NC}"
docker compose -f docker-compose.prod.yml up -d
echo -e "${GREEN}✓ Containers started${NC}"

echo -e "\n${YELLOW}Waiting for services to be ready...${NC}"
sleep 15

echo -e "\n${GREEN}[4/6] Configuring Laravel...${NC}"

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" backend/.env 2>/dev/null; then
    echo "Generating Laravel application key..."
    docker compose -f docker-compose.prod.yml exec -T backend php artisan key:generate --force
    echo -e "${GREEN}✓ App key generated${NC}"
else
    echo -e "${GREEN}✓ App key already set${NC}"
fi

# Run database migrations
echo "Running database migrations..."
docker compose -f docker-compose.prod.yml exec -T backend php artisan migrate --force
echo -e "${GREEN}✓ Migrations complete${NC}"

# Create storage symbolic link
echo "Creating storage link..."
docker compose -f docker-compose.prod.yml exec -T backend php artisan storage:link || true
echo -e "${GREEN}✓ Storage link created${NC}"

# Publish Filament assets
echo "Publishing Filament assets..."
docker compose -f docker-compose.prod.yml exec -T backend php artisan filament:assets || true
echo -e "${GREEN}✓ Filament assets published${NC}"

# Optimize Laravel
echo "Optimizing Laravel..."
docker compose -f docker-compose.prod.yml exec -T backend php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T backend php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T backend php artisan view:cache
docker compose -f docker-compose.prod.yml exec -T backend php artisan optimize
echo -e "${GREEN}✓ Laravel optimized${NC}"

echo -e "\n${GREEN}[5/6] Creating admin user...${NC}"
echo -e "${YELLOW}Please enter admin user details:${NC}"
docker compose -f docker-compose.prod.yml exec backend php artisan make:filament-user

echo -e "\n${GREEN}[6/6] Verifying deployment...${NC}"
docker compose -f docker-compose.prod.yml ps

echo -e "\n${BLUE}================================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${BLUE}================================================${NC}"

echo -e "\n${GREEN}Your application is now running at:${NC}"
echo -e "  Frontend: ${BLUE}https://www2.webnova.edu.gh${NC}"
echo -e "  Admin Panel: ${BLUE}https://www2.webnova.edu.gh/admin${NC}"
echo -e "  API: ${BLUE}https://www2.webnova.edu.gh/api/v1${NC}"

echo -e "\n${YELLOW}Useful commands:${NC}"
echo -e "  View logs: ${BLUE}docker compose -f docker-compose.prod.yml logs -f${NC}"
echo -e "  Restart: ${BLUE}docker compose -f docker-compose.prod.yml restart${NC}"
echo -e "  Stop: ${BLUE}docker compose -f docker-compose.prod.yml down${NC}"
echo -e "  Status: ${BLUE}docker compose -f docker-compose.prod.yml ps${NC}"