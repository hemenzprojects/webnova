/**
 * Counts page views of the public site for the platform's Site Health
 * statistics. Sends only the page's path: no cookies, no visitor details.
 */
export default defineNuxtPlugin((nuxtApp) => {
  const config = useRuntimeConfig()
  const router = useRouter()
  let last = ''

  const track = (path: string) => {
    // Not the page editor, not automated browsers, and not the same page twice in a row
    if (path === last || path.startsWith('/elementor') || navigator.webdriver) return
    last = path

    const body = new Blob([JSON.stringify({ path })], { type: 'application/json' })
    navigator.sendBeacon?.(`${config.public.apiBase}/track`, body)
  }

  nuxtApp.hook('app:mounted', () => track(router.currentRoute.value.path))
  router.afterEach((to) => track(to.path))
})
