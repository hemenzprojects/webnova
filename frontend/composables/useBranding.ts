/**
 * Composable for managing site branding and theme
 */
export const useBranding = () => {
  const { fetchBranding } = useApi()
  const { getImageUrl } = useImageUrl()

  /**
   * Fetch branding settings from the API
   */
  const getBranding = async () => {
    try {
      const branding = await fetchBranding()
      return branding
    } catch (error) {
      console.error('Failed to fetch branding:', error)
      return null
    }
  }

  /**
   * Branding's colours as CSS custom properties. They override the theme's
   * tokens; empty fields leave the theme's colour in place.
   */
  const colorVars = (branding: any): Record<string, string> => {
    if (!branding) return {}
    const map: Record<string, string> = {
      '--color-primary': branding.primary_color,
      '--color-primary-light': branding.primary_light,
      '--color-primary-dark': branding.primary_dark,
      '--color-accent': branding.accent_color,
      '--color-accent-light': branding.accent_light,
      '--color-accent-dark': branding.accent_dark,
      '--color-text': branding.text_color,
      '--color-background': branding.background_color,
    }
    // Only colour-shaped values: they are written into a <style> tag
    return Object.fromEntries(Object.entries(map).filter(([, v]) => typeof v === 'string' && /^[#\w(),.%\s-]+$/.test(v)))
  }

  /**
   * Get logo URL
   */
  const getLogoUrl = (branding: any, light: boolean = false) => {
    if (!branding) return ''
    const logoPath = light ? branding.logo_light : branding.logo
    return logoPath ? getImageUrl(logoPath) : ''
  }

  /**
   * Get favicon URL
   */
  const getFaviconUrl = (branding: any) => {
    if (!branding || !branding.favicon) return ''
    return getImageUrl(branding.favicon)
  }

  /**
   * Update page favicon
   */
  const updateFavicon = (branding: any) => {
    if (!branding || !branding.favicon || !process.client) return

    const favicon = document.querySelector('link[rel="icon"]') as HTMLLinkElement
    if (favicon) {
      favicon.href = getFaviconUrl(branding)
    } else {
      const newFavicon = document.createElement('link')
      newFavicon.rel = 'icon'
      newFavicon.href = getFaviconUrl(branding)
      document.head.appendChild(newFavicon)
    }
  }

  return {
    getBranding,
    colorVars,
    getLogoUrl,
    getFaviconUrl,
    updateFavicon,
  }
}