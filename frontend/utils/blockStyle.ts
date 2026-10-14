/**
 * Style options every page-builder widget shares (editor: Style and Advanced
 * tabs). Stored on the widget as data._style and applied by
 * PageBuilder/BlockWrapper.vue. Colours, fonts and corner radius override the
 * theme's CSS variables for that widget only, so theme-aware blocks follow them.
 */
export interface BlockStyle {
  colors?: {
    heading?: string
    text?: string
    accent?: string
    background?: string
    card?: string
  }
  fonts?: { heading?: string; body?: string }
  align?: '' | 'left' | 'center' | 'right'
  radius?: { card?: number | null; button?: number | null }
  spacing?: {
    margin?: { top?: number | null; bottom?: number | null }
    padding?: { top?: number | null; right?: number | null; bottom?: number | null; left?: number | null }
  }
  background?: { color?: string; image?: string; overlay?: number | null }
  border?: { width?: number | null; color?: string; radius?: number | null }
  shadow?: '' | 'sm' | 'md' | 'lg' | 'xl'
  maxWidth?: '' | 'sm' | 'md' | 'lg' | 'xl' | 'full'
  hide?: { mobile?: boolean; tablet?: boolean; desktop?: boolean }
  anchorId?: string
  cssClass?: string
}

/** Fonts offered in the editor; loaded from Google Fonts when used */
export const BLOCK_FONTS: { value: string; label: string; family: string }[] = [
  { value: 'Inter', label: 'Inter', family: 'Inter' },
  { value: 'Poppins', label: 'Poppins', family: 'Poppins' },
  { value: 'Montserrat', label: 'Montserrat', family: 'Montserrat' },
  { value: 'Lato', label: 'Lato', family: 'Lato' },
  { value: 'Roboto', label: 'Roboto', family: 'Roboto' },
  { value: 'Open Sans', label: 'Open Sans', family: 'Open+Sans' },
  { value: 'Nunito', label: 'Nunito', family: 'Nunito' },
  { value: 'Raleway', label: 'Raleway', family: 'Raleway' },
  { value: 'Playfair Display', label: 'Playfair Display (serif)', family: 'Playfair+Display' },
  { value: 'Merriweather', label: 'Merriweather (serif)', family: 'Merriweather' },
  { value: 'Lora', label: 'Lora (serif)', family: 'Lora' },
]

const SHADOWS: Record<string, string> = {
  sm: '0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06)',
  md: '0 4px 12px rgba(0,0,0,.08)',
  lg: '0 12px 28px rgba(0,0,0,.12)',
  xl: '0 24px 48px rgba(0,0,0,.16)',
}

const MAX_WIDTHS: Record<string, string> = {
  sm: '640px',
  md: '768px',
  lg: '1024px',
  xl: '1280px',
}

const px = (value: number | null | undefined) => (value === null || value === undefined || Number.isNaN(Number(value)) ? undefined : `${Number(value)}px`)

const fontStack = (name?: string) => (name ? `"${name}", system-ui, sans-serif` : undefined)

/**
 * Inline style, classes and font links for a widget's wrapper.
 * `forEditor` keeps hidden-on-device widgets visible while editing.
 */
export const blockStyleToCss = (style: BlockStyle | undefined, forEditor = false) => {
  const s = style || {}
  const css: Record<string, string> = {}
  const classes: string[] = []
  const set = (key: string, value: string | undefined) => {
    if (value) css[key] = value
  }

  // Theme variables, overridden for this widget only
  set('--color-text', s.colors?.heading)
  set('--color-text-muted', s.colors?.text)
  set('--color-primary', s.colors?.accent)
  set('--color-accent', s.colors?.accent)
  set('--color-surface', s.colors?.background)
  // Blocks with their own coloured band (e.g. a hero) paint it with --block-bg
  set('--block-bg', s.colors?.background || s.background?.color)
  set('--color-surface-muted', s.colors?.card)
  set('--font-heading', fontStack(s.fonts?.heading))
  set('--font-body', fontStack(s.fonts?.body))
  set('--radius-card', px(s.radius?.card))
  set('--radius-button', px(s.radius?.button))

  // Plain properties, for blocks that don't use the theme variables
  set('color', s.colors?.text)
  set('font-family', fontStack(s.fonts?.body))
  if (s.align) css['text-align'] = s.align

  set('margin-top', px(s.spacing?.margin?.top))
  set('margin-bottom', px(s.spacing?.margin?.bottom))
  set('padding-top', px(s.spacing?.padding?.top))
  set('padding-right', px(s.spacing?.padding?.right))
  set('padding-bottom', px(s.spacing?.padding?.bottom))
  set('padding-left', px(s.spacing?.padding?.left))

  set('background-color', s.background?.color)
  if (s.background?.image) {
    const overlay = Math.min(Math.max(Number(s.background.overlay) || 0, 0), 90) / 100
    css['background-image'] = overlay
      ? `linear-gradient(rgba(0,0,0,${overlay}), rgba(0,0,0,${overlay})), url("${s.background.image}")`
      : `url("${s.background.image}")`
    css['background-size'] = 'cover'
    css['background-position'] = 'center'
  }

  if (s.border?.width) {
    css.border = `${Number(s.border.width)}px solid ${s.border.color || 'rgba(0,0,0,.12)'}`
  }
  set('border-radius', px(s.border?.radius))
  if (s.border?.radius) css.overflow = 'hidden'
  set('box-shadow', s.shadow ? SHADOWS[s.shadow] : undefined)

  if (s.maxWidth && MAX_WIDTHS[s.maxWidth]) {
    css['max-width'] = MAX_WIDTHS[s.maxWidth]
    css['margin-left'] = 'auto'
    css['margin-right'] = 'auto'
  }

  if (!forEditor) {
    // Literal class names so Tailwind keeps them
    if (s.hide?.mobile) classes.push('max-md:hidden')
    if (s.hide?.tablet) classes.push('md:max-lg:hidden')
    if (s.hide?.desktop) classes.push('lg:hidden')
  }
  if (s.cssClass) classes.push(...s.cssClass.split(/\s+/).filter(Boolean))

  const fonts = [s.fonts?.heading, s.fonts?.body]
    .map((name) => BLOCK_FONTS.find((f) => f.value === name))
    .filter(Boolean)
    .map((f) => `https://fonts.googleapis.com/css2?family=${f!.family}:wght@400;500;600;700&display=swap`)

  return { style: css, classes, fonts: [...new Set(fonts)] }
}
