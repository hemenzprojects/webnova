/** A "?redirect=" target that stays on this site: a path, never another host */
export const safeRedirect = (value: unknown, fallback = '/account'): string => {
  const path = Array.isArray(value) ? value[0] : value
  return typeof path === 'string' && path.startsWith('/') && !path.startsWith('//') && !path.startsWith('/\\') ? path : fallback
}
