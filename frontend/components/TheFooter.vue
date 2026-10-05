<template>
  <footer class="text-white py-12" :style="{ backgroundColor: 'var(--color-footer-bg, var(--color-primary, #0A1E3E))' }">
    <div class="container mx-auto px-4">
      <!-- Columns and their widgets are set in the admin under Customize → Footer Settings -->
      <div v-if="columns.length" :class="gridClass">
        <div v-for="(column, columnIndex) in columns" :key="columnIndex" class="space-y-8">
          <div v-for="(widget, index) in column.widgets" :key="index">
            <!-- Heading -->
            <h4 v-if="widget.type === 'heading'" class="text-lg font-bold -mb-4">{{ widget.text }}</h4>

            <!-- Text -->
            <div v-else-if="widget.type === 'text'" class="footer-rich-text text-gray-300" v-html="widget.html"></div>

            <!-- Menu -->
            <template v-else-if="widget.type === 'menu'">
              <h4 v-if="widget.heading" class="font-semibold mb-4">{{ widget.heading }}</h4>
              <ul class="space-y-2 text-gray-300">
                <li v-for="item in widget.items" :key="item.id">
                  <component :is="linkComponent(item)" v-bind="linkProps(item)" :class="linkClass(item)">
                    {{ item.label }}
                  </component>
                  <ul v-if="item.children && item.children.length" class="mt-2 ml-4 space-y-2">
                    <li v-for="child in item.children" :key="child.id">
                      <component :is="linkComponent(child)" v-bind="linkProps(child)" :class="linkClass(child)">
                        {{ child.label }}
                      </component>
                    </li>
                  </ul>
                </li>
              </ul>
            </template>

            <!-- Social media links -->
            <template v-else-if="widget.type === 'social' && widget.links?.length">
              <h4 v-if="widget.heading" class="font-semibold mb-4">{{ widget.heading }}</h4>
              <div class="flex flex-wrap gap-4">
                <a
                  v-for="(link, linkIndex) in widget.links"
                  :key="linkIndex"
                  :href="link.url"
                  :aria-label="link.label"
                  :title="link.label"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="w-10 h-10 rounded-full flex items-center justify-center hover:scale-110 transition"
                  :style="{ backgroundColor: 'var(--color-accent)' }"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path :d="link.icon" /></svg>
                </a>
              </div>
            </template>

            <!-- Logo (from Branding) -->
            <NuxtLink v-else-if="widget.type === 'logo' && footerLogo" to="/" class="inline-block">
              <img :src="footerLogo" :alt="siteName" class="h-12 w-auto" />
            </NuxtLink>

            <!-- Contact details (from Branding) -->
            <template v-else-if="widget.type === 'contact' && hasContact">
              <h4 v-if="widget.heading" class="font-semibold mb-4">{{ widget.heading }}</h4>
              <ul class="space-y-2 text-gray-300">
                <li v-if="branding?.contact_address" class="whitespace-pre-line">{{ branding.contact_address }}</li>
                <li v-if="branding?.contact_phone">
                  <a :href="`tel:${branding.contact_phone}`" class="hover:text-accent transition">{{ branding.contact_phone }}</a>
                </li>
                <li v-if="branding?.contact_email">
                  <a :href="`mailto:${branding.contact_email}`" class="hover:text-accent transition">{{ branding.contact_email }}</a>
                </li>
              </ul>
            </template>
          </div>
        </div>
      </div>

      <div :class="['text-center text-gray-400', columns.length ? 'border-t border-gray-700 mt-8 pt-8' : '']">
        <p>{{ copyright }}</p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import type { MenuItem } from '~/composables/useMenu'

interface FooterWidget {
  type: 'heading' | 'text' | 'menu' | 'social' | 'contact' | 'logo'
  heading?: string | null
  text?: string | null
  html?: string | null
  items?: MenuItem[]
  links?: { platform: string; label: string; icon: string; url: string }[]
}

interface FooterColumn {
  widgets: FooterWidget[]
}

