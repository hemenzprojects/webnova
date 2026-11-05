<template>
  <section class="py-12 md:py-16" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4">
      <div
        class="group relative grid md:grid-cols-2 items-center gap-8 overflow-hidden"
        :style="{ backgroundColor: 'var(--color-surface-muted, #f3f4f6)', borderRadius: 'var(--radius-card, 12px)' }"
      >
        <!-- Soft colour shapes drift behind the text -->
        <span class="blob w-64 h-64 -top-20 -right-16" style="background: var(--color-primary)" aria-hidden="true" />
        <span class="blob w-56 h-56 -bottom-24 left-1/3" style="background: var(--color-accent, var(--color-primary)); animation-delay: -7s" aria-hidden="true" />
        <div v-if="imageUrl" class="hover-zoom h-full">
          <img :src="imageUrl" alt="" class="w-full h-full max-h-[380px] object-cover" loading="lazy" />
        </div>
        <div class="relative px-8 pb-10 md:p-12" :class="imageUrl ? '' : 'md:col-span-2 text-center'">
          <h2 class="text-3xl md:text-5xl leading-tight mb-5" :style="{ fontFamily: 'var(--font-heading)', fontWeight: 'var(--font-heading-weight, 700)', color: 'var(--color-text)' }">
            {{ data.heading }}
          </h2>
          <p v-if="data.text" class="text-lg leading-relaxed mb-7" :style="{ color: 'var(--color-text-muted)' }">{{ data.text }}</p>
          <SmartLink
            v-if="data.buttonText"
            :to="data.buttonUrl"
            class="btn-theme btn-shine arrow-nudge inline-flex items-center gap-2 px-6 py-3 font-semibold"
            :style="{ borderRadius: 'var(--radius-button, 8px)' }"
          >
            {{ data.buttonText }}
            <ThemeIcon name="arrow-right" class="w-4 h-4" />
          </SmartLink>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    heading?: string
    text?: string
    buttonText?: string
    buttonUrl?: string
    image?: string
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()
const imageUrl = computed(() => transformImageUrl(props.data.image || null))
</script>
