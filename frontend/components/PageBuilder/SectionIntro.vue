<template>
  <div :class="align === 'left' ? 'text-left' : 'text-center mx-auto'" :style="align === 'left' ? {} : { maxWidth: '44rem' }">
    <span
      v-if="eyebrow"
      class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-widest border mb-4"
      :style="{ color: 'var(--color-primary)', borderColor: 'var(--color-primary)', borderRadius: 'var(--radius-pill, 9999px)' }"
    >
      {{ eyebrow }}
    </span>
    <component
      :is="tag"
      v-if="heading"
      class="text-3xl md:text-5xl leading-tight mb-5"
      :style="{ fontFamily: 'var(--font-heading)', fontWeight: 'var(--font-heading-weight, 700)', color: 'var(--color-text)' }"
    >
      {{ heading }}
    </component>
    <p v-if="text" class="text-base md:text-lg leading-relaxed" :style="{ color: 'var(--color-text-muted)' }">
      {{ text }}
    </p>
  </div>
</template>

<script setup lang="ts">
// Pill label, heading and intro text shared by the section blocks
withDefaults(defineProps<{
  eyebrow?: string
  heading?: string
  text?: string
  align?: 'center' | 'left'
  tag?: string
}>(), {
  align: 'center',
  tag: 'h2',
})
</script>