const props = defineProps<{
  branding?: any
}>()

const { fetchFooter } = useApi()

const { data: footerData } = await useAsyncData('footer', async () => {
  try {
    return await fetchFooter()
  } catch (error) {
    console.error('Failed to fetch footer settings:', error)
    return { columns: null, copyright: null }
  }
})

const siteName = computed(() => props.branding?.site_name || 'WEBNOVA')

const { getImageUrl } = useImageUrl()
const footerLogo = computed(() => getImageUrl(props.branding?.logo_light || props.branding?.logo))

const hasContact = computed(() =>
  !!(props.branding?.contact_address || props.branding?.contact_phone || props.branding?.contact_email)
)

const escapeHtml = (text: string) =>
  text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

const link = (id: number, label: string, url: string): MenuItem => ({ id, label, type: 'custom', url, open_in_new_tab: false })

// Shown until the footer has been configured in the admin
const defaultColumns = computed<FooterColumn[]>(() => {
  return [
    {
      widgets: [
        { type: 'heading', text: siteName.value },
        { type: 'text', html: escapeHtml(props.branding?.site_tagline || 'Ghanaian Academic and Research Network - Connecting Education') },
      ],
    },
    {
      widgets: [{
        type: 'menu',
        heading: 'Quick Links',
        items: [link(1, 'About Us', '/about'), link(2, 'Services', '/services'), link(3, 'Members', '/members')],
      }],
    },
    {
      widgets: [{
        type: 'menu',
        heading: 'Resources',
        items: [link(4, 'News', '/news'), link(5, 'Events', '/events'), link(6, 'Contact', '/contact')],
      }],
    },
  ]
})

const columns = computed<FooterColumn[]>(() => (footerData.value as any)?.columns ?? defaultColumns.value)

const copyright = computed(() => {
  const year = String(new Date().getFullYear())
  const text = (footerData.value as any)?.copyright
  return text ? text.replaceAll('{year}', year) : `© ${year} ${siteName.value}. All rights reserved.`
})

// The row adjusts to the number of columns
const gridClass = computed(() => {
  const map: Record<number, string> = {
    1: 'grid gap-8',
    2: 'grid md:grid-cols-2 gap-8',
    3: 'grid md:grid-cols-3 gap-8',
    4: 'grid md:grid-cols-2 lg:grid-cols-4 gap-8',
    5: 'grid md:grid-cols-3 lg:grid-cols-5 gap-8',
    6: 'grid md:grid-cols-3 lg:grid-cols-6 gap-8',
  }
  return map[columns.value.length] || map[6]
})

const isExternal = (item: MenuItem) =>
  !!item.url && (item.url.startsWith('http://') || item.url.startsWith('https://') || item.open_in_new_tab)

const linkComponent = (item: MenuItem) => {
  // Category items are non-clickable headings
  if (item.type === 'category' || !item.url) return 'span'
  return isExternal(item) ? 'a' : resolveComponent('NuxtLink')
}

const linkProps = (item: MenuItem) => {
  if (item.type === 'category' || !item.url) return {}
  if (isExternal(item)) {
    return {
      href: item.url,
      target: item.open_in_new_tab ? '_blank' : '_self',
      rel: item.open_in_new_tab ? 'noopener noreferrer' : undefined,
    }
  }
  return { to: item.url }
}

const linkClass = (item: MenuItem) =>
  item.type === 'category' || !item.url ? 'font-medium text-white' : 'hover:text-accent transition'
</script>

<style>
/* Text widget content comes from the admin's rich text editor */
.footer-rich-text > * + * {
  margin-top: 0.75rem;
}
.footer-rich-text a {
  color: var(--color-accent, #00D9FF);
  text-decoration: underline;
}
.footer-rich-text ul {
  list-style: disc;
  padding-left: 1.25rem;
}
.footer-rich-text ol {
  list-style: decimal;
  padding-left: 1.25rem;
}
.footer-rich-text strong {
  color: #fff;
}
</style>
