export const usePageBuilder = () => {
  const { getImageUrl } = useImageUrl()
  const { resolveWidget } = useTheme()

  // Component registry mapping block types to Vue component names
  const componentMap: Record<string, string> = {
    // Basic blocks
    text_block: 'PageBuilderTextBlock',
    heading: 'PageBuilderHeading',
    image: 'PageBuilderImage',
    button: 'PageBuilderButton',
    spacer: 'PageBuilderSpacer',
    divider: 'PageBuilderDivider',
    carousel: 'PageBuilderCarousel',

    // Section blocks
    hero: 'PageBuilderHero',
    hero_slider: 'PageBuilderHeroSlider',
    hero_split: 'PageBuilderHeroSplit',
    stats: 'PageBuilderStats',
    two_column: 'PageBuilderTwoColumn',
    card_grid: 'PageBuilderCardGrid',
    icon_cards: 'PageBuilderIconCards',
    who_we_are: 'PageBuilderWhoWeAre',
    timeline: 'PageBuilderTimeline',

    // Form blocks
    contact_form: 'PageBuilderContactForm',
    search_box: 'PageBuilderSearchBox',

    // Dynamic blocks
    dynamic_news: 'PageBuilderDynamicNews',
    dynamic_events: 'PageBuilderDynamicEvents',
    dynamic_services: 'PageBuilderDynamicServices',
    dynamic_members: 'PageBuilderDynamicMembers',
    dynamic_team_members: 'PageBuilderDynamicTeamMembers',
    dynamic_carousel: 'PageBuilderDynamicCarousel',
  }

  /**
   * Get the Vue component name for a block type
   */
  const getComponentName = (blockType: string): string => {
    const fallback = componentMap[blockType] || 'PageBuilderUnknown'
    return resolveWidget(blockType, fallback)
  }

  /**
   * Returns a theme-specific override component name for a block type,
   * or null if the active theme doesn't override it. Used by PageRenderer
   * to short-circuit the default v-if chain when a theme provides its own
   * widget for this block type.
   */
  const getThemeOverride = (blockType: string): string | null => {
    const fallback = componentMap[blockType] || ''
    const resolved = resolveWidget(blockType, fallback)
    return resolved !== fallback ? resolved : null
  }

  /**
   * Transform image path to full URL
   */
  const transformImageUrl = (path: string | null): string | null => {
    if (!path) return null
    return getImageUrl(path)
  }

  /**
   * Sort blocks by order field
   */
  const sortBlocks = (blocks: any[]) => {
    return [...blocks].sort((a, b) => {
      const orderA = a.order !== undefined ? a.order : 0
      const orderB = b.order !== undefined ? b.order : 0
      return orderA - orderB
    })
  }

  return {
    getComponentName,
    getThemeOverride,
    transformImageUrl,
    sortBlocks,
  }
}
