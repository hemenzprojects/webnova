/**
 * The header settings from Customize → Header Settings (design, buttons, page title bar).
 * Shared by TheNavigation and PageTitleBar under one key, so it is fetched once per request,
 * and whichever renders first during SSR waits for it.
 */
export const useHeaderSettings = () => {
  const { fetchHeader } = useApi()

  return useAsyncData('header', async () => {
    try {
      return await fetchHeader()
    } catch (error) {
      console.error('Failed to fetch header settings:', error)
      return null
    }
  })
}
