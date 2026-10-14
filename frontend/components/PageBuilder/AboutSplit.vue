<template>
  <section class="py-16 md:py-24" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4 grid lg:grid-cols-2 gap-12 items-center">
      <!-- Two staggered photos -->
      <div class="grid grid-cols-2 gap-5" :class="data.imagePosition === 'right' ? 'lg:order-2' : ''">
        <img
          v-for="(image, i) in images"
          :key="i"
          :src="image"
          alt=""
          class="w-full object-cover shadow-sm"
          :class="i === 1 ? 'aspect-[3/4.4] -mt-6' : 'aspect-[3/4.4] mt-6'"
          :style="{ borderRadius: 'var(--radius-card, 12px)' }"
          loading="lazy"
        />
      </div>

      <div>
        <PageBuilderSectionIntro :eyebrow="data.eyebrow" :heading="data.heading" :text="data.text" align="left" />

        <ul v-if="data.checklist?.length" class="mt-7 space-y-2.5">
          <li v-for="(item, i) in data.checklist" :key="i" class="flex items-center gap-3 font-medium" :style="{ color: 'var(--color-text)', fontFamily: 'var(--font-heading)' }">
            <ThemeIcon name="check" class="w-5 h-5 shrink-0 text-primary" />
            {{ typeof item === 'string' ? item : item.text }}
          </li>
        </ul>

        <div v-if="data.primaryButton?.text || data.secondaryButton?.text" class="mt-8 flex flex-wrap gap-4">
          <SmartLink
            v-if="data.primaryButton?.text"
            :to="data.primaryButton.url"
            class="px-7 py-3 font-semibold text-white bg-primary hover:opacity-90 transition"
            :style="{ borderRadius: 'var(--radius-button, 8px)' }"
          >
            {{ data.primaryButton.text }}
          </SmartLink>
          <SmartLink
            v-if="data.secondaryButton?.text"
            :to="data.secondaryButton.url"
            class="px-7 py-3 font-semibold border-2 hover:opacity-80 transition"
            :style="{ borderRadius: 'var(--radius-button, 8px)', borderColor: 'var(--color-text)', color: 'var(--color-text)' }"
          >
            {{ data.secondaryButton.text }}
          </SmartLink>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    eyebrow?: string
    heading?: string
    text?: string
    images?: string[]
    imagePosition?: 'left' | 'right'
    checklist?: (string | { text: string })[]
    primaryButton?: { text: string; url: string }
    secondaryButton?: { text: string; url: string }
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()
const images = computed(() =>
  (props.data.images || []).slice(0, 2).map((img: any) => transformImageUrl(typeof img === 'string' ? img : img?.image)).filter(Boolean)
)
</script>
