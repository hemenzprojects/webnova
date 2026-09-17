#!/bin/bash

###############################################################################
# WEBNOVA - Fix Missing Filament Assets in Production
# Use this script if the admin panel is showing without CSS/styling
#
# Usage: bash fix-assets.sh
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
echo -e "${BLUE}    Fix Filament Assets in Production${NC}"
echo -e "${BLUE}================================================${NC}"

cd "$DEPLOY_DIR"

echo -e "\n${GREEN}[1/5] Installing Node.js in backend container...${NC}"
docker compose -f docker-compose.prod.yml exec -T backend sh -c "
  if ! command -v npm &> /dev/null; then
    apk add --no-cache nodejs npm
    echo 'Node.js and npm installed'
  else
    echo 'Node.js already installed'
  fi
"
echo -e "${GREEN}✓ Node.js ready${NC}"

echo -e "\n${GREEN}[2/5] Installing npm dependencies...${NC}"
docker compose -f docker-compose.prod.yml exec -T backend npm install
echo -e "${GREEN}✓ npm dependencies installed${NC}"

echo -e "\n${GREEN}[3/5] Building frontend assets...${NC}"
docker compose -f docker-compose.prod.yml exec -T backend npm run build
echo -e "${GREEN}✓ Assets built${NC}"

echo -e "\n${GREEN}[4/5] Publishing Filament assets...${NC}"
docker compose -f docker-compose.prod.yml exec -T backend php artisan filament:assets
echo -e "${GREEN}✓ Filament assets published${NC}"

echo -e "\n${GREEN}[5/5] Clearing and rebuilding cache...${NC}"
docker compose -f docker-compose.prod.yml exec -T backend php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec -T backend php artisan optimize
echo -e "${GREEN}✓ Cache rebuilt${NC}"

echo -e "\n${YELLOW}Restarting services...${NC}"
docker compose -f docker-compose.prod.yml restart backend nginx
echo -e "${GREEN}✓ Services restarted${NC}"

echo -e "\n${BLUE}================================================${NC}"
echo -e "${GREEN}Assets Fixed!${NC}"
echo -e "${BLUE}================================================${NC}"

echo -e "\n${GREEN}Please check:${NC}"
echo -e "  Admin Panel: ${BLUE}https://www2.webnova.edu.gh/admin${NC}"
echo -e "\n${YELLOW}The admin panel should now display with proper styling.${NC}"