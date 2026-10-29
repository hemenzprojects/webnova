// A signed-in member of the member portal (Membership plugin)
export interface Member {
  name: string
  email: string
  member_number: string | null
  membership_type: string | null
  status: 'active' | 'suspended' | string
  status_label: string
  is_active: boolean
  member_since: string | null
}

export const useApi = () => {
  const config = useRuntimeConfig()
  const apiBase = process.server ? config.apiBaseSSR : config.public.apiBase

  // Forward the original Host header during SSR so tenancy can identify the tenant
  const ssrHeaders = (): Record<string, string> => {
    if (!process.server) return {}
    const event = useRequestEvent()
    const host = event?.node?.req?.headers?.host
    return host ? { Host: host, 'X-Forwarded-Host': host } : {}
  }

  // The visitor's cookies, for endpoints on their session (forwarded during SSR; the browser sends its own)
  const cookieHeaders = (): Record<string, string> => (process.server ? useRequestHeaders(['cookie']) : {})

  const fetchPages = async () => {
    return await $fetch(`${apiBase}/pages`, { headers: ssrHeaders() })
  }

  // Sends the visitor's cookies (forwarded during SSR): members-only pages need the member portal's sign-in.
  // A members-only page the visitor may not see answers 403 { restricted, reason, title }.
  const fetchPage = async (slug: string) => {
    return await $fetch(`${apiBase}/pages/${slug}`, { headers: { ...ssrHeaders(), ...cookieHeaders(), Accept: 'application/json' }, credentials: 'include' })
  }

  const fetchNews = async (params = {}) => {
    return await $fetch(`${apiBase}/news`, { params, headers: ssrHeaders() })
  }

  const fetchNewsItem = async (slug: string) => {
    return await $fetch(`${apiBase}/news/${slug}`, { headers: ssrHeaders() })
  }

  const fetchEvents = async (params = {}) => {
    return await $fetch(`${apiBase}/events`, { params, headers: ssrHeaders() })
  }

  const fetchEvent = async (slug: string) => {
    return await $fetch(`${apiBase}/events/${slug}`, { headers: ssrHeaders() })
  }

  const fetchMembers = async (params = {}) => {
    return await $fetch(`${apiBase}/members`, { params, headers: ssrHeaders() })
  }

  const fetchMember = async (slug: string) => {
    return await $fetch(`${apiBase}/members/${slug}`, { headers: ssrHeaders() })
  }

  const fetchServices = async (params = {}) => {
    return await $fetch(`${apiBase}/services`, { params, headers: ssrHeaders() })
  }

  const fetchService = async (slug: string) => {
    return await $fetch(`${apiBase}/services/${slug}`, { headers: ssrHeaders() })
  }

  const fetchTeamMembers = async (params = {}) => {
    return await $fetch(`${apiBase}/team-members`, { params, headers: ssrHeaders() })
  }

  const fetchTeamMember = async (slug: string) => {
    return await $fetch(`${apiBase}/team-members/${slug}`, { headers: ssrHeaders() })
  }

  const fetchSettings = async (params = {}) => {
    return await $fetch(`${apiBase}/settings`, { params, headers: ssrHeaders() })
  }

  const fetchBranding = async () => {
    return await $fetch(`${apiBase}/branding`, { headers: ssrHeaders() })
  }

  const fetchHeader = async () => {
    return await $fetch(`${apiBase}/header`, { headers: ssrHeaders() })
  }

  // Appearance → Custom CSS
  const fetchCustomCss = async () => {
    return await $fetch<{ css: string }>(`${apiBase}/custom-css`, { headers: ssrHeaders() })
  }

  const fetchFooter = async () => {
    return await $fetch(`${apiBase}/footer`, { headers: ssrHeaders() })
  }

  // Plugins switched on for this site
  const fetchPlugins = async (): Promise<{ active: string[] }> => {
    return await $fetch(`${apiBase}/plugins`, { headers: ssrHeaders() })
  }

  // Coming Soon plugin: whether to hide the site from this visitor. Sends the
  // visitor's cookies (forwarded during SSR) so signed-in admins see the real site.
  const fetchComingSoon = async (preview = false): Promise<Record<string, any>> => {
    const cookie = process.server ? useRequestHeaders(['cookie']) : {}
    return await $fetch(`${apiBase}/coming-soon`, {
      params: preview ? { preview: 1 } : {},
      headers: { ...ssrHeaders(), ...cookie, Accept: 'application/json' },
      credentials: 'include',
    })
  }

  // Membership plugin
  const fetchMembershipForm = async () => {
    return await $fetch(`${apiBase}/membership/form`, { headers: ssrHeaders() })
  }

  const submitMembership = async (answers: Record<string, any>) => {
    return await $fetch(`${apiBase}/membership/register`, { method: 'POST', body: { answers }, headers: { Accept: 'application/json' } })
  }

  const fetchMembershipStatus = async (reference: string) => {
    return await $fetch(`${apiBase}/membership/registrations/${encodeURIComponent(reference)}`, { headers: ssrHeaders() })
  }

  const retryMembershipPayment = async (reference: string) => {
    return await $fetch(`${apiBase}/membership/registrations/${encodeURIComponent(reference)}/pay`, { method: 'POST', headers: { Accept: 'application/json' } })
  }

  // Membership plugin, member portal (Member accounts feature). Runs on the visitor's session:
  // cookies are forwarded during SSR, and changes send the XSRF token (utils/xsrf.ts).
  const memberRequest = async <T = any>(path: string, method: 'GET' | 'POST' = 'GET', body?: Record<string, any>): Promise<T> => {
    return await $fetch<T>(`${apiBase}/membership/account${path}`, {
      method,
      body,
      headers: { ...ssrHeaders(), ...cookieHeaders(), ...xsrfHeaders(), Accept: 'application/json' },
      credentials: 'include',
    })
  }

  const fetchMemberSession = () => memberRequest<{ member: Member | null }>('')
  const memberLogin = (email: string, password: string, remember: boolean) =>
    memberRequest<{ member: Member }>('/login', 'POST', { email, password, remember })
  const memberLogout = () => memberRequest<{ member: null }>('/logout', 'POST')
  const memberForgotPassword = (email: string) => memberRequest<{ message: string }>('/forgot-password', 'POST', { email })
  const memberSetPassword = (data: { token: string; email: string; password: string; password_confirmation: string }) =>
    memberRequest<{ member: Member }>('/reset-password', 'POST', data)
  const memberChangePassword = (data: { current_password: string; password: string; password_confirmation: string }) =>
    memberRequest<{ message: string }>('/password', 'POST', data)
  const fetchMemberPages = () => memberRequest<{ pages: { title: string; slug: string; excerpt: string | null }[] }>('/pages')

  // Donations plugin
  const fetchDonationForm = async () => {
    return await $fetch(`${apiBase}/donations/form`, { headers: ssrHeaders() })
  }

  const submitDonation = async (donation: Record<string, any>) => {
    return await $fetch(`${apiBase}/donations`, { method: 'POST', body: donation, headers: { Accept: 'application/json' } })
  }

  const fetchDonationStatus = async (reference: string) => {
    return await $fetch(`${apiBase}/donations/${encodeURIComponent(reference)}`, { headers: ssrHeaders() })
  }

  const retryDonationPayment = async (reference: string) => {
    return await $fetch(`${apiBase}/donations/${encodeURIComponent(reference)}/pay`, { method: 'POST', headers: { Accept: 'application/json' } })
  }

  const fetchPageForEdit = async (id: number | string) => {
    return await $fetch(`${apiBase}/pages/${id}/edit`, { headers: { ...ssrHeaders(), ...xsrfHeaders() } })
  }

  const updatePageBlocks = async (id: number | string, blocks: any[]) => {
    return await $fetch(`${apiBase}/pages/${id}/blocks`, {
      method: 'PUT',
      body: { blocks },
      headers: { ...ssrHeaders(), ...xsrfHeaders() },
    })
  }

  const publishPage = async (id: number | string) => {
    return await $fetch(`${apiBase}/pages/${id}/publish`, {
      method: 'POST',
      headers: { ...ssrHeaders(), ...xsrfHeaders() },
    })
  }

  const submitContactForm = async (data: any) => {
    return await $fetch(`${apiBase}/contact-form`, {
      method: 'POST',
      body: data,
      headers: ssrHeaders(),
    })
  }

  return {
    fetchPages,
    fetchPage,
    fetchNews,
    fetchNewsItem,
    fetchEvents,
    fetchEvent,
    fetchMembers,
    fetchMember,
    fetchServices,
    fetchService,
    fetchTeamMembers,
    fetchTeamMember,
    fetchSettings,
    fetchBranding,
    fetchHeader,
    fetchFooter,
    fetchCustomCss,
    fetchPageForEdit,
    updatePageBlocks,
    publishPage,
    submitContactForm,
    fetchPlugins,
    fetchComingSoon,
    fetchMembershipForm,
    submitMembership,
    fetchMembershipStatus,
    retryMembershipPayment,
    fetchMemberSession,
    memberLogin,
    memberLogout,
    memberForgotPassword,
    memberSetPassword,
    memberChangePassword,
    fetchMemberPages,
    fetchDonationForm,
    submitDonation,
    fetchDonationStatus,
    retryDonationPayment,
  }
}