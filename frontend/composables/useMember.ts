import type { Member } from '~/composables/useApi'

/**
 * The member portal's sign-in state (Membership plugin, Member accounts feature).
 * `enabled` is false when the site has no member portal, so the header shows no
 * "Member login" link and /account pages answer 404.
 */
export const useMember = () => {
  const api = useApi()
  const member = useState<Member | null>('member', () => null)
  const enabled = useState<boolean>('member-portal-enabled', () => false)
  const loaded = useState<boolean>('member-portal-loaded', () => false)

  const load = async (force = false) => {
    if (loaded.value && !force) return
    try {
      member.value = (await api.fetchMemberSession()).member
      enabled.value = true
    } catch {
      // 404 while the plugin or the feature is off
      member.value = null
      enabled.value = false
    }
    loaded.value = true
  }

  // Laravel only accepts changes with the XSRF token from its cookie; a first
  // visit straight to the login page has no cookie yet, and any GET sets it
  const ensureXsrf = async () => {
    if (process.client && !/(?:^|;\s*)XSRF-TOKEN=/.test(document.cookie)) {
      await api.fetchMemberSession().catch(() => null)
    }
  }

  const signedIn = (next: Member) => {
    member.value = next
    // Pages fetched while signed out (e.g. a "members only" screen) are fetched again
    clearNuxtData((key) => key.startsWith('page-'))
  }

  const login = async (email: string, password: string, remember = false) => {
    await ensureXsrf()
    signedIn((await api.memberLogin(email, password, remember)).member)
  }

  const setPassword = async (data: { token: string; email: string; password: string; password_confirmation: string }) => {
    await ensureXsrf()
    signedIn((await api.memberSetPassword(data)).member)
  }

  const forgotPassword = async (email: string) => {
    await ensureXsrf()
    return (await api.memberForgotPassword(email)).message
  }

  const changePassword = async (data: { current_password: string; password: string; password_confirmation: string }) => {
    await ensureXsrf()
    return (await api.memberChangePassword(data)).message
  }

  const logout = async () => {
    await ensureXsrf()
    await api.memberLogout().catch(() => null)
    member.value = null
    clearNuxtData((key) => key.startsWith('page-') || key.startsWith('member-'))
  }

  return { member, enabled, loaded, load, login, logout, setPassword, forgotPassword, changePassword }
}

/** The first validation message of a failed request, for showing under a form */
export const apiErrorMessage = (error: any, fallback = 'Something went wrong. Please try again.'): string => {
  const data = error?.data
  if (data?.errors) {
    const first = Object.values(data.errors as Record<string, string[]>)[0]
    if (first?.[0]) return first[0]
  }
  if (error?.statusCode === 419) return 'Your session expired. Please reload the page and try again.'
  if (error?.statusCode === 429) return 'Too many attempts. Please wait a minute and try again.'
  return data?.message || fallback
}
