// Member login, forgot password and set password: 404 without a member portal.
// Signed-in members skip the login page.
export default defineNuxtRouteMiddleware(async (to) => {
  const { member, enabled, load } = useMember()
  await load()

  if (!enabled.value) {
    return abortNavigation(createError({ statusCode: 404, statusMessage: 'Page not found', fatal: true }))
  }
  if (member.value && to.path === '/account/login') {
    return navigateTo(safeRedirect(to.query.redirect))
  }
})
