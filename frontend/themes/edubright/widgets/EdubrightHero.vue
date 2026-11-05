<template>
  <section class="relative" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <!-- Coloured band (theme lavender unless the editor sets a background); the stats bar overlaps its lower edge -->
    <div class="relative overflow-hidden" :style="bandStyle">
      <!-- The photo zooms in slowly; with several photos they cross-fade from one to the next (the text, photo and stats stay).
           Without a photo, soft colour shapes drift behind the text. -->
      <div
        v-for="(url, i) in backgrounds"
        :key="url + i"
        class="absolute inset-0 bg-cover bg-center transition-opacity duration-[1400ms] ease-in-out"
        :class="[i === current ? 'opacity-100' : 'opacity-0', i === current ? 'ken-burns' : '']"
        :style="{ backgroundImage: `url(&quot;${url}&quot;)` }"
        aria-hidden="true"
      />
      <div v-if="backgroundUrl && overlayColor" class="absolute inset-0" :style="{ background: overlayColor }" aria-hidden="true" />
      <template v-else-if="!backgroundUrl">
        <span class="blob w-72 h-72 -top-16 -left-10" style="background: var(--color-primary)" aria-hidden="true" />
        <span class="blob w-64 h-64 bottom-0 right-1/3" style="background: var(--color-accent, var(--color-primary)); animation-delay: -6s" aria-hidden="true" />
      </template>
      <div class="relative container mx-auto px-4 pt-14 lg:pt-20" :class="stats.length ? 'pb-28 lg:pb-32' : 'pb-16 lg:pb-20'">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div class="hero-rise">
            <h1
              class="text-4xl md:text-6xl leading-[1.15] mb-6"
              :style="{ fontFamily: 'var(--font-heading)', fontWeight: 'var(--font-heading-weight)', color: hasImage ? '#ffffff' : 'var(--color-text)' }"
            >
              {{ data.heading }}
            </h1>
            <p v-if="data.subheading" class="text-lg mb-8 max-w-xl leading-relaxed" :style="{ color: hasImage ? 'rgba(255,255,255,.88)' : 'var(--color-text-muted)' }">
              {{ data.subheading }}
            </p>
            <SmartLink
              v-if="data.ctaText && data.ctaLink"
              :to="data.ctaLink"
              class="btn-theme btn-shine arrow-nudge inline-flex items-center gap-2 px-6 py-3 font-semibold"
              :style="{ borderRadius: 'var(--radius-button)' }"
            >
              {{ data.ctaText }}
              <ThemeIcon name="arrow-right" class="w-4 h-4" />
            </SmartLink>
          </div>

          <!-- Slides in slowly from the right, then the badge follows (assets/css/motion.css) -->
          <div v-if="foregroundImageUrl" class="relative max-w-md lg:ml-auto w-full hero-slide-in">
            <img
              :src="foregroundImageUrl"
              alt=""
              class="w-full aspect-[4/5] object-cover shadow-2xl"
              :style="{ borderRadius: 'var(--radius-card)' }"
            />
            <div v-if="data.stat?.value" class="hero-slide-in-late absolute top-8 -right-2 md:-right-10">
              <div
                class="float-slow px-7 py-5 text-center shadow-lg"
                :style="{ borderRadius: 'var(--radius-card)', backgroundColor: 'var(--color-surface-muted, #F4F0FE)' }"
              >
                <div class="text-4xl" :style="{ fontFamily: 'var(--font-heading)', fontWeight: 700, color: 'var(--color-text)' }"><CountUp :value="data.stat.value" /></div>
                <div class="text-xs font-semibold tracking-widest uppercase mt-1 text-primary">{{ data.stat.label }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Slideshow dots -->
      <div v-if="backgrounds.length > 1" class="absolute left-0 right-0 z-10 flex justify-center gap-2" :class="stats.length ? 'bottom-24 lg:bottom-28' : 'bottom-5'">
        <button
          v-for="(url, i) in backgrounds"
          :key="i"
          type="button"
          class="h-2 rounded-full transition-all duration-300"
          :class="i === current ? 'w-7 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"
          :aria-label="`Show photo ${i + 1}`"
          @click="show(i)"
        />
      </div>
    </div>

    <div v-if="stats.length" class="container mx-auto px-4 relative -mt-20">
      <div
        v-reveal="'zoom'"
        class="grid gap-6 py-9 px-6 text-center shadow-xl"
        :class="stats.length >= 3 ? 'grid-cols-1 sm:grid-cols-3' : 'grid-cols-1 sm:grid-cols-2'"
        :style="{ borderRadius: 'var(--radius-card)', backgroundColor: 'var(--color-surface-muted, #F4F0FE)' }"
      >
        <div v-for="(stat, i) in stats" :key="i">
          <div class="text-4xl md:text-5xl" :style="{ fontFamily: 'var(--font-heading)', fontWeight: 700, color: 'var(--color-text)' }"><CountUp :value="stat.value" /></div>
          <div class="text-xs md:text-sm font-semibold tracking-widest uppercase mt-2 text-primary">{{ stat.label }}</div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    heading: string
    subheading?: string
    foregroundImage?: string
    ctaText?: string
    ctaLink?: string
    stat?: { value: string; label: string }
    stats?: { value: string; label: string }[]
    backgroundImage?: string
    /** More background photos: the background becomes a slideshow */
    backgroundSlides?: string[]
    slideInterval?: number
    overlay?: string
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()
const foregroundImageUrl = computed(() => transformImageUrl(props.data.foregroundImage || null))

// The Hero's own Background Image (Content tab) and Overlay setting
const OVERLAYS: Record<string, string> = {
  dark: 'rgba(0,0,0,.55)',
  gradient: 'rgba(0,0,0,.35)',
  primary: 'color-mix(in srgb, var(--color-primary) 70%, transparent)',
}
// The background photos: the main one, then the slideshow's
const backgrounds = computed(() =>
  [props.data.backgroundImage, ...(props.data.backgroundSlides || [])]
    .map((path) => transformImageUrl(path || null))
    .filter((url): url is string => !!url)
)
// The first background photo (also when only slideshow photos are set)
const backgroundUrl = computed(() => backgrounds.value[0] || null)
// Text over a photo is white so it stays readable
const hasImage = computed(() => !!backgroundUrl.value)
const current = ref(0)
const show = (i: number) => {
  current.value = i
  restart()
}
let timer: ReturnType<typeof setInterval> | null = null
const restart = () => {
  if (timer) clearInterval(timer)
  timer = null
  if (backgrounds.value.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  timer = setInterval(() => {
    // Not while the tab is in the background
    if (!document.hidden) current.value = (current.value + 1) % backgrounds.value.length
  }, Math.max(3, Number(props.data.slideInterval) || 6) * 1000)
}
onMounted(restart)
onBeforeUnmount(() => timer && clearInterval(timer))
watch(backgrounds, () => {
  current.value = 0
  if (process.client) restart()
})

const bandStyle = computed(() => ({ backgroundColor: 'var(--block-bg, var(--color-surface-strong, #E8DEFB))' }))
const overlayColor = computed(() => OVERLAYS[props.data.overlay || ''] || '')
const stats = computed(() => (props.data.stats || []).filter((s) => s?.value))
</script>
