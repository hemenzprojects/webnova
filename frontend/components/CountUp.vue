<template>
  <span ref="el">{{ shown }}</span>
</template>

<script setup lang="ts">
// A figure such as "1,200+" or "98%" that counts up from 0 when it comes into view.
// The server renders the final value; text without a number is shown as it is.
const props = withDefaults(defineProps<{ value: string | number; duration?: number }>(), { duration: 1600 })

const el = ref<HTMLElement | null>(null)
const text = computed(() => String(props.value ?? ''))
const parts = computed(() => {
  const match = text.value.match(/^(\D*?)(\d[\d,]*(?:\.\d+)?)(.*)$/)
  if (!match) return null
  const [, before, number, after] = match
  return { before, after, target: parseFloat(number.replace(/,/g, '')), commas: number.includes(','), decimals: (number.split('.')[1] || '').length }
})
const shown = ref(text.value)

const format = (n: number) => {
  const p = parts.value!
  const fixed = n.toFixed(p.decimals)
  const [whole, fraction] = fixed.split('.')
  const grouped = p.commas ? whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : whole
  return `${p.before}${grouped}${fraction ? `.${fraction}` : ''}${p.after}`
}

onMounted(() => {
  const p = parts.value
  if (!p || !el.value || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  const run = () => {
    const start = performance.now()
    const step = (now: number) => {
      const t = Math.min(1, (now - start) / props.duration)
      shown.value = format(p.target * (1 - Math.pow(1 - t, 3)))
      if (t < 1) requestAnimationFrame(step)
      else shown.value = text.value
    }
    requestAnimationFrame(step)
  }
  const observer = new IntersectionObserver((entries) => {
    if (entries.some((e) => e.isIntersecting)) {
      observer.disconnect()
      run()
    }
  }, { threshold: 0.4 })
  observer.observe(el.value)
})

watch(text, (value) => { shown.value = value })
</script>
