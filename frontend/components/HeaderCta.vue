<template>
  <component :is="linkComponent" v-bind="linkProps" :style="style">
    {{ button.text }}
  </component>
</template>

<script setup lang="ts">
// A header call-to-action button. "solid" fills with the accent colour;
// "outline" is the quieter second button (e.g. "Sign up" beside "Login").
const props = defineProps<{
  button: { text: string; url: string; new_tab: boolean }
  variant?: 'solid' | 'outline'
  accentColor?: string | null
}>()

const isExternal = computed(() => props.button.new_tab || /^(https?:|mailto:|tel:)/.test(props.button.url))
const linkComponent = computed(() => (isExternal.value ? 'a' : resolveComponent('NuxtLink')))
const linkProps = computed(() =>
  isExternal.value
    ? { href: props.button.url, target: props.button.new_tab ? '_blank' : undefined, rel: props.button.new_tab ? 'noopener noreferrer' : undefined }
    : { to: props.button.url }
)

const style = computed(() => {
  if (props.variant === 'outline') {
    return {
      border: '2px solid var(--color-accent, #00D9FF)',
      color: 'var(--color-accent, #00D9FF)',
      backgroundColor: 'transparent',
    }
  }

  // Pick light or dark text to stay readable on the accent colour
  const accent = /^#[0-9a-f]{6}$/i.test(props.accentColor || '') ? props.accentColor! : '#00D9FF'
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(accent.slice(i, i + 2), 16))
  const isLight = (r * 299 + g * 587 + b * 114) / 1000 > 150

  return {
    border: '2px solid var(--color-accent, #00D9FF)',
    backgroundColor: 'var(--color-accent, #00D9FF)',
    color: isLight ? 'var(--color-primary, #0A1E3E)' : '#ffffff',
  }
})
</script>
