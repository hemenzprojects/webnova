export interface MenuItem {
  id: number
  label: string
  type: string
  url: string | null
  icon?: string | null
  open_in_new_tab: boolean
  css_class?: string | null
  target_id?: string | null
  children?: MenuItem[]
}

export interface Menu {
  id: number
  name: string
  location: string
  items: MenuItem[]
}

export const useMenu = () => {
  const config = useRuntimeConfig()
  const apiBase = process.server ? config.apiBaseSSR : config.public.apiBase

  /**
   * Fetch all active menus
   */
  const fetchMenus = async (): Promise<Record<string, Menu>> => {
    try {
      return await $fetch(`${apiBase}/menus`)
    } catch (error) {
      console.error('Error fetching menus:', error)
      return {}
    }
  }

  /**
   * Fetch a specific menu by location
   */
  const fetchMenuByLocation = async (location: string): Promise<Menu | null> => {
    try {
      return await $fetch(`${apiBase}/menus/${location}`)
    } catch (error) {
      console.error(`Error fetching menu for location "${location}":`, error)
      return null
    }
  }

  /**
   * Reactive composable for fetching menu by location
   * Returns menu, loading state, and error
   */
  const useMenuByLocation = (location: string) => {
    const menu = ref<Menu | null>(null)
    const loading = ref(true)
    const error = ref<Error | null>(null)

    const load = async () => {
      loading.value = true
      error.value = null

      try {
        menu.value = await fetchMenuByLocation(location)
      } catch (e) {
        error.value = e as Error
        console.error(`Failed to load menu for location "${location}":`, e)
      } finally {
        loading.value = false
      }
    }

    // Load on mount
    onMounted(() => {
      load()
    })

    return {
      menu: readonly(menu),
      loading: readonly(loading),
      error: readonly(error),
      reload: load
    }
  }

  /**
   * Check if a menu item matches the current route
   */
  const isMenuItemActive = (item: MenuItem, currentPath: string): boolean => {
    if (!item.url) return false

    // Exact match
    if (item.url === currentPath) return true

    // Parent match (if item has children and current path starts with item URL)
    if (item.children && item.children.length > 0) {
      // Check if any child is active
      return hasActiveChild(item, currentPath)
    }

    return false
  }

  /**
   * Check if any descendant of this item is active
   */
  const hasActiveChild = (item: MenuItem, currentPath: string): boolean => {
    if (!item.children || item.children.length === 0) return false

    for (const child of item.children) {
      if (child.url === currentPath) return true
      if (hasActiveChild(child, currentPath)) return true
    }

    return false
  }

  return {
    fetchMenus,
    fetchMenuByLocation,
    useMenuByLocation,
    isMenuItemActive,
    hasActiveChild
  }
}
