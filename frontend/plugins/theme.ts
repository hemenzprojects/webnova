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

  const fontsUrl =
    theme.tokens?.typography?.fontsUrl ||
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'

  useHead({
    link: [
      { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
      { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
      { rel: 'stylesheet', href: fontsUrl },
    ],
    style: [{
      innerHTML: `:root { ${styleText} } body { font-family: var(--font-body); } ` +
        `h1, h2, h3, h4, h5, h6 { font-family: var(--font-heading); }`,
    }],
  })
})
