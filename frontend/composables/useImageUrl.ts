/**
 * Composable for handling image URLs from the backend
 */
export const useImageUrl = () => {
  const config = useRuntimeConfig()

  /**
   * Convert a storage path to a full URL
   * @param path - The storage path (e.g., "news/featured/image.png")
   * @param fallback - Optional fallback image URL
   * @returns Full URL to the image
   */
  const getImageUrl = (path: string | null | undefined, fallback?: string): string => {
    // Return fallback if no path provided
    if (!path) {
      return fallback || ''
    }

    // If already a full URL, return as-is
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path
    }

    // Remove leading slash if present
    const cleanPath = path.startsWith('/') ? path.substring(1) : path

    // Use the public backend URL for browser-accessible images
    // Falls back to deriving from API base if not set
    const backendUrl = config.public.backendUrl || config.public.apiBase.replace('/api/v1', '')
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