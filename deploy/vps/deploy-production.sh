#!/bin/bash

# WEBNOVA Production Deployment Script
# This script deploys both backend and frontend to production

set -e  # Exit on error

echo "======================================"
echo "WEBNOVA Production Deployment"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running on production server
read -p "Are you sure you want to deploy to PRODUCTION? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo -e "${RED}Deployment cancelled.${NC}"
    exit 1
fi

echo -e "${YELLOW}[1/6] Pulling latest code from repository...${NC}"
git pull origin master

echo -e "${YELLOW}[2/6] Stopping existing containers...${NC}"
docker compose -f docker-compose.prod.yml down

echo -e "${YELLOW}[3/6] Building frontend container with production settings...${NC}"
docker compose -f docker-compose.prod.yml build --no-cache frontend

echo -e "${YELLOW}[4/6] Building backend container...${NC}"
docker compose -f docker-compose.prod.yml build --no-cache backend

echo -e "${YELLOW}[5/6] Starting all services...${NC}"
docker compose -f docker-compose.prod.yml up -d

echo -e "${YELLOW}[6/6] Running post-deployment tasks...${NC}"

# Wait for containers to be ready
echo "  - Waiting for containers to start..."
sleep 10

# Copy frontend static assets from container to filesystem
echo "  - Copying frontend static assets..."
sudo rm -rf frontend-public/_nuxt
sudo docker cp webnova_frontend:/app/.output/public/. frontend-public/
sudo chown -R $(whoami):$(whoami) frontend-public/
echo "  - Frontend assets copied successfully"

# Run Laravel migrations
echo "  - Running database migrations..."
docker compose -f docker-compose.prod.yml exec -T backend php artisan migrate --force

# Cache Laravel config
echo "  - Caching Laravel configuration..."
docker compose -f docker-compose.prod.yml exec -T backend php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T backend php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T backend php artisan view:cache

# Publish Filament assets
echo "  - Publishing Filament assets..."
docker compose -f docker-compose.prod.yml exec -T backend php artisan filament:assets || true

# Create storage link if not exists
echo "  - Creating storage symlink..."
docker compose -f docker-compose.prod.yml exec -T backend php artisan storage:link || true

# Check service status
echo ""
echo -e "${GREEN}======================================"
echo "Deployment completed successfully!"
echo "======================================${NC}"
echo ""
echo "Service Status:"
docker compose -f docker-compose.prod.yml ps

echo ""
echo -e "${GREEN}Frontend:${NC} https://www2.webnova.edu.gh"
echo -e "${GREEN}Admin Panel:${NC} https://www2.webnova.edu.gh/admin"
echo -e "${GREEN}API:${NC} https://www2.webnova.edu.gh/api/v1"
echo ""
echo "View logs with: docker compose -f docker-compose.prod.yml logs -f"
echo ""
