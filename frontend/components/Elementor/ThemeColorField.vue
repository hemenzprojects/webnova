<template>
  <div>
    <div class="flex items-center justify-between mb-1">
      <span class="text-xs font-medium text-gray-600">{{ label }}</span>
      <button v-if="modelValue" type="button" class="text-xs text-blue-600 hover:text-blue-800" @click="emit('update:modelValue', '')">
        Reset to theme
      </button>
    </div>

    <ElementorColorPicker v-if="modelValue" :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" />

    <!-- Unset: the widget uses the theme's colour -->
    <button
      v-else
      type="button"
      class="w-full flex items-center gap-2 px-3 py-2 border border-dashed border-gray-300 rounded-lg text-left hover:border-gray-400 transition-colors"
      @click="customise"
    >
      <span class="w-8 h-8 rounded border border-gray-300" :style="{ backgroundColor: `var(${themeVar}, #e5e7eb)` }" />
      <span class="flex-1 text-sm text-gray-500">Theme default</span>
      <span class="text-xs font-medium text-blue-600">Change</span>
    </button>
  </div>
</template>

<script setup lang="ts">
// Colour setting that stays "theme default" until the editor picks one
const props = defineProps<{
  modelValue?: string
  label: string
  /** Theme variable it overrides, e.g. --color-primary */
  themeVar: string
}>()

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

// Start from the colour the theme currently uses
const customise = () => {
  const current = getComputedStyle(document.documentElement).getPropertyValue(props.themeVar).trim()
  emit('update:modelValue', /^#[0-9a-f]{6}$/i.test(current) ? current : '#3B82F6')
}
</script>
