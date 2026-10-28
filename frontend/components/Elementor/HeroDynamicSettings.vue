<template>
  <!-- Content: words, links and what to show. Style: size, background and every colour. -->
  <div v-if="tab === 'Content'" class="divide-y divide-gray-200 -mt-2">
    <details class="group py-1" open>
      <summary :class="groupTitle">Heading <ChevronIcon /></summary>
      <div class="pb-4 space-y-3">
        <div>
          <label :class="fieldLabel">Small label above the heading</label>
          <input type="text" :value="data.topBadge" :class="fieldInput" placeholder="e.g. SAFETY & TRUST" @input="set('topBadge', value($event))" />
        </div>
        <div>
          <label :class="fieldLabel">Heading, first line</label>
          <input type="text" :value="data.headingLine1" :class="fieldInput" placeholder="Safety that moves" @input="set('headingLine1', value($event))" />
        </div>
        <div>
          <label :class="fieldLabel">Heading, second line</label>
          <input type="text" :value="data.headingLine2" :class="fieldInput" placeholder="at city speed." @input="set('headingLine2', value($event))" />
          <p :class="fieldHelp">Shown in the highlight colour.</p>
        </div>
        <div>
          <label :class="fieldLabel">Heading, third line (optional)</label>
          <input type="text" :value="data.headingLine3" :class="fieldInput" @input="set('headingLine3', value($event))" />
        </div>
        <div>
          <label :class="fieldLabel">Text under the heading</label>
          <textarea :value="data.subheading" rows="3" :class="fieldInput" placeholder="A sentence or two about what you offer" @input="set('subheading', value($event))" />
        </div>
      </div>
    </details>

    <details class="group py-1" open>
      <summary :class="groupTitle">Buttons <ChevronIcon /></summary>
      <div class="pb-4 space-y-4">
        <div v-for="btn in BUTTONS" :key="btn.key" class="space-y-2">
          <p class="text-xs font-medium text-gray-700">{{ btn.label }}</p>
          <input type="text" :value="data[`${btn.key}Text`]" :class="fieldInput" :placeholder="btn.placeholder" @input="set(`${btn.key}Text`, value($event))" />
          <input type="text" :value="data[`${btn.key}Link`]" :class="fieldInput" placeholder="Link, e.g. /contact" @input="set(`${btn.key}Link`, value($event))" />
          <div class="flex rounded-lg border border-gray-300 overflow-hidden text-xs font-medium">
            <button
              v-for="opt in ['filled', 'outline']"
              :key="opt"
              type="button"
              class="flex-1 py-1.5 capitalize transition-colors"
              :class="(data[`${btn.key}Style`] || btn.defaultStyle) === opt ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
              @click="set(`${btn.key}Style`, opt)"
            >
              {{ opt }}
            </button>
          </div>
        </div>
        <p :class="fieldHelp">Leave a button's text empty to hide it.</p>
      </div>
    </details>

    <details class="group py-1">
      <summary :class="groupTitle">Highlights under the buttons <ChevronIcon /></summary>
      <div class="pb-4 space-y-2">
        <div v-for="(badge, index) in badges" :key="index" class="flex items-center gap-2">
          <input type="text" :value="badge" :class="fieldInput" @input="updateBadge(index, value($event))" />
          <button type="button" class="shrink-0 p-2 text-gray-400 hover:text-red-600" aria-label="Remove" @click="removeBadge(index)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <button type="button" :class="addButton" @click="set('bottomBadges', [...badges, ''])">Add a highlight</button>
        <p :class="fieldHelp">Short points such as "24/7 support".</p>
      </div>
    </details>

    <details class="group py-1" open>
      <summary :class="groupTitle">Right side <ChevronIcon /></summary>
      <div class="pb-4 space-y-3">
        <div>
          <label :class="fieldLabel">Show</label>
          <select :value="rightType" :class="fieldInput" @change="set('rightContentType', value($event))">
            <option value="cards">Info cards</option>
            <option value="image">Round image</option>
            <option value="text">Feature box</option>
          </select>
        </div>

        <!-- Info cards, each folds open to edit -->
        <template v-if="rightType === 'cards'">
          <details v-for="(card, index) in cards" :key="index" class="group/card border border-gray-200 rounded-lg">
            <summary class="cursor-pointer list-none flex items-center justify-between gap-2 px-3 py-2 text-sm">
              <span class="truncate text-gray-800">{{ card.title || card.header || `Card ${index + 1}` }}</span>
              <svg class="shrink-0 w-4 h-4 text-gray-400 transition-transform group-open/card:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </summary>
            <div class="px-3 pb-3 pt-3 space-y-3 border-t border-gray-100">
              <div>
                <label :class="fieldLabel">Small label</label>
                <input type="text" :value="card.header" :class="fieldInput" placeholder="LIVE TRIP STATUS" @input="updateCard(index, 'header', value($event))" />
              </div>
              <div>
                <label :class="fieldLabel">Title</label>
                <input type="text" :value="card.title" :class="fieldInput" placeholder="Active" @input="updateCard(index, 'title', value($event))" />
              </div>
              <div>
                <label :class="fieldLabel">Description (optional)</label>
                <textarea :value="card.description" rows="2" :class="fieldInput" @input="updateCard(index, 'description', value($event))" />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div class="min-w-0">
                  <label :class="fieldLabel">Progress bar %</label>
                  <input type="number" min="0" max="100" :value="card.progress" :class="fieldInput" placeholder="None" @input="updateCard(index, 'progress', progressValue($event))" />
                </div>
                <div class="min-w-0">
                  <label :class="fieldLabel">Bar label</label>
                  <input type="text" :value="card.progressValue" :class="fieldInput" placeholder="3 min" @input="updateCard(index, 'progressValue', value($event))" />
                </div>
              </div>
              <button type="button" class="text-xs font-medium text-red-600 hover:text-red-700" @click="removeCard(index)">Remove this card</button>
            </div>
          </details>
          <button type="button" :class="addButton" @click="addCard">Add a card</button>
        </template>

        <template v-else-if="rightType === 'image'">
          <ElementorImageUpload :model-value="data.circleImage || ''" label="Image" type="general" @update:model-value="set('circleImage', $event)" />
          <div>
            <label :class="fieldLabel">Size</label>
            <select :value="data.circleImageSize || 'medium'" :class="fieldInput" @change="set('circleImageSize', value($event))">
              <option value="small">Small</option>
              <option value="medium">Medium</option>
              <option value="large">Large</option>
              <option value="xlarge">Extra large</option>
            </select>
          </div>
          <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
            <input type="checkbox" :checked="data.showHoverOverlay !== false" class="rounded border-gray-300 text-blue-600" @change="set('showHoverOverlay', checked($event))" />
            Tint the image when the mouse is over it
          </label>
          <div v-if="data.showHoverOverlay !== false">
            <label :class="fieldLabel">Text shown on hover (optional)</label>
            <input type="text" :value="data.hoverText" :class="fieldInput" placeholder="Learn more" @input="set('hoverText', value($event))" />
          </div>
        </template>

        <template v-else>
          <div>
            <label :class="fieldLabel">Top line</label>
            <input type="text" :value="data.featureBoxTopText" :class="fieldInput" placeholder="FEATURE" @input="set('featureBoxTopText', value($event))" />
          </div>
          <div>
            <label :class="fieldLabel">Main text</label>
            <input type="text" :value="data.featureBoxMainText" :class="fieldInput" placeholder="MAIN TEXT" @input="set('featureBoxMainText', value($event))" />
          </div>
          <div>
            <label :class="fieldLabel">Bottom line</label>
            <input type="text" :value="data.featureBoxBottomText" :class="fieldInput" placeholder="TAGLINE" @input="set('featureBoxBottomText', value($event))" />
          </div>
        </template>
      </div>
    </details>
  </div>

  <div v-else-if="tab === 'Style'" class="divide-y divide-gray-200 -mt-2">
    <details class="group py-1" open>
      <summary :class="groupTitle">Size and background <ChevronIcon /></summary>
      <div class="pb-4 space-y-3">
        <div>
          <label :class="fieldLabel">Height</label>
          <select :value="data.height || 'large'" :class="fieldInput" @change="set('height', value($event))">
            <option value="medium">Medium</option>
            <option value="large">Large</option>
            <option value="full">Full screen</option>
            <option value="custom">Custom</option>
          </select>
        </div>
        <div v-if="data.height === 'custom'">
          <label :class="fieldLabel">Height: {{ data.customHeight || 600 }}px</label>
          <input type="range" :value="data.customHeight || 600" min="300" max="1000" step="10" class="w-full accent-blue-600" @input="set('customHeight', parseInt(value($event)))" />
        </div>
        <div>
          <label :class="fieldLabel">Content width</label>
          <select :value="data.width || 'full'" :class="fieldInput" @change="set('width', value($event))">
            <option value="small">Narrow</option>
            <option value="medium">Medium</option>
            <option value="large">Wide</option>
            <option value="full">Full width</option>
            <option value="custom">Custom</option>
          </select>
        </div>
        <div v-if="data.width === 'custom'">
          <label :class="fieldLabel">Width: {{ data.customWidth || 1280 }}px</label>
          <input type="range" :value="data.customWidth || 1280" min="800" max="1920" step="20" class="w-full accent-blue-600" @input="set('customWidth', parseInt(value($event)))" />
        </div>
        <ElementorImageUpload :model-value="data.backgroundImage || ''" label="Background image" type="general" @update:model-value="set('backgroundImage', $event)" />
        <template v-if="data.backgroundImage">
          <ElementorColorPicker :model-value="data.overlayColor || '#000000'" label="Shade over the image" @update:model-value="set('overlayColor', $event)" />
          <div>
            <label :class="fieldLabel">Shade strength: {{ Math.round((data.overlayOpacity ?? 0.5) * 100) }}%</label>
            <input type="range" :value="data.overlayOpacity ?? 0.5" min="0" max="1" step="0.05" class="w-full accent-blue-600" @input="set('overlayOpacity', parseFloat(value($event)))" />
          </div>
        </template>
        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
          <input type="checkbox" :checked="data.showDecorations" class="rounded border-gray-300 text-blue-600" @change="set('showDecorations', checked($event))" />
          Soft decorative shapes in the background
        </label>
      </div>
    </details>

    <details class="group py-1">
      <summary :class="groupTitle">Heading colours <ChevronIcon /></summary>
      <div class="pb-4 space-y-3">
        <ElementorColorPicker :model-value="data.topBadgeColor || '#14b8a6'" label="Small label text" @update:model-value="set('topBadgeColor', $event)" />
        <ElementorColorPicker :model-value="data.topBadgeBgColor || 'rgba(20, 184, 166, 0.1)'" label="Small label background" @update:model-value="set('topBadgeBgColor', $event)" />
        <ElementorColorPicker :model-value="data.headingLine1Color || '#ffffff'" label="Heading, first line" @update:model-value="set('headingLine1Color', $event)" />
        <ElementorColorPicker :model-value="data.headingLine2Color || '#14b8a6'" label="Heading, second line (highlight)" @update:model-value="set('headingLine2Color', $event)" />
        <ElementorColorPicker :model-value="data.headingLine3Color || '#ffffff'" label="Heading, third line" @update:model-value="set('headingLine3Color', $event)" />
      </div>
    </details>

    <details class="group py-1">
      <summary :class="groupTitle">Button colours <ChevronIcon /></summary>
      <div class="pb-4 space-y-3">
        <template v-for="btn in BUTTONS" :key="btn.key">
          <!-- An outlined button has one colour, used for its text and border -->
          <ElementorColorPicker
            v-if="(data[`${btn.key}Style`] || btn.defaultStyle) === 'filled'"
            :model-value="data[`${btn.key}BgColor`] || btn.defaultBg"
            :label="btn.label"
            @update:model-value="set(`${btn.key}BgColor`, $event)"
          />
          <ElementorColorPicker
            :model-value="data[`${btn.key}Color`] || btn.defaultText"
            :label="(data[`${btn.key}Style`] || btn.defaultStyle) === 'filled' ? `${btn.label} text` : `${btn.label} (outline)`"
            @update:model-value="set(`${btn.key}Color`, $event)"
          />
        </template>
      </div>
    </details>

    <details v-if="badges.length" class="group py-1">
      <summary :class="groupTitle">Highlight colours <ChevronIcon /></summary>
      <div class="pb-4 space-y-3">
        <ElementorColorPicker :model-value="data.bottomBadgeTextColor || '#ffffff'" label="Text" @update:model-value="set('bottomBadgeTextColor', $event)" />
        <ElementorColorPicker :model-value="data.bottomBadgeBgColor || 'rgba(255, 255, 255, 0.1)'" label="Background" @update:model-value="set('bottomBadgeBgColor', $event)" />
      </div>
    </details>

    <details class="group py-1">
      <summary :class="groupTitle">Right side colours <ChevronIcon /></summary>
      <div class="pb-4 space-y-3">
        <!-- Colours are stored on each card; set here, they apply to all of them -->
        <p v-if="rightType === 'cards' && !cards.length" :class="fieldHelp">Add a card on the Content tab to choose its colours.</p>
        <template v-else-if="rightType === 'cards'">
          <p :class="fieldHelp">Applies to every card.</p>
          <ElementorColorPicker
            v-for="field in CARD_COLORS"
            :key="field.key"
            :model-value="cards[0]?.[field.key] || field.fallback"
            :label="field.label"
            @update:model-value="updateAllCards(field.key, $event)"
          />
        </template>
        <ElementorColorPicker
          v-else-if="rightType === 'image'"
          :model-value="data.hoverOverlayColor || 'rgba(147, 51, 234, 0.85)'"
          label="Hover tint"
          @update:model-value="set('hoverOverlayColor', $event)"
        />
        <ElementorColorPicker
          v-else
          :model-value="data.featureBoxColor || '#0D9488'"
          label="Feature box"
          @update:model-value="set('featureBoxColor', $event)"
        />
      </div>
    </details>
  </div>
