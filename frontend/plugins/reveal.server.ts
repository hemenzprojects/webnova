// The server renders everything visible; the directives only do something in the browser (reveal.client.ts)
export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.directive('reveal', {})
  nuxtApp.vueApp.directive('reveal-stagger', {})
})
