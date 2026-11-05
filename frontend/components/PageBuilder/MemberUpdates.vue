<template>
  <!-- Member updates (Membership plugin): publications, milestones and news members submitted, published by the editorial team -->
  <section
    class="py-16 md:py-24"
    :style="{ backgroundColor: data.background === 'tinted' ? 'var(--block-bg, var(--color-surface-muted, #f3f4f6))' : 'var(--block-bg, var(--color-surface, #fff))' }"
  >
    <div class="container mx-auto px-4">
      <PageBuilderSectionIntro :eyebrow="data.eyebrow" :heading="data.heading" :text="data.text" />

      <div v-if="showFilters && kinds.length" class="flex flex-wrap justify-center gap-2 mt-8">
        <button
          v-for="option in [{ value: '', label: 'All' }, ...kinds]"
          :key="option.value"
          type="button"
          class="px-4 py-1.5 text-sm border transition"
          :style="{
            borderRadius: 'var(--radius-button, 999px)',
            borderColor: 'var(--color-primary)',
            backgroundColor: kind === option.value ? 'var(--color-primary)' : 'transparent',
            color: kind === option.value ? '#fff' : 'var(--color-primary)',
          }"
          @click="choose(option.value)"
        >
          {{ option.label }}
        </button>
      </div>

      <p v-if="!pending && !items.length" class="text-center mt-10" :style="{ color: 'var(--color-text-muted)' }">
        No updates yet.
      </p>

      <div v-else v-reveal-stagger class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 mt-10">
        <article
          v-for="item in items"
          :key="item.id"
          class="group hover-lift flex flex-col overflow-hidden border"
          :style="{ borderRadius: 'var(--radius-card, 12px)', borderColor: 'var(--color-border, #e5e7eb)', backgroundColor: 'var(--color-surface, #fff)' }"
        >
          <component :is="item.link ? 'a' : 'div'" v-bind="item.link ? linkAttrs(item.link) : {}" class="hover-zoom block aspect-[16/10]" :style="{ backgroundColor: 'var(--color-surface-muted, #f3f4f6)' }">
            <img v-if="item.image" :src="getImageUrl(item.image)" :alt="item.title" class="w-full h-full object-cover" loading="lazy">
            <div v-else class="w-full h-full flex items-center justify-center">
              <ThemeIcon :name="item.kind === 'publication' ? 'book-open' : item.kind === 'milestone' ? 'trophy' : 'light-bulb'" class="w-12 h-12 text-primary opacity-60" />
            </div>
          </component>
          <div class="flex flex-col flex-1 p-5">
            <p class="text-xs uppercase tracking-wider font-semibold mb-2 text-primary">
              {{ item.kind_label }}<span v-if="item.is_featured"> · Featured</span>
            </p>
            <h3 class="text-lg leading-snug mb-2" :style="{ fontFamily: 'var(--font-heading)', fontWeight: 600, color: 'var(--color-text)' }">
              <a v-if="item.link" v-bind="linkAttrs(item.link)" class="hover:underline">{{ item.title }}</a>
              <template v-else>{{ item.title }}</template>
            </h3>
            <p v-if="item.summary" class="text-sm leading-relaxed mb-4 line-clamp-4" :style="{ color: 'var(--color-text-muted)' }">{{ item.summary }}</p>
            <div class="mt-auto pt-3 flex items-end justify-between gap-3 text-sm border-t" :style="{ borderColor: 'var(--color-border, #e5e7eb)' }">
              <div class="min-w-0">
                <p v-if="item.author" class="font-medium truncate" :style="{ color: 'var(--color-text)' }">{{ item.author }}</p>
                <p class="text-xs truncate" :style="{ color: 'var(--color-text-muted)' }">
                  {{ [item.author_detail, formatDate(item.published_at)].filter(Boolean).join(' · ') }}
                </p>
              </div>
              <a v-if="item.link" v-bind="linkAttrs(item.link)" class="arrow-nudge shrink-0 inline-flex items-center gap-1 font-medium text-primary">Read more <ThemeIcon name="arrow-right" class="w-4 h-4" /></a>
            </div>
          </div>
        </article>
      </div>

      <div v-if="page < lastPage" class="text-center mt-10">
        <button
          type="button"
          :disabled="loadingMore"
          class="px-6 py-2.5 border disabled:opacity-60"
          :style="{ borderRadius: 'var(--radius-button, 8px)', borderColor: 'var(--color-primary)', color: 'var(--color-primary)' }"
          @click="more"
        >
          {{ loadingMore ? 'Loading…' : 'Show more' }}
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { MemberUpdate } from '~/composables/useApi'

const props = defineProps<{
  data: {
    eyebrow?: string
    heading?: string
    text?: string
    kind?: string
    limit?: string | number
    featuredFirst?: 'yes' | 'no'
    showFilters?: 'yes' | 'no'
    background?: 'plain' | 'tinted'
  }
  blockId: string
}>()

const { fetchMemberUpdates } = useApi()
const { getImageUrl } = useImageUrl()

const limit = computed(() => Number(props.data.limit) || 6)
// A block set to one type has no type buttons
const showFilters = computed(() => props.data.showFilters !== 'no' && !props.data.kind)
const kind = ref(props.data.kind || '')

const load = (page = 1) =>
  fetchMemberUpdates({ kind: kind.value || undefined, limit: limit.value, page, featured_first: props.data.featuredFirst !== 'no' }).catch(() => null)

const { data: first, pending, refresh } = await useAsyncData(`member-updates-${props.blockId}`, () => load(1))

const extra = ref<MemberUpdate[]>([])
const page = ref(1)
const lastPage = computed(() => (page.value > 1 ? lastSeen.value : first.value?.last_page ?? 1))
const lastSeen = ref(1)
const items = computed(() => [...(first.value?.items ?? []), ...extra.value])
const kinds = computed(() => Object.entries(first.value?.kinds ?? {}).map(([value, label]) => ({ value, label })))

const loadingMore = ref(false)
const more = async () => {
  loadingMore.value = true
  const next = await load(page.value + 1)
  if (next) {
    extra.value.push(...next.items)
    page.value = next.page
    lastSeen.value = next.last_page
  }
  loadingMore.value = false
}

const choose = async (value: string) => {
  kind.value = value
  extra.value = []
  page.value = 1
  await refresh()
}

// Links lead to where the publication is hosted: a new tab, and no credit passed on to unknown sites
const linkAttrs = (href: string) => ({ href, target: '_blank', rel: 'noopener noreferrer nofollow ugc' })
</script>
