// Member portal pages (definePageMeta({ middleware: 'member' })): signed-in members only.
// 404 while the site has no member portal; otherwise visitors go to the login page and come back after.
export default defineNuxtRouteMiddleware(async (to) => {
  const { member, enabled, load } = useMember()
  await load()

  if (!enabled.value) {
    return abortNavigation(createError({ statusCode: 404, statusMessage: 'Page not found', fatal: true }))
  }
  if (!member.value) {
    return navigateTo({ path: '/account/login', query: { redirect: to.fullPath } })
  }
})
