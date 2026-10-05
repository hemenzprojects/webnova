<template>
  <label class="block">
    <span class="block text-xs text-gray-500 mb-1">{{ label }}</span>
    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-blue-500">
      <input
        type="number"
        :value="modelValue ?? ''"
        :min="0"
        :max="max"
        :placeholder="placeholder"
        class="w-full px-2 py-1.5 text-sm outline-none"
        @input="update"
      />
      <span class="px-2 text-xs text-gray-400 bg-gray-50 self-stretch flex items-center">{{ unit }}</span>
    </div>
  </label>
</template>

<script setup lang="ts">
// Number input for style settings; empty means "not set"
const props = withDefaults(defineProps<{
  modelValue?: number | null
  label: string
  unit?: string
  max?: number
  placeholder?: string
}>(), { unit: 'px', max: 400, placeholder: '' })

const emit = defineEmits<{ 'update:modelValue': [value: number | null] }>()

const update = (event: Event) => {
  const raw = (event.target as HTMLInputElement).value
  if (raw === '') {
    emit('update:modelValue', null)
    return
  }
  emit('update:modelValue', Math.min(Math.max(Number(raw), 0), props.max))
}
</script>
