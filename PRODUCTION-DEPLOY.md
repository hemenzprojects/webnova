# WEBNOVA Production Deployment Guide

## What Was Fixed

The frontend wasn't rendering in production because the `NUXT_PUBLIC_BACKEND_URL` environment variable was missing. This caused image URLs to be generated incorrectly.

### Changes Made:

1. **Updated `frontend/Dockerfile.prod`**: Added `NUXT_PUBLIC_BACKEND_URL` build argument
2. **Updated `docker-compose.prod.yml`**: Added backend URL environment variables
3. **Created `deploy-production.sh`**: Automated deployment script

## How to Deploy to Production

### On the Production Server:

```bash
# 1. SSH into your production server
ssh your-server

# 2. Navigate to project directory
cd /path/to/webnova

# 3. Pull latest code
git pull origin main

# 4. Run deployment script
./deploy-production.sh
```

### Manual Deployment (if needed):

```bash
# Stop containers
docker-compose -f docker-compose.prod.yml down

# Rebuild with new environment variables
docker-compose -f docker-compose.prod.yml build --no-cache frontend
docker-compose -f docker-compose.prod.yml build --no-cache backend

# Start services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations and cache
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec backend php artisan config:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan filament:assets
docker-compose -f docker-compose.prod.yml exec backend php artisan storage:link
```

## Verify Deployment

1. **Check containers are running:**
   ```bash
   docker-compose -f docker-compose.prod.yml ps
   ```

2. **Check frontend logs:**
   ```bash
   docker-compose -f docker-compose.prod.yml logs frontend
   ```

3. **Check backend logs:**
   ```bash
   docker-compose -f docker-compose.prod.yml logs backend
   ```

4. **Test the website:**
   - Homepage: https://www2.webnova.edu.gh
   - Admin: https://www2.webnova.edu.gh/admin
   - API: https://www2.webnova.edu.gh/api/v1

## Troubleshooting

### Frontend shows "Cannot GET /"

**Problem:** Frontend container not running or crashed

**Solution:**
```bash
docker-compose -f docker-compose.prod.yml logs frontend
docker-compose -f docker-compose.prod.yml restart frontend
```

### Images not loading

**Problem:** Storage symlink missing or incorrect permissions

**Solution:**
```bash
docker-compose -f docker-compose.prod.yml exec backend php artisan storage:link
docker-compose -f docker-compose.prod.yml exec backend chmod -R 775 storage
```

### Admin panel missing CSS

**Problem:** Filament assets not published

**Solution:**
```bash
docker-compose -f docker-compose.prod.yml exec backend php artisan filament:assets
docker-compose -f docker-compose.prod.yml exec backend php artisan view:cache
```

### Vue.js not rendering (blank page with raw HTML)

**Problem:** Frontend build failed or environment variables missing

**Solution:**
```bash
# Rebuild frontend with verbose output
docker-compose -f docker-compose.prod.yml build --no-cache --progress=plain frontend

# Check build logs for errors
docker-compose -f docker-compose.prod.yml logs frontend | grep -i error
```

## Environment Variables

### Required for Production:

#### Backend (.env in backend folder):
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www2.webnova.edu.gh
FILESYSTEM_DISK=public
```

#### Frontend (docker-compose.prod.yml):
```yaml
environment:
  - NUXT_PUBLIC_API_BASE=http://www2.webnova.edu.gh/api/v1
  - NUXT_PUBLIC_BACKEND_URL=https://www2.webnova.edu.gh
```

## Quick Commands

```bash
# View all logs
docker-compose -f docker-compose.prod.yml logs -f

# Restart a service
docker-compose -f docker-compose.prod.yml restart frontend
docker-compose -f docker-compose.prod.yml restart backend

# Rebuild a service
docker-compose -f docker-compose.prod.yml up -d --build frontend

# Check service status
docker-compose -f docker-compose.prod.yml ps

# Execute commands in container
docker-compose -f docker-compose.prod.yml exec backend php artisan [command]
docker-compose -f docker-compose.prod.yml exec frontend npm [command]
```

## Post-Deployment Checklist

- [ ] Homepage loads with proper styling
- [ ] Images display correctly
- [ ] Admin panel (/admin) is accessible and styled
- [ ] API endpoints respond correctly
- [ ] News articles display with images
- [ ] Branding settings work (logo, colors)
- [ ] File uploads work in admin
- [ ] No console errors in browser

## Support

If issues persist:
1. Check logs: `docker-compose -f docker-compose.prod.yml logs -f`
2. Verify all containers are running: `docker-compose -f docker-compose.prod.yml ps`
3. Check nginx configuration: `docker-compose -f docker-compose.prod.yml exec nginx nginx -t`
4. Review environment variables in containers