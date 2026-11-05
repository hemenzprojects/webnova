/**
 * Scroll reveal for the public site (styles in assets/css/motion.css):
 *
 *   v-reveal                 fade and rise when scrolled into view ("zoom", "left", "right" for others)
 *   v-reveal-stagger         the same for each child in turn (card grids)
 *
 * Only what is below the fold at load is hidden first, so nothing above it
 * flickers; after its entrance the classes are removed again so hover effects
 * work normally. Off for visitors who ask for reduced motion.
 */
export default defineNuxtPlugin((nuxtApp) => {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches

  const observer = reduced
    ? null
    : new IntersectionObserver(
        (entries) => {
          for (const entry of entries) {
            if (!entry.isIntersecting) continue
            const el = entry.target as HTMLElement
            observer!.unobserve(el)
            el.classList.add('reveal-in')
            const delay = parseInt(el.style.getPropertyValue('--reveal-delay') || '0', 10)
            window.setTimeout(() => {
              el.classList.remove('reveal-ready', 'reveal-in')
              el.style.removeProperty('--reveal-delay')
            }, 900 + delay)
          }
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
      )

  const prepare = (el: HTMLElement, kind?: string, delay = 0) => {
    if (!observer || !el.getBoundingClientRect) return
    // Already on screen when the page loads: leave it as it is
    if (el.getBoundingClientRect().top < window.innerHeight * 0.92) return
    if (kind) el.dataset.reveal = kind
    if (delay) el.style.setProperty('--reveal-delay', `${delay}ms`)
    el.classList.add('reveal-ready')
    observer.observe(el)
  }

  nuxtApp.vueApp.directive('reveal', {
    mounted(el: HTMLElement, binding) {
      if (binding.value === false) return
      prepare(el, typeof binding.value === 'string' ? binding.value : undefined)
    },
    unmounted(el: HTMLElement) {
      observer?.unobserve(el)
    },
  })

  nuxtApp.vueApp.directive('reveal-stagger', {
    mounted(el: HTMLElement, binding) {
      if (binding.value === false) return
      // One after another, with a cap so long lists do not wait too long
      Array.from(el.children).forEach((child, i) => prepare(child as HTMLElement, undefined, Math.min(i, 6) * 90))
    },
  })
})
