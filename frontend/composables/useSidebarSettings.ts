/**
 * Composable for the detail-page sidebars (services, news, events).
 * Settings are managed in the admin under "Sidebar Settings".
 */
export type SidebarSection = 'services' | 'news' | 'events'

export interface SidebarConfig {
  enabled: boolean
  title: string
  limit: number
  showImage: boolean
  showDate: boolean
  order: string
  position: 'left' | 'right'
}

// Used when nothing has been saved in the admin yet (keep in sync with
// backend/app/Filament/Pages/SidebarSettings.php)
const defaults: Record<SidebarSection, SidebarConfig> = {
  services: { enabled: true, title: 'Other Services', limit: 5, showImage: true, showDate: false, order: 'order', position: 'right' },
  news: { enabled: true, title: 'Other News', limit: 5, showImage: true, showDate: true, order: 'latest', position: 'right' },
  events: { enabled: true, title: 'Other Events', limit: 5, showImage: true, showDate: true, order: 'upcoming', position: 'right' },
}

export const useSidebarSettings = () => {
  const { fetchSettings } = useApi()

  /**
   * Get the sidebar configuration for a section, falling back to defaults
   */
  const getSidebarConfig = async (section: SidebarSection): Promise<SidebarConfig> => {
    const fallback = defaults[section]

    let settings: Record<string, any> = {}
    try {
      settings = (await fetchSettings({ group: 'sidebar' })) as Record<string, any>
    } catch (error) {
      console.error('Failed to fetch sidebar settings:', error)
      return fallback
    }

    const prefix = `sidebar_${section}_`

    return {
      enabled: settings[`${prefix}enabled`] ?? fallback.enabled,
      title: settings[`${prefix}title`] || fallback.title,
      limit: Number(settings[`${prefix}limit`]) || fallback.limit,
      showImage: settings[`${prefix}show_image`] ?? fallback.showImage,
      showDate: settings[`${prefix}show_date`] ?? fallback.showDate,
      order: settings[`${prefix}order`] || fallback.order,
      position: settings.sidebar_position === 'left' ? 'left' : 'right',
    }
  }

  return {
    getSidebarConfig,
  }
}
