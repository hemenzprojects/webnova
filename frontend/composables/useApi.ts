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

  const fetchPages = async () => {
    return await $fetch(`${apiBase}/pages`, { headers: ssrHeaders() })
  }

  const fetchPage = async (slug: string) => {
    return await $fetch(`${apiBase}/pages/${slug}`, { headers: ssrHeaders() })
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

  const fetchFooter = async () => {
    return await $fetch(`${apiBase}/footer`, { headers: ssrHeaders() })
  }

  // Plugins switched on for this site
  const fetchPlugins = async (): Promise<{ active: string[] }> => {
    return await $fetch(`${apiBase}/plugins`, { headers: ssrHeaders() })
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
    fetchPageForEdit,
    updatePageBlocks,
    publishPage,
    submitContactForm,
    fetchPlugins,
    fetchMembershipForm,
    submitMembership,
    fetchMembershipStatus,
    retryMembershipPayment,
  }
}