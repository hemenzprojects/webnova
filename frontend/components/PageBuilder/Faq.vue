<template>
  <section class="py-16 md:py-24" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4 grid lg:grid-cols-2 gap-12 items-start">
      <img
        v-if="imageUrl"
        :src="imageUrl"
        alt=""
        class="w-full aspect-[4/3.4] object-cover"
        :style="{ borderRadius: 'var(--radius-card, 12px)' }"
        loading="lazy"
      />

      <div :class="imageUrl ? '' : 'lg:col-span-2 max-w-3xl mx-auto w-full'">
        <PageBuilderSectionIntro :eyebrow="data.eyebrow" :heading="data.heading" :text="data.text" :align="imageUrl ? 'left' : 'center'" />

        <div class="mt-8 space-y-3">
          <div
            v-for="(item, i) in data.items"
            :key="i"
            class="overflow-hidden border transition"
            :style="{
              borderRadius: 'var(--radius-button, 8px)',
              borderColor: open === i ? 'var(--color-primary)' : 'var(--color-surface-strong, #e5e7eb)',
              backgroundColor: open === i ? 'var(--color-surface-muted, #f3f4f6)' : 'var(--color-surface, #fff)',
            }"
          >
            <button
              type="button"
              class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-semibold"
              :class="open === i ? 'bg-primary text-white' : ''"
              :style="open === i ? {} : { color: 'var(--color-text)' }"
              :aria-expanded="open === i"
              @click="open = open === i ? null : i"
            >
              <span>{{ item.question }}</span>
              <svg class="w-5 h-5 shrink-0 transition-transform" :class="open === i ? 'rotate-45' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
            </button>
            <div v-show="open === i" class="px-5 py-4 leading-relaxed" :style="{ color: 'var(--color-text-muted)' }">
              {{ item.answer }}
            </div>
          </div>
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
    image?: string
    items?: { question: string; answer: string }[]
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()
const imageUrl = computed(() => transformImageUrl(props.data.image || null))

// First question starts open, as in the design
const open = ref<number | null>(0)
</script>
