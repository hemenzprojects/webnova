<template>
  <!-- The look is set in the admin under Customize → Header Settings → Page title bar -->
  <section
    :class="['page-title-bar relative bg-cover bg-center', heightClass]"
    :style="{ backgroundColor: bar.bg_color || 'var(--color-primary, #0A1E3E)', backgroundImage: imageUrl ? `url('${imageUrl}')` : undefined, color: bar.text_color || '#FFFFFF' }"
  >
    <div
      v-if="imageUrl"
      class="absolute inset-0"
      :style="{ backgroundColor: bar.overlay_color, opacity: bar.overlay_opacity / 100 }"
    />
    <div :class="['relative container mx-auto px-4', bar.align === 'center' ? 'text-center' : '']">
      <NuxtLink v-if="bar.nav === 'back'" to="/" class="inline-flex items-center opacity-80 hover:opacity-100 mb-4 transition">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Home
      </NuxtLink>

      <nav v-else-if="bar.nav === 'breadcrumbs'" aria-label="Breadcrumb" class="mb-4 text-sm">
        <ol :class="['flex flex-wrap items-center gap-2', bar.align === 'center' ? 'justify-center' : '']">
          <li><NuxtLink to="/" class="opacity-80 hover:opacity-100 transition">Home</NuxtLink></li>
          <template v-for="(crumb, i) in breadcrumbs" :key="i">
            <li aria-hidden="true" class="opacity-60">›</li>
            <li :class="i === breadcrumbs.length - 1 ? 'font-semibold' : 'opacity-80'" :aria-current="i === breadcrumbs.length - 1 ? 'page' : undefined">
              {{ crumb }}
            </li>
          </template>
        </ol>
      </nav>

      <h1 class="text-4xl md:text-5xl font-bold">{{ title }}</h1>
    </div>
  </section>
</template>

<script setup lang="ts">
interface TitleBarConfig {
  bg_color: string | null
  text_color: string | null
  image: string | null
  use_featured_image: boolean
  overlay_color: string
  overlay_opacity: number
  height: 'small' | 'medium' | 'large'
  align: 'left' | 'center'
  nav: 'back' | 'breadcrumbs' | 'none'
}

const props = defineProps<{
  title: string
  // Breadcrumbs after "Home", e.g. ['News', 'Article title']
  breadcrumbs?: string[]
  // The item's own featured image, used when the admin turns that on
  image?: string | null
}>()

const defaultBar: TitleBarConfig = {
  bg_color: null, text_color: null, image: null, use_featured_image: false,
  overlay_color: '#000000', overlay_opacity: 50, height: 'medium', align: 'left', nav: 'back',
}

// Shared with TheNavigation; awaited so SSR never renders the defaults
const { data: header } = await useHeaderSettings()
const bar = computed<TitleBarConfig>(() => ({ ...defaultBar, ...(header.value?.title_bar || {}) }))

const breadcrumbs = computed(() => props.breadcrumbs?.length ? props.breadcrumbs : [props.title])

const { getImageUrl } = useImageUrl()
const imageUrl = computed(() => {
  const path = (bar.value.use_featured_image && props.image) || bar.value.image
  return path ? getImageUrl(path) : ''
})

const heightClass = computed(() => ({
  small: 'py-8',
  medium: 'py-12',
  large: 'py-20 md:py-28',
}[bar.value.height] || 'py-12'))
</script>
