export const useApi = () => {
  const config = useRuntimeConfig()
  // Use server-side URL for SSR, client-side URL for browser
  const apiBase = process.server ? config.apiBaseSSR : config.public.apiBase

  const fetchPages = async () => {
    return await $fetch(`${apiBase}/pages`)
  }

  const fetchPage = async (slug: string) => {
    return await $fetch(`${apiBase}/pages/${slug}`)
  }

  const fetchNews = async (params = {}) => {
    return await $fetch(`${apiBase}/news`, { params })
  }

  const fetchNewsItem = async (slug: string) => {
    return await $fetch(`${apiBase}/news/${slug}`)
  }

  const fetchEvents = async (params = {}) => {
    return await $fetch(`${apiBase}/events`, { params })
  }

  const fetchEvent = async (slug: string) => {
    return await $fetch(`${apiBase}/events/${slug}`)
  }

  const fetchMembers = async (params = {}) => {
    return await $fetch(`${apiBase}/members`, { params })
  }

  const fetchMember = async (slug: string) => {
    return await $fetch(`${apiBase}/members/${slug}`)
  }

  const fetchServices = async (params = {}) => {
    return await $fetch(`${apiBase}/services`, { params })
  }

  const fetchService = async (slug: string) => {
    return await $fetch(`${apiBase}/services/${slug}`)
  }

  const fetchTeamMembers = async (params = {}) => {
    return await $fetch(`${apiBase}/team-members`, { params })
  }

  const fetchTeamMember = async (slug: string) => {
    return await $fetch(`${apiBase}/team-members/${slug}`)
  }

  const fetchSettings = async () => {
    return await $fetch(`${apiBase}/settings`)
  }

  const fetchBranding = async () => {
    return await $fetch(`${apiBase}/branding`)
  }

  const fetchPageForEdit = async (id: number | string) => {
    return await $fetch(`${apiBase}/pages/${id}/edit`)
  }

  const updatePageBlocks = async (id: number | string, blocks: any[]) => {
    return await $fetch(`${apiBase}/pages/${id}/blocks`, {
      method: 'PUT',
      body: { blocks }
    })
  }

  const publishPage = async (id: number | string) => {
    return await $fetch(`${apiBase}/pages/${id}/publish`, {
      method: 'POST'
    })
  }

  const submitContactForm = async (data: any) => {
    return await $fetch(`${apiBase}/contact-form`, {
      method: 'POST',
      body: data
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
    fetchPageForEdit,
    updatePageBlocks,
    publishPage,
    submitContactForm,
  }
}