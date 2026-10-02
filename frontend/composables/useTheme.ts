import edubrightManifest from '~/themes/edubright/manifest.json'
import defaultManifest from '~/themes/default/manifest.json'

interface ThemeTokens {
  colors: Record<string, string>
  typography: Record<string, string>
  radius: Record<string, string>
}

interface ActiveTheme {
  slug: string
  name: string
  tokens: ThemeTokens
}

const manifests: Record<string, { widgets: Record<string, string> }> = {
  default: defaultManifest as any,
  edubright: edubrightManifest as any,
}

const activeTheme = () => useState<ActiveTheme | null>('active-theme', () => null)

export const useTheme = () => {
  const theme = activeTheme()
  const config = useRuntimeConfig()
  const apiBase = process.server ? config.apiBaseSSR : config.public.apiBase

  const ssrHeaders = (): Record<string, string> => {
    if (!process.server) return {}
    const event = useRequestEvent()
    const host = event?.node?.req?.headers?.host
    return host ? { Host: host } : {}
  }

  const load = async () => {
    if (theme.value) return theme.value
    try {
      const res: any = await $fetch(`${apiBase}/theme`, { headers: ssrHeaders() })
      theme.value = res.active
      return theme.value
    } catch (e) {
      console.error('Failed to load theme', e)
      return null
    }
  }

  const cssVars = (t: ActiveTheme | null): Record<string, string> => {
    if (!t?.tokens) return {}
    const vars: Record<string, string> = {}
    for (const [key, val] of Object.entries(t.tokens.colors || {})) {
      vars[`--color-${kebab(key)}`] = val
    }
    vars['--font-heading'] = t.tokens.typography?.headingFont || ''
    vars['--font-body'] = t.tokens.typography?.bodyFont || ''
    vars['--font-heading-weight'] = t.tokens.typography?.headingWeight || '700'
    vars['--radius-card'] = t.tokens.radius?.card || '12px'
    vars['--radius-button'] = t.tokens.radius?.button || '8px'
    vars['--radius-pill'] = t.tokens.radius?.pill || '9999px'
    return vars
  }

  /**
   * Resolve a widget component name for a given block type.
   * If the active theme registers an override for the type, return it;
   * otherwise return the default PageBuilder component name.
   */
  const resolveWidget = (blockType: string, fallback: string): string => {
    const slug = theme.value?.slug || 'default'
    const override = manifests[slug]?.widgets?.[blockType]
    return override || fallback
  }

  return { theme, load, cssVars, resolveWidget }
}

const kebab = (s: string) => s.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase()