export const useImageUrl = () => {
  const config = useRuntimeConfig()

  // Capture backend URL at composable init time so it's available in SSR context
  let backendUrl: string
  if (process.server) {
    try {
      const event = useRequestEvent()
      const host = event?.node?.req?.headers?.host
      const proto = (event?.node?.req?.headers?.['x-forwarded-proto'] as string) || 'http'
      backendUrl = host ? `${proto}://${host}` : (config.public.backendUrl || '')
    } catch {
      backendUrl = config.public.backendUrl || ''
    }
  } else {
    backendUrl = typeof window !== 'undefined' ? window.location.origin : (config.public.backendUrl || '')
  }

  const getImageUrl = (path: string | null | undefined, fallback?: string): string => {
    if (!path) return fallback || ''

    // Rewrite old localhost URLs with the current host
    if (path.startsWith('http://') || path.startsWith('https://')) {
      try {
        const parsed = new URL(path)
        if (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1') {
          return `${backendUrl}${parsed.pathname}`
        }
      } catch {}
      return path
    }

    const cleanPath = path.startsWith('/') ? path.substring(1) : path
    return `${backendUrl}/storage/${cleanPath}`
  }

  /**
   * Get filename from a file path
   * @param path - The file path
   * @returns The filename
   */
  const getFileName = (path: string | null | undefined): string => {
    if (!path) return 'Download'
    const parts = path.split('/')
    return decodeURIComponent(parts[parts.length - 1])
  }

  /**
   * Check if a file is an image based on extension
   * @param path - The file path
   * @returns true if the file is an image
   */
  const isImage = (path: string | null | undefined): boolean => {
    if (!path) return false
    const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp']
    return imageExtensions.some(ext => path.toLowerCase().endsWith(ext))
  }

  return {
    getImageUrl,
    getFileName,
    isImage,
  }
}