/**
 * Header for admin-only API calls (page editor, media library). They use the
 * admin's login session, so Laravel checks its CSRF token, which it keeps in
 * the XSRF-TOKEN cookie.
 */
export const xsrfHeaders = (): Record<string, string> => {
  if (typeof document === 'undefined') return {}
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]), Accept: 'application/json' } : { Accept: 'application/json' }
}
