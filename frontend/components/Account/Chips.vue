<template>
  <!-- Choose several from a list: each option toggles on click -->
  <div class="flex flex-wrap gap-2">
    <button
      v-for="option in options"
      :key="option"
      type="button"
      :aria-pressed="modelValue.includes(option)"
      :class="[
        'px-3 py-1.5 rounded-full border text-sm transition',
        modelValue.includes(option) ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300 hover:border-primary',
      ]"
      @click="toggle(option)"
    >
      {{ option }}
    </button>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ modelValue: string[]; options: string[] }>()
const emit = defineEmits<{ 'update:modelValue': [value: string[]] }>()

const toggle = (option: string) =>
  emit('update:modelValue', props.modelValue.includes(option) ? props.modelValue.filter((o) => o !== option) : [...props.modelValue, option])
</script>
