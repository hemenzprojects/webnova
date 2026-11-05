<template>
  <!-- A thin reading-progress bar along the top, and a back-to-top button once the visitor has scrolled down -->
  <div class="fixed top-0 left-0 right-0 h-[3px] z-[60] pointer-events-none" aria-hidden="true">
    <div class="h-full origin-left bg-primary" :style="{ transform: `scaleX(${progress})`, transition: 'transform .1s linear' }" />
  </div>
  <Transition
    enter-active-class="transition duration-300"
    enter-from-class="opacity-0 translate-y-4"
    leave-active-class="transition duration-200"
    leave-to-class="opacity-0 translate-y-4"
  >
    <button
      v-if="showTop"
      type="button"
      class="btn-shine fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full bg-primary text-white shadow-lg flex items-center justify-center"
      aria-label="Back to top"
      @click="toTop"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
    </button>
  </Transition>
</template>

<script setup lang="ts">
const progress = ref(0)
const showTop = ref(false)

let ticking = false
const update = () => {
  if (ticking) return
  ticking = true
  requestAnimationFrame(() => {
    const max = document.documentElement.scrollHeight - window.innerHeight
    progress.value = max > 0 ? Math.min(1, window.scrollY / max) : 0
    showTop.value = window.scrollY > 600
    ticking = false
  })
}

const toTop = () => {
  const smooth = !window.matchMedia('(prefers-reduced-motion: reduce)').matches
  window.scrollTo({ top: 0, behavior: smooth ? 'smooth' : 'auto' })
}

onMounted(() => {
  window.addEventListener('scroll', update, { passive: true })
  window.addEventListener('resize', update, { passive: true })
  update()
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', update)
  window.removeEventListener('resize', update)
})
</script>
