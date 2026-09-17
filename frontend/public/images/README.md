# Images Directory

## Adding a Hero Background Image

To add a custom hero background image:

1. **Add your image file** to this directory:
   - Recommended name: `hero-bg.jpg` or `hero-bg.png`
   - Recommended size: 1920x1080px or larger
   - Format: JPG, PNG, or WebP

2. **Update the HeroSection component** at `components/HeroSection.vue`:
   ```vue
   <!-- Uncomment this line and add your image -->
   <img src="/images/hero-bg.jpg" class="w-full h-full object-cover opacity-20" />
   ```

3. **Adjust opacity** as needed (currently set to 20% to keep text readable)

## Other Images

You can add other images here as well:
- Logo: `logo.png`
- Service icons: `service-*.svg`
- Team photos: `team/`
- News thumbnails: `news/`

All images in the `/public` directory are accessible at the root URL.
For example: `/public/images/logo.png` → `/images/logo.png`