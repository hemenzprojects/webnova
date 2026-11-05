<template>
  <div>
    <label v-if="!['list', 'group', 'image'].includes(field.type)" class="block text-sm font-medium text-gray-700 mb-1">{{ field.label }}</label>

    <input
      v-if="field.type === 'text'"
      type="text"
      :value="modelValue ?? ''"
      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />

    <textarea
      v-else-if="field.type === 'textarea'"
      :value="modelValue ?? ''"
      rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />

    <ElementorImageUpload
      v-else-if="field.type === 'image'"
      :model-value="modelValue || ''"
      :label="field.label"
      type="general"
      @update:model-value="emit('update:modelValue', $event)"
    />

    <select
      v-else-if="field.type === 'select' || field.type === 'icon'"
      :value="modelValue ?? ''"
      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option value="">—</option>
      <option v-for="option in selectOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>

    <!-- Group: an object with its own fields, e.g. a button's text and link -->
    <fieldset v-else-if="field.type === 'group'" class="p-3 border border-gray-200 rounded-lg space-y-3">
      <legend class="px-1 text-sm font-medium text-gray-700">{{ field.label }}</legend>
      <ElementorSchemaField
        v-for="sub in field.fields"
        :key="sub.key"
        :field="sub"
        :model-value="modelValue?.[sub.key]"
        @update:model-value="emit('update:modelValue', { ...(modelValue || {}), [sub.key]: $event })"
      />
    </fieldset>

    <!-- List: repeatable items that can be added, removed and reordered -->
    <div v-else-if="field.type === 'list'">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">{{ field.label }}</span>
        <button type="button" class="text-xs font-medium text-blue-600 hover:text-blue-800" @click="addItem">+ Add {{ (field.itemLabel || 'item').toLowerCase() }}</button>
      </div>
      <div class="space-y-3">
        <div v-for="(item, index) in items" :key="index" class="p-3 border border-gray-200 rounded-lg space-y-3 bg-gray-50">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 uppercase">{{ field.itemLabel || 'Item' }} {{ index + 1 }}</span>
            <div class="flex gap-2 text-xs">
              <button type="button" class="text-gray-500 hover:text-gray-800 disabled:opacity-30" :disabled="index === 0" @click="moveItem(index, -1)">↑</button>
              <button type="button" class="text-gray-500 hover:text-gray-800 disabled:opacity-30" :disabled="index === items.length - 1" @click="moveItem(index, 1)">↓</button>
              <button type="button" class="text-red-500 hover:text-red-700" @click="removeItem(index)">Remove</button>
            </div>
          </div>
          <ElementorSchemaField
            v-for="sub in field.fields"
            :key="sub.key"
            :field="sub"
            :model-value="field.plain ? item : item?.[sub.key]"
            @update:model-value="updateItem(index, sub.key, $event)"
          />
        </div>
      </div>
    </div>

    <p v-if="field.help" class="mt-1 text-xs text-gray-500">{{ field.help }}</p>
  </div>
</template>

<script setup lang="ts">
import type { BlockField } from '~/utils/blockSchemas'

const props = defineProps<{
  field: BlockField
  modelValue: any
}>()

const emit = defineEmits<{ 'update:modelValue': [value: any] }>()

const selectOptions = computed(() =>
  props.field.type === 'icon'
    ? THEME_ICON_NAMES.map((name) => ({ value: name, label: name.replace(/-/g, ' ') }))
    : props.field.options || []
)

const items = computed<any[]>(() => (Array.isArray(props.modelValue) ? props.modelValue : []))

const updateItem = (index: number, key: string, value: any) => {
  const next = [...items.value]
  next[index] = props.field.plain ? value : { ...(next[index] || {}), [key]: value }
  emit('update:modelValue', next)
}

const addItem = () => {
  const blank = props.field.plain ? '' : Object.fromEntries((props.field.fields || []).map((f) => [f.key, '']))
  emit('update:modelValue', [...items.value, blank])
}

const removeItem = (index: number) => {
  emit('update:modelValue', items.value.filter((_, i) => i !== index))
}

const moveItem = (index: number, direction: number) => {
  const next = [...items.value]
  const [item] = next.splice(index, 1)
  next.splice(index + direction, 0, item)
  emit('update:modelValue', next)
}
</script>