</template>

<script setup lang="ts">
// Settings panel for the hero_dynamic block (PageBuilder/HeroDynamic.vue)
const props = defineProps<{
  data: Record<string, any>
  tab: string
}>()

const emit = defineEmits<{ update: [patch: Record<string, any>] }>()

const ChevronIcon = () =>
  h('svg', { class: 'w-4 h-4 text-gray-400 transition-transform group-open:rotate-180', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 9l-7 7-7-7' }),
  ])

const groupTitle = 'cursor-pointer list-none flex items-center justify-between py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-700'
const fieldLabel = 'block text-xs font-medium text-gray-600 mb-1'
const fieldInput = 'w-full min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500'
const fieldHelp = 'text-xs text-gray-500'
const addButton = 'w-full py-2 border border-dashed border-gray-300 rounded-lg text-sm text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-colors'

// Defaults match PageBuilder/HeroDynamic.vue
const BUTTONS = [
  { key: 'primaryCta', label: 'Main button', placeholder: 'Get Started', defaultStyle: 'filled', defaultBg: '#14b8a6', defaultText: '#ffffff' },
  { key: 'secondaryCta', label: 'Second button', placeholder: 'Learn More', defaultStyle: 'outline', defaultBg: 'rgba(255, 255, 255, 0.1)', defaultText: '#ffffff' },
]

