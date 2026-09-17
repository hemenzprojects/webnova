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
   * Apply color theme to the document
   */
  const applyColorTheme = (branding: any) => {
    if (!branding || !process.client) return

    const root = document.documentElement

    // Apply CSS custom properties
    root.style.setProperty('--color-primary', branding.primary_color || '#0A1E3E')
    root.style.setProperty('--color-primary-light', branding.primary_light || '#1A3A5C')
    root.style.setProperty('--color-primary-dark', branding.primary_dark || '#050F1F')
    root.style.setProperty('--color-accent', branding.accent_color || '#00D9FF')
    root.style.setProperty('--color-accent-light', branding.accent_light || '#33E3FF')
    root.style.setProperty('--color-accent-dark', branding.accent_dark || '#00A8CC')
    root.style.setProperty('--color-text', branding.text_color || '#1F2937')
    root.style.setProperty('--color-background', branding.background_color || '#FFFFFF')
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
    applyColorTheme,
    getLogoUrl,
    getFaviconUrl,
    updateFavicon,
  }
}