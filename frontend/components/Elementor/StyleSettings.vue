<template>
  <div class="space-y-6">
    <!-- ───────────── Style tab ───────────── -->
    <template v-if="tab === 'Style'">
      <fieldset class="space-y-3">
        <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Colours</legend>
        <ElementorThemeColorField label="Headings" theme-var="--color-text" :model-value="style.colors?.heading" @update:model-value="set('colors.heading', $event)" />
        <ElementorThemeColorField label="Text" theme-var="--color-text-muted" :model-value="style.colors?.text" @update:model-value="set('colors.text', $event)" />
        <ElementorThemeColorField label="Buttons & accents" theme-var="--color-primary" :model-value="style.colors?.accent" @update:model-value="set('colors.accent', $event)" />
        <ElementorThemeColorField label="Background" theme-var="--color-surface" :model-value="style.colors?.background" @update:model-value="set('colors.background', $event)" />
        <ElementorThemeColorField label="Cards & panels" theme-var="--color-surface-muted" :model-value="style.colors?.card" @update:model-value="set('colors.card', $event)" />
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Typography</legend>
        <label class="block">
          <span class="block text-xs font-medium text-gray-600 mb-1">Heading font</span>
          <select :value="style.fonts?.heading || ''" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" @change="set('fonts.heading', value($event))">
            <option value="">Theme default</option>
            <option v-for="font in BLOCK_FONTS" :key="font.value" :value="font.value">{{ font.label }}</option>
          </select>
        </label>
        <label class="block">
          <span class="block text-xs font-medium text-gray-600 mb-1">Body font</span>
          <select :value="style.fonts?.body || ''" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" @change="set('fonts.body', value($event))">
            <option value="">Theme default</option>
            <option v-for="font in BLOCK_FONTS" :key="font.value" :value="font.value">{{ font.label }}</option>
          </select>
        </label>
        <div>
          <span class="block text-xs font-medium text-gray-600 mb-1">Text alignment</span>
          <div class="grid grid-cols-4 gap-1">
            <button
              v-for="option in alignOptions"
              :key="option.value"
              type="button"
              :class="['px-2 py-1.5 text-xs border rounded', (style.align || '') === option.value ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50']"
              @click="set('align', option.value)"
            >
              {{ option.label }}
            </button>
          </div>
        </div>
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Corners</legend>
        <ElementorStyleNumber label="Cards" unit="px" :max="48" :model-value="style.radius?.card" placeholder="Theme" @update:model-value="set('radius.card', $event)" />
        <ElementorStyleNumber label="Buttons" unit="px" :max="48" :model-value="style.radius?.button" placeholder="Theme" @update:model-value="set('radius.button', $event)" />
      </fieldset>

      <p class="text-xs text-gray-500">Leave a setting on "Theme default" to follow the site theme. Blocks built for themes follow every option; older blocks follow most of them.</p>
    </template>

    <!-- ───────────── Advanced tab ───────────── -->
    <template v-else-if="tab === 'Advanced'">
      <fieldset>
        <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Spacing</legend>
        <p class="text-xs font-medium text-gray-600 mb-1">Margin (outside)</p>
        <div class="grid grid-cols-2 gap-2 mb-3">
          <ElementorStyleNumber label="Top" unit="px" :model-value="style.spacing?.margin?.top" @update:model-value="set('spacing.margin.top', $event)" />
          <ElementorStyleNumber label="Bottom" unit="px" :model-value="style.spacing?.margin?.bottom" @update:model-value="set('spacing.margin.bottom', $event)" />
        </div>
        <p class="text-xs font-medium text-gray-600 mb-1">Padding (inside)</p>
        <div class="grid grid-cols-2 gap-2">
          <ElementorStyleNumber v-for="side in sides" :key="side" :label="capitalize(side)" unit="px" :model-value="(style.spacing?.padding as any)?.[side]" @update:model-value="set(`spacing.padding.${side}`, $event)" />
        </div>
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Background</legend>
        <ElementorThemeColorField label="Colour" theme-var="--color-surface" :model-value="style.background?.color" @update:model-value="set('background.color', $event)" />
        <ElementorImageUpload :model-value="style.background?.image || ''" label="Image" type="general" @update:model-value="set('background.image', $event)" />
        <ElementorStyleNumber v-if="style.background?.image" label="Darken image" unit="%" :max="90" :model-value="style.background?.overlay" @update:model-value="set('background.overlay', $event)" />
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Border & shadow</legend>
        <div class="grid grid-cols-2 gap-2">
          <ElementorStyleNumber label="Border width" unit="px" :max="20" :model-value="style.border?.width" @update:model-value="set('border.width', $event)" />
          <ElementorStyleNumber label="Rounded corners" unit="px" :max="64" :model-value="style.border?.radius" @update:model-value="set('border.radius', $event)" />
        </div>
        <ElementorThemeColorField v-if="style.border?.width" label="Border colour" theme-var="--color-surface-strong" :model-value="style.border?.color" @update:model-value="set('border.color', $event)" />
        <label class="block">
          <span class="block text-xs font-medium text-gray-600 mb-1">Shadow</span>
          <select :value="style.shadow || ''" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" @change="set('shadow', value($event))">
            <option value="">None</option>
            <option value="sm">Subtle</option>
            <option value="md">Medium</option>
            <option value="lg">Large</option>
            <option value="xl">Extra large</option>
          </select>
        </label>
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Layout</legend>
        <label class="block">
          <span class="block text-xs font-medium text-gray-600 mb-1">Maximum width</span>
          <select :value="style.maxWidth || ''" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" @change="set('maxWidth', value($event))">
            <option value="">Full width</option>
            <option value="xl">Extra large (1280px)</option>
            <option value="lg">Large (1024px)</option>
            <option value="md">Medium (768px)</option>
            <option value="sm">Narrow (640px)</option>
          </select>
        </label>
        <div>
          <span class="block text-xs font-medium text-gray-600 mb-1">Hide on</span>
          <div class="flex gap-4">
            <label v-for="device in devices" :key="device" class="inline-flex items-center gap-1.5 text-sm text-gray-700">
              <input type="checkbox" :checked="!!(style.hide as any)?.[device]" @change="set(`hide.${device}`, ($event.target as HTMLInputElement).checked)" />
              {{ capitalize(device) }}
            </label>
          </div>
        </div>
      </fieldset>

      <fieldset class="space-y-3">
        <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Other</legend>
        <label class="block">
          <span class="block text-xs font-medium text-gray-600 mb-1">Anchor ID</span>
          <input
            type="text"
            :value="style.anchorId || ''"
            placeholder="e.g. faq"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            @input="set('anchorId', ($event.target as HTMLInputElement).value.replace(/[^a-zA-Z0-9_-]/g, ''))"
          />
          <span class="block mt-1 text-xs text-gray-500">Link to this block with #{{ style.anchorId || 'faq' }}, e.g. from a menu.</span>
        </label>
        <label class="block">
          <span class="block text-xs font-medium text-gray-600 mb-1">CSS class</span>
          <input type="text" :value="style.cssClass || ''" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono" @input="set('cssClass', ($event.target as HTMLInputElement).value)" />
        </label>
      </fieldset>

      <button type="button" class="w-full px-3 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50" @click="emit('update', {})">
        Clear all style settings for this widget
      </button>
    </template>
  </div>
