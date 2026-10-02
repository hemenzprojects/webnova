/**
 * Loads the active tenant theme at app boot and injects its design
 * tokens as CSS custom properties on the document root. Runs on both
 * server and client so SSR HTML already carries the correct colors.
 */
export default defineNuxtPlugin(async (nuxtApp) => {
  const { load, cssVars } = useTheme()
  const theme = await load()
  if (!theme) return

  const vars = cssVars(theme)
  const styleText = Object.entries(vars)
    .map(([k, v]) => `${k}: ${v};`)
    .join(' ')

  useHead({
    link: [
      { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
      { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
      {
        rel: 'stylesheet',
        href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap',
      },
    ],
    style: [{ innerHTML: `:root { ${styleText} } body { font-family: var(--font-body); }` }],
  })
})