const CARD_COLORS = [
  { key: 'cardBackgroundColor', label: 'Card background', fallback: 'rgba(255, 255, 255, 0.05)' },
  { key: 'cardBorderColor', label: 'Card border', fallback: 'rgba(255, 255, 255, 0.1)' },
  { key: 'headerTextColor', label: 'Small label', fallback: '#9ca3af' },
  { key: 'titleTextColor', label: 'Title', fallback: '#ffffff' },
  { key: 'descriptionTextColor', label: 'Description', fallback: '#d1d5db' },
  { key: 'progressColor', label: 'Progress bar', fallback: '#14b8a6' },
]

const rightType = computed(() => props.data.rightContentType || 'cards')
const badges = computed<string[]>(() => props.data.bottomBadges || [])
const cards = computed<Record<string, any>[]>(() => props.data.infoCards || [])

const set = (key: string, val: any) => emit('update', { [key]: val })
const value = (e: Event) => (e.target as HTMLInputElement).value
const checked = (e: Event) => (e.target as HTMLInputElement).checked
const progressValue = (e: Event) => {
  const n = parseInt(value(e))
  return Number.isNaN(n) ? undefined : Math.min(Math.max(n, 0), 100)
}

const updateBadge = (index: number, val: string) => {
  const next = [...badges.value]
  next[index] = val
  set('bottomBadges', next)
}

const removeBadge = (index: number) => set('bottomBadges', badges.value.filter((_, i) => i !== index))

const updateCard = (index: number, key: string, val: any) =>
  set('infoCards', cards.value.map((card, i) => (i === index ? { ...card, [key]: val } : card)))

const updateAllCards = (key: string, val: any) => set('infoCards', cards.value.map((card) => ({ ...card, [key]: val })))

const removeCard = (index: number) => set('infoCards', cards.value.filter((_, i) => i !== index))

// A new card starts with the same colours as the others
const addCard = () => {
  const colours = Object.fromEntries(CARD_COLORS.map((f) => [f.key, cards.value[0]?.[f.key]]).filter(([, v]) => v))
  set('infoCards', [...cards.value, { header: 'NEW CARD', title: 'Title', description: '', progressValue: '', ...colours }])
}
</script>
