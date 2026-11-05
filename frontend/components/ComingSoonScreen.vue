<template>
  <div
    class="relative min-h-screen flex items-center justify-center px-4 py-16 bg-primary text-white bg-cover bg-center"
    :style="backgroundUrl ? { backgroundImage: `url('${backgroundUrl}')` } : {}"
  >
    <div v-if="backgroundUrl" class="absolute inset-0 bg-primary/75" />

    <div class="relative w-full max-w-2xl text-center">
      <img
        v-if="settings.show_logo && logoUrl"
        :src="logoUrl"
        :alt="siteName"
        class="mx-auto mb-10 h-16 w-auto object-contain"
      >
      <p v-else class="mb-10 text-lg font-semibold tracking-wide uppercase opacity-80">{{ siteName }}</p>

      <p class="mb-4 text-sm font-semibold tracking-widest uppercase text-accent">
        {{ settings.mode === 'maintenance' ? 'Under maintenance' : 'Coming soon' }}
      </p>
      <h1 class="mb-6 text-4xl md:text-5xl font-bold leading-tight" style="font-family: var(--font-heading)">
        {{ settings.heading }}
      </h1>
      <p v-if="settings.message" class="mx-auto max-w-xl text-lg opacity-90 whitespace-pre-line">
        {{ settings.message }}
      </p>

      <div v-if="countdown" class="mt-10 grid grid-cols-4 gap-3 max-w-md mx-auto">
        <div v-for="part in countdown" :key="part.label" class="rounded-lg bg-white/10 py-4">
          <div class="text-3xl font-bold tabular-nums">{{ part.value }}</div>
          <div class="text-xs uppercase tracking-wider opacity-75">{{ part.label }}</div>
        </div>
      </div>

      <div v-if="hasContact" class="mt-12 border-t border-white/20 pt-8">
        <p v-if="contact.heading" class="mb-4 text-sm font-semibold tracking-widest uppercase opacity-75">{{ contact.heading }}</p>
        <div class="flex flex-col sm:flex-row flex-wrap items-center justify-center gap-x-8 gap-y-3">
          <a v-if="contact.email" :href="`mailto:${contact.email}`" class="inline-flex items-center gap-2 opacity-90 hover:opacity-100">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" class="w-5 h-5 shrink-0" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.mail" /></svg>{{ contact.email }}
          </a>
          <a v-if="contact.phone" :href="`tel:${contact.phone.replace(/\s+/g, '')}`" class="inline-flex items-center gap-2 opacity-90 hover:opacity-100">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" class="w-5 h-5 shrink-0" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.phone" /></svg>{{ contact.phone }}
          </a>
          <span v-if="contact.address" class="inline-flex items-center gap-2 opacity-90 whitespace-pre-line text-left">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" class="w-5 h-5 shrink-0" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.pin" /></svg>{{ contact.address }}
          </span>
        </div>
      </div>

      <div v-if="settings.social?.length" class="mt-8 flex flex-wrap justify-center gap-3">
        <a
          v-for="link in settings.social"
          :key="link.url"
          :href="link.url"
          :aria-label="link.label"
          :title="link.label"
          target="_blank"
          rel="noopener"
          class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 hover:bg-white/25 transition-colors"
        >
          <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true"><path :d="link.icon" /></svg>
        </a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  settings: Record<string, any>
  branding?: Record<string, any> | null
}>()

const { getImageUrl } = useImageUrl()

// Heroicons (outline)
const ICONS = {
  mail: 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
  phone: 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z',
  pin: 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
}

const contact = computed(() => props.settings.contact || {})
const hasContact = computed(() => contact.value.email || contact.value.phone || contact.value.address)

const siteName = computed(() => props.branding?.site_name || '')
// The light logo reads better on the dark background
const logoUrl = computed(() => {
  const logo = props.branding?.logo_light || props.branding?.logo
  return logo ? getImageUrl(logo) : ''
})
const backgroundUrl = computed(() => props.settings.background_image ? getImageUrl(props.settings.background_image) : '')

// Countdown to the launch date; only ticks in the browser
const now = ref<number | null>(null)
let timer: ReturnType<typeof setInterval> | undefined
onMounted(() => {
  now.value = Date.now()
  timer = setInterval(() => { now.value = Date.now() }, 1000)
})
onBeforeUnmount(() => clearInterval(timer))

const countdown = computed(() => {
  if (!props.settings.launch_at || now.value === null) return null
  const left = Math.floor((new Date(props.settings.launch_at).getTime() - now.value) / 1000)
  if (left <= 0) return null
  return [
    { label: 'Days', value: Math.floor(left / 86400) },
    { label: 'Hours', value: Math.floor(left / 3600) % 24 },
    { label: 'Minutes', value: Math.floor(left / 60) % 60 },
    { label: 'Seconds', value: left % 60 },
  ]
})
</script>
