<template>
  <div class="relative" ref="pickerContainer">
    <label v-if="label" class="block text-xs font-medium text-gray-600 mb-1">{{ label }}</label>

    <!-- Color Preview Button -->
    <button
      type="button"
      @click.stop="togglePicker"
      class="w-full flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg hover:border-gray-400 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
      <div
        class="w-8 h-8 rounded border border-gray-300"
        :style="{ backgroundColor: modelValue || '#3B82F6' }"
      ></div>
      <input
        type="text"
        :value="modelValue || '#3B82F6'"
        @input="handleTextInput"
        @click.stop
        class="flex-1 outline-none text-sm font-mono uppercase"
        placeholder="#3B82F6"
      />
    </button>

    <!-- Color Picker Dropdown -->
    <div
      v-if="showPicker"
      @click.stop
      class="absolute z-50 mt-2 p-3 bg-white rounded-lg shadow-xl border border-gray-200"
      style="min-width: 250px;"
    >
      <!-- Gradient Selector -->
      <div class="relative w-full h-40 mb-3 rounded cursor-crosshair" @mousedown="startGradientDrag">
        <div
          class="absolute inset-0 rounded"
          :style="{
            background: `linear-gradient(to top, #000, transparent), linear-gradient(to right, #fff, ${currentHue})`
          }"
        ></div>
        <!-- Selector Circle -->
        <div
          class="absolute w-4 h-4 border-2 border-white rounded-full shadow-lg pointer-events-none"
          :style="{
            left: `${saturation}%`,
            top: `${100 - brightness}%`,
            transform: 'translate(-50%, -50%)'
          }"
        ></div>
      </div>

      <!-- Hue Slider -->
      <div class="relative w-full h-6 mb-3 rounded cursor-pointer" @mousedown="startHueDrag">
        <div
          class="absolute inset-0 rounded"
          style="background: linear-gradient(to right, #ff0000, #ffff00, #00ff00, #00ffff, #0000ff, #ff00ff, #ff0000)"
        ></div>
        <!-- Hue Selector Circle -->
        <div
          class="absolute w-6 h-6 border-2 border-white rounded-full shadow-lg pointer-events-none"
          :style="{
            left: `${(hue / 360) * 100}%`,
            transform: 'translateX(-50%)'
          }"
        ></div>
      </div>

      <!-- Preset Colors -->
      <div class="grid grid-cols-8 gap-1.5 mb-3">
        <button
          v-for="preset in presetColors"
          :key="preset"
          type="button"
          @click="selectPreset(preset)"
          class="w-6 h-6 rounded border border-gray-300 hover:scale-110 transition-transform"
          :style="{ backgroundColor: preset }"
        ></button>
      </div>

      <!-- Hex Input -->
      <div class="flex items-center gap-2">
        <span class="text-xs font-medium text-gray-600">HEX:</span>
        <input
          type="text"
          :value="modelValue || '#3B82F6'"
          @input="handleTextInput"
          class="flex-1 px-2 py-1 text-xs font-mono uppercase border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="#3B82F6"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: string
  label?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const showPicker = ref(false)
const hue = ref(220)
const saturation = ref(75)
const brightness = ref(75)
const pickerContainer = ref<HTMLElement | null>(null)

const presetColors = [
  '#3B82F6', '#8B5CF6', '#EC4899', '#EF4444',
  '#F59E0B', '#10B981', '#14B8A6', '#6366F1',
  '#0EA5E9', '#06B6D4', '#84CC16', '#A855F7',
  '#F97316', '#22C55E', '#0891B2', '#6D28D9'
]

const togglePicker = () => {
  showPicker.value = !showPicker.value
}

const currentHue = computed(() => {
  return `hsl(${hue.value}, 100%, 50%)`
})

// Initialize from props
watch(() => props.modelValue, (newVal) => {
  if (newVal && newVal.startsWith('#')) {
    hexToHSB(newVal)
  }
}, { immediate: true })

function hexToHSB(hex: string) {
  // Convert hex to RGB
  const r = parseInt(hex.slice(1, 3), 16) / 255
  const g = parseInt(hex.slice(3, 5), 16) / 255
  const b = parseInt(hex.slice(5, 7), 16) / 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const delta = max - min

  // Calculate hue
  let h = 0
  if (delta !== 0) {
    if (max === r) {
      h = ((g - b) / delta) % 6
    } else if (max === g) {
      h = (b - r) / delta + 2
    } else {
      h = (r - g) / delta + 4
    }
    h = Math.round(h * 60)
    if (h < 0) h += 360
  }

  // Calculate saturation and brightness
  const s = max === 0 ? 0 : (delta / max) * 100
  const v = max * 100

  hue.value = h
  saturation.value = s
  brightness.value = v
}

function hsbToHex(h: number, s: number, b: number): string {
  s = s / 100
  b = b / 100

  const k = (n: number) => (n + h / 60) % 6
  const f = (n: number) => b * (1 - s * Math.max(0, Math.min(k(n), 4 - k(n), 1)))

  const r = Math.round(f(5) * 255)
  const g = Math.round(f(3) * 255)
  const bl = Math.round(f(1) * 255)

  return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${bl.toString(16).padStart(2, '0')}`.toUpperCase()
}

function updateColor() {
  const hex = hsbToHex(hue.value, saturation.value, brightness.value)
  emit('update:modelValue', hex)
}

function startGradientDrag(e: MouseEvent) {
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()

  const handleMove = (moveEvent: MouseEvent) => {
    const x = Math.max(0, Math.min(1, (moveEvent.clientX - rect.left) / rect.width))
    const y = Math.max(0, Math.min(1, (moveEvent.clientY - rect.top) / rect.height))

    saturation.value = x * 100
    brightness.value = (1 - y) * 100
    updateColor()
  }

  const handleUp = () => {
    document.removeEventListener('mousemove', handleMove)
    document.removeEventListener('mouseup', handleUp)
  }

  handleMove(e)
  document.addEventListener('mousemove', handleMove)
  document.addEventListener('mouseup', handleUp)
}

function startHueDrag(e: MouseEvent) {
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()

  const handleMove = (moveEvent: MouseEvent) => {
    const x = Math.max(0, Math.min(1, (moveEvent.clientX - rect.left) / rect.width))
    hue.value = x * 360
    updateColor()
  }

  const handleUp = () => {
    document.removeEventListener('mousemove', handleMove)
    document.removeEventListener('mouseup', handleUp)
  }

  handleMove(e)
  document.addEventListener('mousemove', handleMove)
  document.addEventListener('mouseup', handleUp)
}

function selectPreset(color: string) {
  emit('update:modelValue', color)
  hexToHSB(color)
}

function handleTextInput(e: Event) {
  const value = (e.target as HTMLInputElement).value
  if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
    emit('update:modelValue', value.toUpperCase())
    hexToHSB(value)
  }
}

function closePicker() {
  showPicker.value = false
}

// Handle click outside
onMounted(() => {
  const handleClickOutside = (event: MouseEvent) => {
    if (pickerContainer.value && !pickerContainer.value.contains(event.target as Node)) {
      closePicker()
    }
  }

  document.addEventListener('click', handleClickOutside)

  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
  })
})
</script>

<style scoped>
/* Additional custom styles if needed */
</style>