</template>

<script setup lang="ts">
// The Style and Advanced tabs every widget gets. Edits data._style.
import type { BlockStyle } from '~/utils/blockStyle'

const props = defineProps<{
  tab: 'Style' | 'Advanced'
  modelValue?: BlockStyle
}>()

const emit = defineEmits<{ update: [style: BlockStyle] }>()

const style = computed<BlockStyle>(() => props.modelValue || {})

const alignOptions = [
  { value: '', label: 'Theme' },
  { value: 'left', label: 'Left' },
  { value: 'center', label: 'Centre' },
  { value: 'right', label: 'Right' },
]
const sides = ['top', 'right', 'bottom', 'left'] as const
const devices = ['mobile', 'tablet', 'desktop'] as const

const capitalize = (text: string) => text.charAt(0).toUpperCase() + text.slice(1)
const value = (event: Event) => (event.target as HTMLSelectElement).value

/** Set a nested value ("spacing.padding.top") and emit a fresh copy */
const set = (path: string, val: any) => {
  const next: any = JSON.parse(JSON.stringify(style.value))
  const keys = path.split('.')
  let node = next
  for (const key of keys.slice(0, -1)) {
    node[key] = node[key] && typeof node[key] === 'object' ? node[key] : {}
    node = node[key]
  }
  const last = keys[keys.length - 1]
  if (val === '' || val === null || val === undefined || val === false) {
    delete node[last]
  } else {
    node[last] = val
  }
  emit('update', next)
}
</script>
