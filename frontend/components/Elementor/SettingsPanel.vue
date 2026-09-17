<template>
  <div class="w-80 bg-white border-l border-gray-200 flex flex-col h-full overflow-hidden">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-gray-50">
      <div>
        <h3 class="text-sm font-semibold text-gray-900">{{ elementTitle }}</h3>
        <p class="text-xs text-gray-500">{{ elementSubtitle }}</p>
      </div>
      <button
        @click="$emit('close')"
        class="text-gray-400 hover:text-gray-600 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 bg-white">
      <button
        v-for="tab in availableTabs"
        :key="tab"
        @click="activeTab = tab"
        :class="[
          'flex-1 px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          activeTab === tab
            ? 'text-blue-600 border-blue-600'
            : 'text-gray-600 border-transparent hover:text-gray-900'
        ]"
      >
        {{ tab }}
      </button>
    </div>

    <!-- Settings Content -->
    <div class="flex-1 overflow-y-auto p-4">
      <!-- Section Settings -->
      <div v-if="selectedElement.type === 'section'" class="space-y-4">
        <!-- Content Tab -->
        <div v-if="activeTab === 'Content'">
          <div class="space-y-4">
            <!-- Layout -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Layout</label>
              <select
                :value="elementData.layout"
                @change="updateSetting('layout', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="boxed">Boxed</option>
                <option value="full_width">Full Width</option>
              </select>
            </div>

            <!-- Column Gap -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Column Gap</label>
              <select
                :value="elementData.gap"
                @change="updateSetting('gap', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="no">No Gap</option>
                <option value="narrow">Narrow</option>
                <option value="default">Default</option>
                <option value="wide">Wide</option>
              </select>
            </div>

            <!-- Height -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Height</label>
              <select
                :value="elementData.height"
                @change="updateSetting('height', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="default">Default</option>
                <option value="min-height">Min Height</option>
                <option value="fit-to-screen">Fit to Screen</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Style Tab -->
        <div v-else-if="activeTab === 'Style'">
          <div class="space-y-4">
            <!-- Background Color -->
            <ElementorColorPicker
              :model-value="elementData.backgroundColor || '#FFFFFF'"
              @update:model-value="updateSetting('backgroundColor', $event)"
              label="Background Color"
            />

            <!-- Background Image -->
            <ElementorImageUpload
              :model-value="elementData.backgroundImage || ''"
              @update:model-value="updateSetting('backgroundImage', $event)"
              label="Background Image"
              type="hero"
            />

            <!-- Padding -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Padding (px)</label>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Top</label>
                  <input
                    type="number"
                    :value="elementData.padding?.top || 0"
                    @input="updatePadding('top', ($event.target as HTMLInputElement).value)"
                    min="0"
                    class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Right</label>
                  <input
                    type="number"
                    :value="elementData.padding?.right || 0"
                    @input="updatePadding('right', ($event.target as HTMLInputElement).value)"
                    min="0"
                    class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Bottom</label>
                  <input
                    type="number"
                    :value="elementData.padding?.bottom || 0"
                    @input="updatePadding('bottom', ($event.target as HTMLInputElement).value)"
                    min="0"
                    class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Left</label>
                  <input
                    type="number"
                    :value="elementData.padding?.left || 0"
                    @input="updatePadding('left', ($event.target as HTMLInputElement).value)"
                    min="0"
                    class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>

            <!-- Margin -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Margin (px)</label>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Top</label>
                  <input
                    type="number"
                    :value="elementData.margin?.top || 0"
                    @input="updateMargin('top', ($event.target as HTMLInputElement).value)"
                    min="0"
                    class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Bottom</label>
                  <input
                    type="number"
                    :value="elementData.margin?.bottom || 0"
                    @input="updateMargin('bottom', ($event.target as HTMLInputElement).value)"
                    min="0"
                    class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Widget Settings -->
      <div v-else-if="selectedElement.type === 'widget'" class="space-y-4">
        <!-- Heading Widget -->
        <div v-if="widgetType === 'heading'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Text -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Text</label>
              <input
                type="text"
                :value="elementData.text"
                @input="updateData('text', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- HTML Tag -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">HTML Tag</label>
              <select
                :value="elementData.tag || 'h2'"
                @change="updateData('tag', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="h1">H1</option>
                <option value="h2">H2</option>
                <option value="h3">H3</option>
                <option value="h4">H4</option>
                <option value="h5">H5</option>
                <option value="h6">H6</option>
              </select>
            </div>

            <!-- Alignment -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Alignment</label>
              <div class="flex space-x-2">
                <button
                  v-for="align in ['left', 'center', 'right']"
                  :key="align"
                  @click="updateData('alignment', align)"
                  :class="[
                    'flex-1 px-3 py-2 rounded border transition-colors',
                    elementData.alignment === align
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-white text-gray-700 border-gray-300 hover:border-blue-500'
                  ]"
                >
                  {{ align.charAt(0).toUpperCase() + align.slice(1) }}
                </button>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'Style'" class="space-y-4">
            <!-- Color -->
            <ElementorColorPicker
              :model-value="elementData.color || '#000000'"
              @update:model-value="updateData('color', $event)"
              label="Color"
            />

            <!-- Font Size -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Font Size (px)</label>
              <input
                type="number"
                :value="elementData.typography?.size || 32"
                @input="updateTypography('size', parseInt(($event.target as HTMLInputElement).value))"
                min="8"
                max="120"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Font Weight -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Font Weight</label>
              <select
                :value="elementData.typography?.weight || 600"
                @change="updateTypography('weight', parseInt(($event.target as HTMLSelectElement).value))"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option :value="300">Light</option>
                <option :value="400">Normal</option>
                <option :value="500">Medium</option>
                <option :value="600">Semi Bold</option>
                <option :value="700">Bold</option>
                <option :value="800">Extra Bold</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Text Editor Widget -->
        <div v-else-if="widgetType === 'text_editor'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Content -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
              <textarea
                :value="elementData.content"
                @input="updateData('content', ($event.target as HTMLTextAreaElement).value)"
                rows="10"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">HTML is supported</p>
            </div>

            <!-- Alignment -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Alignment</label>
              <div class="flex space-x-2">
                <button
                  v-for="align in ['left', 'center', 'right', 'justify']"
                  :key="align"
                  @click="updateData('alignment', align)"
                  :class="[
                    'flex-1 px-3 py-2 rounded border transition-colors text-xs',
                    elementData.alignment === align
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-white text-gray-700 border-gray-300 hover:border-blue-500'
                  ]"
                >
                  {{ align.charAt(0).toUpperCase() + align.slice(1) }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Image Widget -->
        <div v-else-if="widgetType === 'image'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Image URL -->
            <ElementorImageUpload
              :model-value="elementData.url || ''"
              @update:model-value="updateData('url', $event)"
              label="Image"
              type="general"
            />

            <!-- Alt Text -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Alt Text</label>
              <input
                type="text"
                :value="elementData.alt"
                @input="updateData('alt', ($event.target as HTMLInputElement).value)"
                placeholder="Image description"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Alignment -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Alignment</label>
              <div class="flex space-x-2">
                <button
                  v-for="align in ['left', 'center', 'right']"
                  :key="align"
                  @click="updateData('alignment', align)"
                  :class="[
                    'flex-1 px-3 py-2 rounded border transition-colors',
                    elementData.alignment === align
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-white text-gray-700 border-gray-300 hover:border-blue-500'
                  ]"
                >
                  {{ align.charAt(0).toUpperCase() + align.slice(1) }}
                </button>
              </div>
            </div>

            <!-- Width -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Width</label>
              <div class="flex items-center space-x-2">
                <input
                  type="number"
                  :value="elementData.width?.size || 100"
                  @input="updateImageSize('size', parseInt(($event.target as HTMLInputElement).value))"
                  min="1"
                  max="100"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <select
                  :value="elementData.width?.unit || '%'"
                  @change="updateImageSize('unit', ($event.target as HTMLSelectElement).value)"
                  class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="%">%</option>
                  <option value="px">px</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Button Widget -->
        <div v-else-if="widgetType === 'button'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Text -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Text</label>
              <input
                type="text"
                :value="elementData.text"
                @input="updateData('text', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- URL -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Link URL</label>
              <input
                type="text"
                :value="elementData.url"
                @input="updateData('url', ($event.target as HTMLInputElement).value)"
                placeholder="https://example.com"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Alignment -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Alignment</label>
              <div class="flex space-x-2">
                <button
                  v-for="align in ['left', 'center', 'right']"
                  :key="align"
                  @click="updateData('alignment', align)"
                  :class="[
                    'flex-1 px-3 py-2 rounded border transition-colors',
                    elementData.alignment === align
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-white text-gray-700 border-gray-300 hover:border-blue-500'
                  ]"
                >
                  {{ align.charAt(0).toUpperCase() + align.slice(1) }}
                </button>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'Style'" class="space-y-4">
            <!-- Button Style -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Button Style</label>
              <select
                :value="elementData.style || 'primary'"
                @change="updateData('style', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="primary">Primary</option>
                <option value="secondary">Secondary</option>
                <option value="accent">Accent</option>
                <option value="outline">Outline</option>
              </select>
            </div>

            <!-- Size -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Size</label>
              <select
                :value="elementData.size || 'md'"
                @change="updateData('size', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="sm">Small</option>
                <option value="md">Medium</option>
                <option value="lg">Large</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Spacer Widget -->
        <div v-else-if="widgetType === 'spacer'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Height -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Height</label>
              <div class="flex items-center space-x-2">
                <input
                  type="number"
                  :value="elementData.space?.size || 50"
                  @input="updateSpaceSize('size', parseInt(($event.target as HTMLInputElement).value))"
                  min="0"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <select
                  :value="elementData.space?.unit || 'px'"
                  @change="updateSpaceSize('unit', ($event.target as HTMLSelectElement).value)"
                  class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="px">px</option>
                  <option value="em">em</option>
                  <option value="%">%</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Divider Widget -->
        <div v-else-if="widgetType === 'divider'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Style -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Style</label>
              <select
                :value="elementData.style || 'solid'"
                @change="updateData('style', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="solid">Solid</option>
                <option value="dashed">Dashed</option>
                <option value="dotted">Dotted</option>
              </select>
            </div>

            <!-- Color -->
            <ElementorColorPicker
              :model-value="elementData.color || '#E0E0E0'"
              @update:model-value="updateData('color', $event)"
              label="Color"
            />

            <!-- Weight -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Weight</label>
              <div class="flex items-center space-x-2">
                <input
                  type="number"
                  :value="elementData.weight?.size || 1"
                  @input="updateWeightSize('size', parseInt(($event.target as HTMLInputElement).value))"
                  min="1"
                  max="10"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <select
                  :value="elementData.weight?.unit || 'px'"
                  @change="updateWeightSize('unit', ($event.target as HTMLSelectElement).value)"
                  class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="px">px</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Gallery Widget -->
        <div v-else-if="widgetType === 'gallery'">
          <ElementorGallerySettings
            :widget="currentWidget"
            @update="handleHeroSliderUpdate"
          />
        </div>

        <!-- Hero Widget -->
        <div v-else-if="widgetType === 'hero'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Subheading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Subheading</label>
              <textarea
                :value="elementData.subheading"
                @input="updateData('subheading', ($event.target as HTMLTextAreaElement).value)"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- CTA Text -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Button Text</label>
              <input
                type="text"
                :value="elementData.ctaText"
                @input="updateData('ctaText', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- CTA Link -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Button Link</label>
              <input
                type="text"
                :value="elementData.ctaLink"
                @input="updateData('ctaLink', ($event.target as HTMLInputElement).value)"
                placeholder="https://example.com"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Background Image -->
            <ElementorImageUpload
              :model-value="elementData.backgroundImage || ''"
              @update:model-value="updateData('backgroundImage', $event)"
              label="Background Image"
              type="hero"
            />

            <!-- Foreground Image -->
            <ElementorImageUpload
              :model-value="elementData.foregroundImage || ''"
              @update:model-value="updateData('foregroundImage', $event)"
              label="Foreground Image"
              type="hero"
            />
          </div>

          <div v-else-if="activeTab === 'Style'" class="space-y-4">
            <!-- Height -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Height</label>
              <select
                :value="elementData.height || 'large'"
                @change="updateData('height', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="medium">Medium</option>
                <option value="large">Large</option>
                <option value="full">Full Screen</option>
              </select>
            </div>

            <!-- Overlay -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Overlay</label>
              <select
                :value="elementData.overlay || 'gradient'"
                @change="updateData('overlay', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="none">None</option>
                <option value="dark">Dark</option>
                <option value="gradient">Gradient</option>
                <option value="primary">Primary Color</option>
              </select>
            </div>

            <!-- Show Particles -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showParticles"
                  @change="updateData('showParticles', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Animated Particles</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Hero Split Widget -->
        <div v-else-if="widgetType === 'hero_split'">
          <div v-if="activeTab === 'Content'" class="space-y-4">

            <!-- Heading Line 1 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading Line 1</label>
              <input
                type="text"
                :value="elementData.headingLine1"
                @input="updateData('headingLine1', ($event.target as HTMLInputElement).value)"
                placeholder="Empowering Ghana's"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Heading Line 1 Color -->
            <ElementorColorPicker
              :model-value="elementData.headingLine1Color || '#0A1E3E'"
              @update:model-value="updateData('headingLine1Color', $event)"
              label="Line 1 Color"
            />

            <!-- Heading Line 2 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading Line 2</label>
              <input
                type="text"
                :value="elementData.headingLine2"
                @input="updateData('headingLine2', ($event.target as HTMLInputElement).value)"
                placeholder="Intellectual"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Heading Line 2 Color -->
            <ElementorColorPicker
              :model-value="elementData.headingLine2Color || '#9333EA'"
              @update:model-value="updateData('headingLine2Color', $event)"
              label="Line 2 Color"
            />

            <!-- Heading Line 3 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading Line 3</label>
              <input
                type="text"
                :value="elementData.headingLine3"
                @input="updateData('headingLine3', ($event.target as HTMLInputElement).value)"
                placeholder="Backbone"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Heading Line 3 Color -->
            <ElementorColorPicker
              :model-value="elementData.headingLine3Color || '#0A1E3E'"
              @update:model-value="updateData('headingLine3Color', $event)"
              label="Line 3 Color"
            />

            <!-- Subheading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Subheading</label>
              <textarea
                :value="elementData.subheading"
                @input="updateData('subheading', ($event.target as HTMLTextAreaElement).value)"
                rows="3"
                placeholder="Join a prestigious ecosystem..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Primary CTA Text -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Primary Button Text</label>
              <input
                type="text"
                :value="elementData.primaryCtaText"
                @input="updateData('primaryCtaText', ($event.target as HTMLInputElement).value)"
                placeholder="Get Started"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Primary CTA Link -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Primary Button Link</label>
              <input
                type="text"
                :value="elementData.primaryCtaLink"
                @input="updateData('primaryCtaLink', ($event.target as HTMLInputElement).value)"
                placeholder="/membership/apply"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Secondary CTA Text -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Button Text</label>
              <input
                type="text"
                :value="elementData.secondaryCtaText"
                @input="updateData('secondaryCtaText', ($event.target as HTMLInputElement).value)"
                placeholder="Learn More"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Secondary CTA Link -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Button Link</label>
              <input
                type="text"
                :value="elementData.secondaryCtaLink"
                @input="updateData('secondaryCtaLink', ($event.target as HTMLInputElement).value)"
                placeholder="/about"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200 my-4"></div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Feature Box</h4>

            <!-- Show Feature Box Toggle -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showFeatureBox !== false"
                  @change="updateData('showFeatureBox', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Feature Box</span>
              </label>
            </div>

            <template v-if="elementData.showFeatureBox !== false">
              <!-- Feature Box Top Text -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feature Box Top Text</label>
                <input
                  type="text"
                  :value="elementData.featureBoxTopText"
                  @input="updateData('featureBoxTopText', ($event.target as HTMLInputElement).value)"
                  placeholder="NAIDMIIC"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Feature Box Main Text -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feature Box Main Text</label>
                <input
                  type="text"
                  :value="elementData.featureBoxMainText"
                  @input="updateData('featureBoxMainText', ($event.target as HTMLInputElement).value)"
                  placeholder="RESEARCH"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Feature Box Bottom Text -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feature Box Bottom Text</label>
                <input
                  type="text"
                  :value="elementData.featureBoxBottomText"
                  @input="updateData('featureBoxBottomText', ($event.target as HTMLInputElement).value)"
                  placeholder="INNOVATION"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </template>
          </div>

          <div v-else-if="activeTab === 'Style'" class="space-y-4">
            <template v-if="elementData.showFeatureBox !== false">
              <!-- Feature Box Color -->
              <ElementorColorPicker
                :model-value="elementData.featureBoxColor || '#0D9488'"
                @update:model-value="updateData('featureBoxColor', $event)"
                label="Feature Box Color"
              />

              <!-- Feature Box Shape -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feature Box Shape</label>
                <select
                  :value="elementData.featureBoxShape || 'rounded'"
                  @change="updateData('featureBoxShape', ($event.target as HTMLSelectElement).value)"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="rounded">Rounded Square</option>
                  <option value="circle">Circle</option>
                </select>
              </div>

              <!-- Divider -->
              <div class="border-t border-gray-200 my-4"></div>
            </template>

            <!-- Height -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Section Height</label>
              <select
                :value="elementData.height || 'large'"
                @change="updateData('height', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="medium">Medium (600px)</option>
                <option value="large">Large (700px)</option>
                <option value="full">Full Screen</option>
                <option value="custom">Custom Height</option>
              </select>
            </div>

            <!-- Custom Height Slider -->
            <div v-if="elementData.height === 'custom'" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Custom Height: {{ elementData.customHeight || 700 }}px
              </label>
              <input
                type="range"
                :value="elementData.customHeight || 700"
                @input="updateData('customHeight', parseInt(($event.target as HTMLInputElement).value))"
                min="300"
                max="1000"
                step="10"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
              />
              <div class="flex justify-between text-xs text-gray-500">
                <span>300px</span>
                <span>650px</span>
                <span>1000px</span>
              </div>
            </div>

            <!-- Content Width -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Content Width</label>
              <select
                :value="elementData.width || 'full'"
                @change="updateData('width', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="small">Small (1024px)</option>
                <option value="medium">Medium (1280px)</option>
                <option value="large">Large (1536px)</option>
                <option value="full">Full Width</option>
                <option value="custom">Custom Width</option>
              </select>
            </div>

            <!-- Custom Width Slider -->
            <div v-if="elementData.width === 'custom'" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Custom Width: {{ elementData.customWidth || 1280 }}px
              </label>
              <input
                type="range"
                :value="elementData.customWidth || 1280"
                @input="updateData('customWidth', parseInt(($event.target as HTMLInputElement).value))"
                min="800"
                max="1920"
                step="20"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
              />
              <div class="flex justify-between text-xs text-gray-500">
                <span>800px</span>
                <span>1360px</span>
                <span>1920px</span>
              </div>
            </div>

            <!-- Show Decorations -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showDecorations"
                  @change="updateData('showDecorations', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Background Decorations</span>
              </label>
            </div>

            <!-- Background Image -->
            <ElementorImageUpload
              :model-value="elementData.backgroundImage || ''"
              @update:model-value="updateData('backgroundImage', $event)"
              label="Background Image (Optional)"
              type="general"
            />
          </div>
        </div>

        <!-- Hero Dynamic Widget -->
        <div v-else-if="widgetType === 'hero_dynamic'">
          <div v-if="activeTab === 'Content'" class="space-y-3">
            <!-- Top Badge Section -->
            <details class="group bg-gray-50 rounded-lg">
              <summary class="cursor-pointer px-4 py-3 font-medium text-sm text-gray-700 hover:bg-gray-100 rounded-lg flex items-center justify-between">
                <span>🏷️ Top Badge</span>
                <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </summary>
              <div class="px-4 pb-4 pt-2 space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-2">Badge Text</label>
                  <input
                    type="text"
                    :value="elementData.topBadge"
                    @input="updateData('topBadge', ($event.target as HTMLInputElement).value)"
                    placeholder="SAFETY & TRUST"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <ElementorColorPicker
                    :model-value="elementData.topBadgeColor || '#14b8a6'"
                    @update:model-value="updateData('topBadgeColor', $event)"
                    label="Text Color"
                  />
                  <ElementorColorPicker
                    :model-value="elementData.topBadgeBgColor || 'rgba(20, 184, 166, 0.1)'"
                    @update:model-value="updateData('topBadgeBgColor', $event)"
                    label="Background"
                  />
                </div>
              </div>
            </details>

            <!-- Heading & Subheading Section -->
            <details class="group bg-gray-50 rounded-lg" open>
              <summary class="cursor-pointer px-4 py-3 font-medium text-sm text-gray-700 hover:bg-gray-100 rounded-lg flex items-center justify-between">
                <span>📝 Heading & Subheading</span>
                <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </summary>
              <div class="px-4 pb-4 pt-2 space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-2">Heading Line 1</label>
                  <input
                    type="text"
                    :value="elementData.headingLine1"
                    @input="updateData('headingLine1', ($event.target as HTMLInputElement).value)"
                    placeholder="Safety that moves"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <ElementorColorPicker
                    :model-value="elementData.headingLine1Color || '#ffffff'"
                    @update:model-value="updateData('headingLine1Color', $event)"
                    label="Line 1 Color"
                    class="mt-2"
                  />
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-2">Heading Line 2</label>
                  <input
                    type="text"
                    :value="elementData.headingLine2"
                    @input="updateData('headingLine2', ($event.target as HTMLInputElement).value)"
                    placeholder="at city speed."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <ElementorColorPicker
                    :model-value="elementData.headingLine2Color || '#14b8a6'"
                    @update:model-value="updateData('headingLine2Color', $event)"
                    label="Line 2 Color"
                    class="mt-2"
                  />
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-2">Heading Line 3 (Optional)</label>
                  <input
                    type="text"
                    :value="elementData.headingLine3"
                    @input="updateData('headingLine3', ($event.target as HTMLInputElement).value)"
                    placeholder=""
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <ElementorColorPicker
                    :model-value="elementData.headingLine3Color || '#ffffff'"
                    @update:model-value="updateData('headingLine3Color', $event)"
                    label="Line 3 Color"
                    class="mt-2"
                  />
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-2">Subheading</label>
                  <textarea
                    :value="elementData.subheading"
                    @input="updateData('subheading', ($event.target as HTMLTextAreaElement).value)"
                    rows="3"
                    placeholder="We are committed to upholding high standards..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  ></textarea>
                </div>
              </div>
            </details>

            <!-- Call to Action Buttons Section -->
            <details class="group bg-gray-50 rounded-lg">
              <summary class="cursor-pointer px-4 py-3 font-medium text-sm text-gray-700 hover:bg-gray-100 rounded-lg flex items-center justify-between">
                <span>🔘 Call to Action Buttons</span>
                <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </summary>
              <div class="px-4 pb-4 pt-2 space-y-3">

            <!-- Primary CTA Text -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Primary Button Text</label>
              <input
                type="text"
                :value="elementData.primaryCtaText"
                @input="updateData('primaryCtaText', ($event.target as HTMLInputElement).value)"
                placeholder="Get Started"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Primary CTA Link -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Primary Button Link</label>
              <input
                type="text"
                :value="elementData.primaryCtaLink"
                @input="updateData('primaryCtaLink', ($event.target as HTMLInputElement).value)"
                placeholder="#"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Primary CTA Style -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Primary Button Style</label>
              <select
                :value="elementData.primaryCtaStyle || 'filled'"
                @change="updateData('primaryCtaStyle', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="filled">Filled</option>
                <option value="outline">Outline</option>
              </select>
            </div>

            <!-- Primary CTA Colors -->
            <div class="grid grid-cols-2 gap-3">
              <ElementorColorPicker
                :model-value="elementData.primaryCtaColor || '#ffffff'"
                @update:model-value="updateData('primaryCtaColor', $event)"
                label="Primary Text Color"
              />
              <ElementorColorPicker
                :model-value="elementData.primaryCtaBgColor || '#14b8a6'"
                @update:model-value="updateData('primaryCtaBgColor', $event)"
                label="Primary BG Color"
              />
            </div>

            <!-- Secondary CTA Text -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Button Text</label>
              <input
                type="text"
                :value="elementData.secondaryCtaText"
                @input="updateData('secondaryCtaText', ($event.target as HTMLInputElement).value)"
                placeholder="Learn More"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Secondary CTA Link -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Button Link</label>
              <input
                type="text"
                :value="elementData.secondaryCtaLink"
                @input="updateData('secondaryCtaLink', ($event.target as HTMLInputElement).value)"
                placeholder="#"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Secondary CTA Style -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Button Style</label>
              <select
                :value="elementData.secondaryCtaStyle || 'outline'"
                @change="updateData('secondaryCtaStyle', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="filled">Filled</option>
                <option value="outline">Outline</option>
              </select>
            </div>

            <!-- Secondary CTA Colors -->
                <div class="grid grid-cols-2 gap-3">
                  <ElementorColorPicker
                    :model-value="elementData.secondaryCtaColor || '#ffffff'"
                    @update:model-value="updateData('secondaryCtaColor', $event)"
                    label="Secondary Text Color"
                  />
                  <ElementorColorPicker
                    :model-value="elementData.secondaryCtaBgColor || 'transparent'"
                    @update:model-value="updateData('secondaryCtaBgColor', $event)"
                    label="Secondary BG Color"
                  />
                </div>
              </div>
            </details>

            <!-- Bottom Badges Section -->
            <details class="group bg-gray-50 rounded-lg">
              <summary class="cursor-pointer px-4 py-3 font-medium text-sm text-gray-700 hover:bg-gray-100 rounded-lg flex items-center justify-between">
                <span>🏷️ Bottom Badges</span>
                <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </summary>
              <div class="px-4 pb-4 pt-2 space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-2">Bottom Badges (comma-separated)</label>
                  <input
                    type="text"
                    :value="(elementData.bottomBadges || []).join(', ')"
                    @input="updateData('bottomBadges', ($event.target as HTMLInputElement).value.split(',').map((b: string) => b.trim()).filter((b: string) => b))"
                    placeholder="Verified drivers, Live tracking, 24/7 support"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <p class="text-xs text-gray-500 mt-1">Separate badges with commas</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <ElementorColorPicker
                    :model-value="elementData.bottomBadgeTextColor || '#ffffff'"
                    @update:model-value="updateData('bottomBadgeTextColor', $event)"
                    label="Badge Text Color"
                  />
                  <ElementorColorPicker
                    :model-value="elementData.bottomBadgeBgColor || 'rgba(255, 255, 255, 0.1)'"
                    @update:model-value="updateData('bottomBadgeBgColor', $event)"
                    label="Badge Background"
                  />
                </div>
              </div>
            </details>

            <!-- Right Side Content Section -->
            <details class="group bg-gray-50 rounded-lg" open>
              <summary class="cursor-pointer px-4 py-3 font-medium text-sm text-gray-700 hover:bg-gray-100 rounded-lg flex items-center justify-between">
                <span>📦 Right Side Content</span>
                <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </summary>
              <div class="px-4 pb-4 pt-2 space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-2">Content Type</label>
                  <select
                    :value="elementData.rightContentType || 'cards'"
                    @change="updateData('rightContentType', ($event.target as HTMLSelectElement).value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="text">Text Content (Feature Box)</option>
                    <option value="cards">Info Cards</option>
                    <option value="image">Circular Image</option>
                  </select>
                </div>

            <!-- Info Cards Editor (shown when cards type is selected) -->
            <template v-if="elementData.rightContentType === 'cards'">
              <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                <div class="flex items-center justify-between">
                  <h5 class="text-sm font-semibold text-gray-900">Info Cards</h5>
                  <button
                    @click="addInfoCard"
                    class="text-xs px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                  >
                    + Add Card
                  </button>
                </div>

                <div
                  v-for="(card, index) in (elementData.infoCards || [])"
                  :key="index"
                  class="bg-white rounded-lg p-4 space-y-3 border border-gray-200"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-600">Card {{ index + 1 }}</span>
                    <button
                      @click="removeInfoCard(index)"
                      class="text-xs text-red-600 hover:text-red-700"
                    >
                      Remove
                    </button>
                  </div>

                  <!-- Card Header -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Header</label>
                    <input
                      type="text"
                      :value="card.header"
                      @input="updateInfoCard(index, 'header', ($event.target as HTMLInputElement).value)"
                      placeholder="LIVE TRIP STATUS"
                      class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <!-- Card Title -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Title</label>
                    <input
                      type="text"
                      :value="card.title"
                      @input="updateInfoCard(index, 'title', ($event.target as HTMLInputElement).value)"
                      placeholder="Active"
                      class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <!-- Card Description -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Description (Optional)</label>
                    <textarea
                      :value="card.description"
                      @input="updateInfoCard(index, 'description', ($event.target as HTMLTextAreaElement).value)"
                      rows="2"
                      placeholder="Additional details..."
                      class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                    ></textarea>
                  </div>

                  <!-- Progress Bar Options -->
                  <div class="flex gap-2">
                    <div class="flex-1">
                      <label class="block text-xs font-medium text-gray-700 mb-1">Progress %</label>
                      <input
                        type="number"
                        min="0"
                        max="100"
                        :value="card.progress"
                        @input="updateInfoCard(index, 'progress', parseInt(($event.target as HTMLInputElement).value))"
                        placeholder="85"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div class="flex-1">
                      <label class="block text-xs font-medium text-gray-700 mb-1">Progress Label</label>
                      <input
                        type="text"
                        :value="card.progressValue"
                        @input="updateInfoCard(index, 'progressValue', ($event.target as HTMLInputElement).value)"
                        placeholder="3 min"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>

                  <!-- Progress Color -->
                  <ElementorColorPicker
                    :model-value="card.progressColor || '#14b8a6'"
                    @update:model-value="updateInfoCard(index, 'progressColor', $event)"
                    label="Progress Bar Color"
                  />

                  <!-- Divider -->
                  <div class="border-t border-gray-200 my-2"></div>
                  <p class="text-xs font-semibold text-gray-700 mb-2">Card Colors</p>

                  <!-- Card Background & Border -->
                  <div class="grid grid-cols-2 gap-2">
                    <ElementorColorPicker
                      :model-value="card.cardBackgroundColor || 'rgba(255, 255, 255, 0.05)'"
                      @update:model-value="updateInfoCard(index, 'cardBackgroundColor', $event)"
                      label="Background"
                    />
                    <ElementorColorPicker
                      :model-value="card.cardBorderColor || 'rgba(255, 255, 255, 0.1)'"
                      @update:model-value="updateInfoCard(index, 'cardBorderColor', $event)"
                      label="Border"
                    />
                  </div>

                  <!-- Text Colors -->
                  <div class="space-y-2">
                    <ElementorColorPicker
                      :model-value="card.headerTextColor || '#9ca3af'"
                      @update:model-value="updateInfoCard(index, 'headerTextColor', $event)"
                      label="Header Text Color"
                    />
                    <ElementorColorPicker
                      :model-value="card.titleTextColor || '#ffffff'"
                      @update:model-value="updateInfoCard(index, 'titleTextColor', $event)"
                      label="Title Text Color"
                    />
                    <ElementorColorPicker
                      :model-value="card.descriptionTextColor || '#d1d5db'"
                      @update:model-value="updateInfoCard(index, 'descriptionTextColor', $event)"
                      label="Description Text Color"
                    />
                  </div>
                </div>
              </div>
            </template>

            <!-- Circular Image Options (shown when image type is selected) -->
            <template v-if="elementData.rightContentType === 'image'">
              <!-- Circle Image Upload -->
              <ElementorImageUpload
                :model-value="elementData.circleImage || ''"
                @update:model-value="updateData('circleImage', $event)"
                label="Circle Image"
                type="general"
              />

              <!-- Circle Image Size -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Image Size</label>
                <select
                  :value="elementData.circleImageSize || 'medium'"
                  @change="updateData('circleImageSize', ($event.target as HTMLSelectElement).value)"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="small">Small (250px)</option>
                  <option value="medium">Medium (350px)</option>
                  <option value="large">Large (450px)</option>
                  <option value="xlarge">Extra Large (550px)</option>
                </select>
              </div>

              <!-- Show Hover Overlay Toggle -->
              <div>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    :checked="elementData.showHoverOverlay !== false"
                    @change="updateData('showHoverOverlay', ($event.target as HTMLInputElement).checked)"
                    class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                  />
                  <span class="text-sm font-medium text-gray-700">Show Hover Overlay</span>
                </label>
              </div>

              <!-- Hover Overlay Color -->
              <ElementorColorPicker
                :model-value="elementData.hoverOverlayColor || 'rgba(147, 51, 234, 0.85)'"
                @update:model-value="updateData('hoverOverlayColor', $event)"
                label="Hover Overlay Color"
              />

              <!-- Hover Text -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Hover Text (Optional)</label>
                <input
                  type="text"
                  :value="elementData.hoverText"
                  @input="updateData('hoverText', ($event.target as HTMLInputElement).value)"
                  placeholder="Learn More"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </template>

            <!-- Feature Box Options (shown when text type is selected) -->
            <template v-else-if="elementData.rightContentType === 'text'">
              <!-- Feature Box Top Text -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feature Box Top Text</label>
                <input
                  type="text"
                  :value="elementData.featureBoxTopText"
                  @input="updateData('featureBoxTopText', ($event.target as HTMLInputElement).value)"
                  placeholder="FEATURE"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Feature Box Main Text -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feature Box Main Text</label>
                <input
                  type="text"
                  :value="elementData.featureBoxMainText"
                  @input="updateData('featureBoxMainText', ($event.target as HTMLInputElement).value)"
                  placeholder="MAIN TEXT"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Feature Box Bottom Text -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feature Box Bottom Text</label>
                <input
                  type="text"
                  :value="elementData.featureBoxBottomText"
                  @input="updateData('featureBoxBottomText', ($event.target as HTMLInputElement).value)"
                  placeholder="TAGLINE"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Feature Box Color -->
              <ElementorColorPicker
                :model-value="elementData.featureBoxColor || '#0D9488'"
                @update:model-value="updateData('featureBoxColor', $event)"
                label="Feature Box Color"
              />
            </template>
              </div>
            </details>
          </div>

          <div v-else-if="activeTab === 'Style'" class="space-y-4">
            <!-- Height -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Section Height</label>
              <select
                :value="elementData.height || 'large'"
                @change="updateData('height', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="medium">Medium (500-600px)</option>
                <option value="large">Large (600-700px)</option>
                <option value="full">Full Screen (700px+)</option>
                <option value="custom">Custom Height</option>
              </select>
            </div>

            <!-- Custom Height Slider -->
            <div v-if="elementData.height === 'custom'" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Custom Height: {{ elementData.customHeight || 600 }}px
              </label>
              <input
                type="range"
                :value="elementData.customHeight || 600"
                @input="updateData('customHeight', parseInt(($event.target as HTMLInputElement).value))"
                min="300"
                max="1000"
                step="10"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
              />
              <div class="flex justify-between text-xs text-gray-500">
                <span>300px</span>
                <span>650px</span>
                <span>1000px</span>
              </div>
            </div>

            <!-- Content Width -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Content Width</label>
              <select
                :value="elementData.width || 'full'"
                @change="updateData('width', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="small">Small (1024px)</option>
                <option value="medium">Medium (1280px)</option>
                <option value="large">Large (1536px)</option>
                <option value="full">Full Width</option>
                <option value="custom">Custom Width</option>
              </select>
            </div>

            <!-- Custom Width Slider -->
            <div v-if="elementData.width === 'custom'" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Custom Width: {{ elementData.customWidth || 1280 }}px
              </label>
              <input
                type="range"
                :value="elementData.customWidth || 1280"
                @input="updateData('customWidth', parseInt(($event.target as HTMLInputElement).value))"
                min="800"
                max="1920"
                step="20"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
              />
              <div class="flex justify-between text-xs text-gray-500">
                <span>800px</span>
                <span>1360px</span>
                <span>1920px</span>
              </div>
            </div>

            <!-- Show Decorations -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showDecorations"
                  @change="updateData('showDecorations', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Background Decorations</span>
              </label>
            </div>

            <!-- Background Image -->
            <ElementorImageUpload
              :model-value="elementData.backgroundImage || ''"
              @update:model-value="updateData('backgroundImage', $event)"
              label="Background Image (Optional)"
              type="general"
            />

            <!-- Divider -->
            <div class="border-t border-gray-200 my-4"></div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Background Overlay</h4>

            <!-- Overlay Color -->
            <ElementorColorPicker
              :model-value="elementData.overlayColor || '#000000'"
              @update:model-value="updateData('overlayColor', $event)"
              label="Overlay Color"
            />

            <!-- Overlay Opacity -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Overlay Opacity: {{ ((elementData.overlayOpacity !== undefined ? elementData.overlayOpacity : 0.5) * 100).toFixed(0) }}%
              </label>
              <input
                type="range"
                :value="elementData.overlayOpacity !== undefined ? elementData.overlayOpacity : 0.5"
                @input="updateData('overlayOpacity', parseFloat(($event.target as HTMLInputElement).value))"
                min="0"
                max="1"
                step="0.05"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
              />
              <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>0%</span>
                <span>50%</span>
                <span>100%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Widget -->
        <div v-else-if="widgetType === 'stats'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Stats Items -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Stats Items</label>
              <div class="space-y-3">
                <div
                  v-for="(item, index) in elementData.items"
                  :key="index"
                  class="p-3 border border-gray-200 rounded-lg"
                >
                  <div class="grid grid-cols-2 gap-2">
                    <input
                      type="text"
                      :value="item.value"
                      @input="updateStatsItem(index, 'value', ($event.target as HTMLInputElement).value)"
                      placeholder="Value"
                      class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <input
                      type="text"
                      :value="item.suffix"
                      @input="updateStatsItem(index, 'suffix', ($event.target as HTMLInputElement).value)"
                      placeholder="Suffix (e.g., +)"
                      class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <input
                    type="text"
                    :value="item.label"
                    @input="updateStatsItem(index, 'label', ($event.target as HTMLInputElement).value)"
                    placeholder="Label"
                    class="w-full mt-2 px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <button
                    v-if="elementData.items.length > 1"
                    @click="removeStatsItem(index)"
                    class="mt-2 text-xs text-red-600 hover:text-red-800"
                  >
                    Remove
                  </button>
                </div>
              </div>
              <button
                @click="addStatsItem"
                class="mt-2 text-sm text-blue-600 hover:text-blue-800"
              >
                + Add Stat
              </button>
            </div>

            <!-- Columns -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Columns</label>
              <select
                :value="elementData.columns || '4'"
                @change="updateData('columns', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="2">2 Columns</option>
                <option value="3">3 Columns</option>
                <option value="4">4 Columns</option>
              </select>
            </div>

            <!-- Animate -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.animate"
                  @change="updateData('animate', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Animate Numbers</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Card Grid Widget -->
        <div v-else-if="widgetType === 'card_grid'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Cards -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cards</label>
              <div class="space-y-3">
                <div
                  v-for="(card, index) in elementData.cards"
                  :key="index"
                  class="p-3 border border-gray-200 rounded-lg"
                >
                  <input
                    type="text"
                    :value="card.title"
                    @input="updateCardItem(index, 'title', ($event.target as HTMLInputElement).value)"
                    placeholder="Title"
                    class="w-full mb-2 px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <textarea
                    :value="card.description"
                    @input="updateCardItem(index, 'description', ($event.target as HTMLTextAreaElement).value)"
                    placeholder="Description"
                    rows="2"
                    class="w-full mb-2 px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  ></textarea>
                  <ElementorImageUpload
                    :model-value="card.image || ''"
                    @update:model-value="updateCardItem(index, 'image', $event)"
                    label="Image"
                    type="card"
                  />
                  <input
                    type="text"
                    :value="card.link"
                    @input="updateCardItem(index, 'link', ($event.target as HTMLInputElement).value)"
                    placeholder="Link URL (optional)"
                    class="w-full mb-2 px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <button
                    v-if="elementData.cards.length > 1"
                    @click="removeCardItem(index)"
                    class="text-xs text-red-600 hover:text-red-800"
                  >
                    Remove
                  </button>
                </div>
              </div>
              <button
                @click="addCardItem"
                class="mt-2 text-sm text-blue-600 hover:text-blue-800"
              >
                + Add Card
              </button>
            </div>

            <!-- Columns -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Columns</label>
              <select
                :value="elementData.columns || '3'"
                @change="updateData('columns', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="2">2 Columns</option>
                <option value="3">3 Columns</option>
                <option value="4">4 Columns</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Icon Cards Widget -->
        <div v-else-if="widgetType === 'icon_cards'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Section Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                placeholder="Our Mission & Vision"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Subheading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Section Subheading</label>
              <textarea
                :value="elementData.subheading"
                @input="updateData('subheading', ($event.target as HTMLTextAreaElement).value)"
                rows="2"
                placeholder="Optional subheading text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Cards -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cards</label>
              <div class="space-y-4">
                <div
                  v-for="(card, index) in elementData.cards"
                  :key="index"
                  class="p-4 border border-gray-200 rounded-lg bg-gray-50"
                >
                  <div class="space-y-3">
                    <!-- Icon -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                      <select
                        :value="card.icon || 'star'"
                        @input="updateIconCardItem(index, 'icon', ($event.target as HTMLSelectElement).value)"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="rocket">Rocket</option>
                        <option value="eye">Eye</option>
                        <option value="star">Star</option>
                        <option value="heart">Heart</option>
                        <option value="lightbulb">Lightbulb</option>
                        <option value="target">Target</option>
                        <option value="shield">Shield</option>
                        <option value="users">Users</option>
                      </select>
                    </div>

                    <!-- Icon Color -->
                    <ElementorColorPicker
                      :model-value="card.iconColor || '#3B82F6'"
                      @update:model-value="updateIconCardItem(index, 'iconColor', $event)"
                      label="Icon Color"
                    />

                    <!-- Border Color -->
                    <ElementorColorPicker
                      :model-value="card.borderColor || '#3B82F6'"
                      @update:model-value="updateIconCardItem(index, 'borderColor', $event)"
                      label="Border Color"
                    />

                    <!-- Title -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                      <input
                        type="text"
                        :value="card.title"
                        @input="updateIconCardItem(index, 'title', ($event.target as HTMLInputElement).value)"
                        placeholder="Our Mission"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <!-- Description -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                      <textarea
                        :value="card.description"
                        @input="updateIconCardItem(index, 'description', ($event.target as HTMLTextAreaElement).value)"
                        placeholder="Card description..."
                        rows="3"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      ></textarea>
                    </div>

                    <!-- Link -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Link URL (Optional)</label>
                      <input
                        type="text"
                        :value="card.link"
                        @input="updateIconCardItem(index, 'link', ($event.target as HTMLInputElement).value)"
                        placeholder="/about"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <!-- Link Text -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Link Text (Optional)</label>
                      <input
                        type="text"
                        :value="card.linkText"
                        @input="updateIconCardItem(index, 'linkText', ($event.target as HTMLInputElement).value)"
                        placeholder="Learn more"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <button
                      v-if="elementData.cards.length > 1"
                      @click="removeIconCardItem(index)"
                      class="text-xs text-red-600 hover:text-red-800 font-medium"
                    >
                      Remove Card
                    </button>
                  </div>
                </div>
              </div>
              <button
                @click="addIconCardItem"
                class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium"
              >
                + Add Card
              </button>
            </div>

            <!-- Columns -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Columns</label>
              <select
                :value="elementData.columns || '2'"
                @change="updateData('columns', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="1">1 Column</option>
                <option value="2">2 Columns</option>
                <option value="3">3 Columns</option>
                <option value="4">4 Columns</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Who We Are Widget -->
        <div v-else-if="widgetType === 'who_we_are'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Section Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                placeholder="Who We Are"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Heading Color -->
            <ElementorColorPicker
              :model-value="elementData.headingColor || '#1E40AF'"
              @update:model-value="updateData('headingColor', $event)"
              label="Heading Color"
            />

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea
                :value="elementData.description"
                @input="updateData('description', ($event.target as HTMLTextAreaElement).value)"
                rows="4"
                placeholder="Main description text..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Feature Title Color -->
            <ElementorColorPicker
              :model-value="elementData.featureTitleColor || '#1E40AF'"
              @update:model-value="updateData('featureTitleColor', $event)"
              label="Feature Title Color"
            />

            <!-- Feature Cards -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Feature Cards</label>
              <div class="space-y-3">
                <div
                  v-for="(feature, index) in elementData.features"
                  :key="index"
                  class="p-4 border border-gray-200 rounded-lg bg-gray-50"
                >
                  <div class="space-y-3">
                    <!-- Icon -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                      <select
                        :value="feature.icon || 'users'"
                        @input="updateWhoWeAreFeature(index, 'icon', ($event.target as HTMLSelectElement).value)"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="users">Users / Team</option>
                        <option value="shield">Shield / Security</option>
                        <option value="star">Star / Quality</option>
                        <option value="heart">Heart / Care</option>
                        <option value="globe">Globe / Global</option>
                        <option value="lightbulb">Lightbulb / Ideas</option>
                        <option value="target">Target / Goals</option>
                      </select>
                    </div>

                    <!-- Icon Color -->
                    <ElementorColorPicker
                      :model-value="feature.iconColor || '#1E40AF'"
                      @update:model-value="updateWhoWeAreFeature(index, 'iconColor', $event)"
                      label="Icon Color"
                    />

                    <!-- Icon Background Color -->
                    <ElementorColorPicker
                      :model-value="feature.iconBgColor || '#EBF5FF'"
                      @update:model-value="updateWhoWeAreFeature(index, 'iconBgColor', $event)"
                      label="Icon Background"
                    />

                    <!-- Title -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                      <input
                        type="text"
                        :value="feature.title"
                        @input="updateWhoWeAreFeature(index, 'title', ($event.target as HTMLInputElement).value)"
                        placeholder="Member-Driven"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <!-- Description -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                      <textarea
                        :value="feature.description"
                        @input="updateWhoWeAreFeature(index, 'description', ($event.target as HTMLTextAreaElement).value)"
                        placeholder="Feature description..."
                        rows="3"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      ></textarea>
                    </div>

                    <button
                      v-if="elementData.features.length > 1"
                      @click="removeWhoWeAreFeature(index)"
                      class="text-xs text-red-600 hover:text-red-800 font-medium"
                    >
                      Remove Feature
                    </button>
                  </div>
                </div>
              </div>
              <button
                @click="addWhoWeAreFeature"
                class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium"
              >
                + Add Feature
              </button>
            </div>

            <!-- Stat Cards -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Stat/Info Cards</label>
              <div class="space-y-3">
                <div
                  v-for="(card, index) in elementData.statCards"
                  :key="index"
                  class="p-4 border border-gray-200 rounded-lg bg-gray-50"
                >
                  <div class="space-y-3">
                    <!-- Label -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Label/Tag</label>
                      <input
                        type="text"
                        :value="card.label"
                        @input="updateWhoWeAreStatCard(index, 'label', ($event.target as HTMLInputElement).value)"
                        placeholder="Research"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <!-- Tag Box -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Tag Box Text</label>
                      <input
                        type="text"
                        :value="card.tagBox"
                        @input="updateWhoWeAreStatCard(index, 'tagBox', ($event.target as HTMLInputElement).value)"
                        placeholder="RESEARCH/EDUCATION/COLLABORATION"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <!-- Main Text -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Main Text</label>
                      <input
                        type="text"
                        :value="card.mainText"
                        @input="updateWhoWeAreStatCard(index, 'mainText', ($event.target as HTMLInputElement).value)"
                        placeholder="10Gbps"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <!-- Subtitle -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Subtitle</label>
                      <input
                        type="text"
                        :value="card.subtitle"
                        @input="updateWhoWeAreStatCard(index, 'subtitle', ($event.target as HTMLInputElement).value)"
                        placeholder="BACKBONE SPEED"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <!-- Description -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                      <textarea
                        :value="card.description"
                        @input="updateWhoWeAreStatCard(index, 'description', ($event.target as HTMLTextAreaElement).value)"
                        placeholder="Optional description"
                        rows="2"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      ></textarea>
                    </div>

                    <!-- Background Color -->
                    <ElementorColorPicker
                      :model-value="card.backgroundColor || '#1E40AF'"
                      @update:model-value="updateWhoWeAreStatCard(index, 'backgroundColor', $event)"
                      label="Background Color"
                    />

                    <!-- Background Image -->
                    <ElementorImageUpload
                      :model-value="card.backgroundImage || ''"
                      @update:model-value="updateWhoWeAreStatCard(index, 'backgroundImage', $event)"
                      label="Background Image (Optional)"
                      type="general"
                    />

                    <!-- Show Decorations -->
                    <div>
                      <label class="flex items-center gap-2 cursor-pointer">
                        <input
                          type="checkbox"
                          :checked="card.showDecorations || false"
                          @change="updateWhoWeAreStatCard(index, 'showDecorations', ($event.target as HTMLInputElement).checked)"
                          class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                        />
                        <span class="text-xs font-medium text-gray-700">Show Network Decorations</span>
                      </label>
                    </div>

                    <!-- Text Effect -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Text Effect</label>
                      <select
                        :value="card.textEffect || 'normal'"
                        @input="updateWhoWeAreStatCard(index, 'textEffect', ($event.target as HTMLSelectElement).value)"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="normal">Normal</option>
                        <option value="glitch">Glitch/Shadow Effect</option>
                      </select>
                    </div>

                    <!-- Card Size -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Card Size</label>
                      <select
                        :value="card.size || 'normal'"
                        @input="updateWhoWeAreStatCard(index, 'size', ($event.target as HTMLSelectElement).value)"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="normal">Normal (1 unit)</option>
                        <option value="large">Large (2 columns wide)</option>
                        <option value="tall">Tall (2 rows high)</option>
                        <option value="large-tall">Large & Tall (2x2)</option>
                      </select>
                    </div>

                    <button
                      v-if="elementData.statCards.length > 3"
                      @click="removeWhoWeAreStatCard(index)"
                      class="text-xs text-red-600 hover:text-red-800 font-medium"
                    >
                      Remove Card
                    </button>
                  </div>
                </div>
              </div>
              <button
                @click="addWhoWeAreStatCard"
                class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium"
              >
                + Add Stat Card
              </button>
            </div>
          </div>
        </div>

        <!-- Timeline Widget -->
        <div v-else-if="widgetType === 'timeline'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Section Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                placeholder="Our History"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Heading Color -->
            <ElementorColorPicker
              :model-value="elementData.headingColor || '#1E40AF'"
              @update:model-value="updateData('headingColor', $event)"
              label="Heading Color"
            />

            <!-- Timeline Items -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Timeline Items</label>
              <div class="space-y-4">
                <div
                  v-for="(item, index) in elementData.items"
                  :key="index"
                  class="p-4 border border-gray-200 rounded-lg bg-gray-50"
                >
                  <div class="space-y-3">
                    <!-- Year -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Year/Label</label>
                      <input
                        type="text"
                        :value="item.year"
                        @input="updateTimelineItem(index, 'year', ($event.target as HTMLInputElement).value)"
                        placeholder="2006"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <!-- Year Color -->
                    <ElementorColorPicker
                      :model-value="item.yearColor || '#1E40AF'"
                      @update:model-value="updateTimelineItem(index, 'yearColor', $event)"
                      label="Year Color"
                    />

                    <!-- Title -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                      <input
                        type="text"
                        :value="item.title"
                        @input="updateTimelineItem(index, 'title', ($event.target as HTMLInputElement).value)"
                        placeholder="Milestone Title"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>

                    <!-- Description -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                      <textarea
                        :value="item.description"
                        @input="updateTimelineItem(index, 'description', ($event.target as HTMLTextAreaElement).value)"
                        placeholder="Description of this milestone..."
                        rows="3"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      ></textarea>
                    </div>

                    <!-- Background Color -->
                    <ElementorColorPicker
                      :model-value="item.backgroundColor || '#E5E7EB'"
                      @update:model-value="updateTimelineItem(index, 'backgroundColor', $event)"
                      label="Background Color"
                    />

                    <!-- Card Size -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Card Size</label>
                      <select
                        :value="item.size || 'normal'"
                        @input="updateTimelineItem(index, 'size', ($event.target as HTMLSelectElement).value)"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="normal">Normal</option>
                        <option value="large">Large</option>
                        <option value="full">Full Width</option>
                      </select>
                    </div>

                    <!-- Icon -->
                    <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                      <select
                        :value="item.icon || 'none'"
                        @input="updateTimelineItem(index, 'icon', ($event.target as HTMLSelectElement).value)"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="none">None</option>
                        <option value="rocket">Rocket</option>
                        <option value="star">Star</option>
                        <option value="trophy">Trophy</option>
                        <option value="globe">Globe</option>
                        <option value="users">Users</option>
                        <option value="lightbulb">Lightbulb</option>
                      </select>
                    </div>

                    <!-- Show Icon Toggle -->
                    <div>
                      <label class="flex items-center gap-2 cursor-pointer">
                        <input
                          type="checkbox"
                          :checked="item.showIcon !== false"
                          @change="updateTimelineItem(index, 'showIcon', ($event.target as HTMLInputElement).checked)"
                          class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                        />
                        <span class="text-xs font-medium text-gray-700">Show Year Watermark</span>
                      </label>
                    </div>

                    <button
                      v-if="elementData.items.length > 1"
                      @click="removeTimelineItem(index)"
                      class="text-xs text-red-600 hover:text-red-800 font-medium"
                    >
                      Remove Item
                    </button>
                  </div>
                </div>
              </div>
              <button
                @click="addTimelineItem"
                class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium"
              >
                + Add Timeline Item
              </button>
            </div>
          </div>
        </div>

        <!-- Contact Form Widget -->
        <div v-else-if="widgetType === 'contact_form'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea
                :value="elementData.description"
                @input="updateData('description', ($event.target as HTMLTextAreaElement).value)"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Submit Text -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Submit Button Text</label>
              <input
                type="text"
                :value="elementData.submitText"
                @input="updateData('submitText', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Email To -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Send To Email</label>
              <input
                type="email"
                :value="elementData.emailTo"
                @input="updateData('emailTo', ($event.target as HTMLInputElement).value)"
                placeholder="info@example.com"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Show Phone -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showPhone"
                  @change="updateData('showPhone', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Phone Field</span>
              </label>
            </div>

            <!-- Show Company -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showCompany"
                  @change="updateData('showCompany', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Organization Field</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Search Box Widget -->
        <div v-else-if="widgetType === 'search_box'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Placeholder -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Placeholder Text</label>
              <input
                type="text"
                :value="elementData.placeholder"
                @input="updateData('placeholder', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Search Scope -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search Scope</label>
              <select
                :value="elementData.searchScope || 'all'"
                @change="updateData('searchScope', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="all">All Content</option>
                <option value="pages">Pages Only</option>
                <option value="news">News Only</option>
                <option value="events">Events Only</option>
              </select>
            </div>

            <!-- Size -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Size</label>
              <select
                :value="elementData.size || 'md'"
                @change="updateData('size', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="sm">Small</option>
                <option value="md">Medium</option>
                <option value="lg">Large</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Dynamic News Widget -->
        <div v-else-if="widgetType === 'dynamic_news'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Subheading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Subheading</label>
              <textarea
                :value="elementData.subheading"
                @input="updateData('subheading', ($event.target as HTMLTextAreaElement).value)"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Limit -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Number of Items</label>
              <input
                type="number"
                :value="elementData.limit"
                @input="updateData('limit', parseInt(($event.target as HTMLInputElement).value))"
                min="1"
                max="20"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Layout -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Layout</label>
              <select
                :value="elementData.layout || 'grid'"
                @change="updateData('layout', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="grid">Grid</option>
                <option value="list">List</option>
                <option value="carousel">Carousel</option>
              </select>
            </div>

            <!-- Columns (for grid layout) -->
            <div v-if="elementData.layout === 'grid' || !elementData.layout">
              <label class="block text-sm font-medium text-gray-700 mb-2">Columns</label>
              <select
                :value="elementData.columns || '3'"
                @change="updateData('columns', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="2">2 Columns</option>
                <option value="3">3 Columns</option>
                <option value="4">4 Columns</option>
              </select>
            </div>

            <!-- Show Image -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showImage"
                  @change="updateData('showImage', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Featured Image</span>
              </label>
            </div>

            <!-- Show Excerpt -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showExcerpt"
                  @change="updateData('showExcerpt', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Excerpt</span>
              </label>
            </div>

            <!-- Show Date -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showDate"
                  @change="updateData('showDate', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Date</span>
              </label>
            </div>

            <!-- Show Read More -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showReadMore"
                  @change="updateData('showReadMore', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Read More Link</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Dynamic Events Widget -->
        <div v-else-if="widgetType === 'dynamic_events'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Limit -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Number of Items</label>
              <input
                type="number"
                :value="elementData.limit"
                @input="updateData('limit', parseInt(($event.target as HTMLInputElement).value))"
                min="1"
                max="20"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Layout -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Layout</label>
              <select
                :value="elementData.layout || 'grid'"
                @change="updateData('layout', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="grid">Grid</option>
                <option value="list">List</option>
                <option value="timeline">Timeline</option>
              </select>
            </div>

            <!-- Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Show Events</label>
              <select
                :value="elementData.filter || 'upcoming'"
                @change="updateData('filter', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="upcoming">Upcoming Only</option>
                <option value="past">Past Only</option>
                <option value="all">All Events</option>
              </select>
            </div>

            <!-- Show Location -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showLocation"
                  @change="updateData('showLocation', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Location</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Dynamic Services Widget -->
        <div v-else-if="widgetType === 'dynamic_services'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Limit -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Number of Items</label>
              <input
                type="number"
                :value="elementData.limit"
                @input="updateData('limit', parseInt(($event.target as HTMLInputElement).value))"
                min="1"
                max="20"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Layout -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Layout</label>
              <select
                :value="elementData.layout || 'grid'"
                @change="updateData('layout', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="grid">Grid</option>
                <option value="list">List</option>
              </select>
            </div>

            <!-- Columns -->
            <div v-if="elementData.layout === 'grid' || !elementData.layout">
              <label class="block text-sm font-medium text-gray-700 mb-2">Columns</label>
              <select
                :value="elementData.columns || '3'"
                @change="updateData('columns', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="2">2 Columns</option>
                <option value="3">3 Columns</option>
                <option value="4">4 Columns</option>
              </select>
            </div>

            <!-- Show Image -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showIcons"
                  @change="updateData('showIcons', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Image</span>
              </label>
            </div>

            <!-- Show Excerpt -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showDescription"
                  @change="updateData('showDescription', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Description</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Dynamic Members Widget -->
        <div v-else-if="widgetType === 'dynamic_members'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Limit -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Number of Items</label>
              <input
                type="number"
                :value="elementData.limit"
                @input="updateData('limit', parseInt(($event.target as HTMLInputElement).value))"
                min="1"
                max="50"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Display Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Display Type</label>
              <select
                :value="elementData.display || 'grid'"
                @change="updateData('display', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="grid">Grid (with details)</option>
                <option value="logos">Logos Only</option>
                <option value="list">List</option>
                <option value="carousel">Carousel / Slider</option>
              </select>
            </div>

            <!-- Columns (only for grid and logos) -->
            <div v-if="(elementData.display === 'grid' || elementData.display === 'logos' || !elementData.display) && elementData.display !== 'carousel'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Columns</label>
              <select
                :value="elementData.columns || '4'"
                @change="updateData('columns', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="2">2 Columns</option>
                <option value="3">3 Columns</option>
                <option value="4">4 Columns</option>
                <option value="6">6 Columns</option>
              </select>
            </div>

            <!-- Show Logo -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showLogo"
                  @change="updateData('showLogo', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Logo</span>
              </label>
            </div>

            <!-- Show Description -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showDescription"
                  @change="updateData('showDescription', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Description</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Dynamic Team Members Widget -->
        <div v-else-if="widgetType === 'dynamic_team_members'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Subheading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Subheading</label>
              <textarea
                :value="elementData.subheading"
                @input="updateData('subheading', ($event.target as HTMLTextAreaElement).value)"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Limit -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Number of Team Members</label>
              <input
                type="number"
                :value="elementData.limit"
                @input="updateData('limit', parseInt(($event.target as HTMLInputElement).value))"
                min="1"
                max="20"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Layout -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Layout</label>
              <select
                :value="elementData.layout || 'grid'"
                @change="updateData('layout', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="grid">Grid</option>
                <option value="list">List</option>
              </select>
            </div>

            <!-- Columns -->
            <div v-if="elementData.layout === 'grid' || !elementData.layout">
              <label class="block text-sm font-medium text-gray-700 mb-2">Columns</label>
              <select
                :value="elementData.columns || '4'"
                @change="updateData('columns', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="2">2 Columns</option>
                <option value="3">3 Columns</option>
                <option value="4">4 Columns</option>
              </select>
            </div>

            <!-- Show Photo -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showPhoto"
                  @change="updateData('showPhoto', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Photos</span>
              </label>
            </div>

            <!-- Show Bio -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showBio"
                  @change="updateData('showBio', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Biography</span>
              </label>
            </div>

            <!-- Show Email -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showEmail"
                  @change="updateData('showEmail', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Email</span>
              </label>
            </div>

            <!-- Show Social Links -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showSocialLinks"
                  @change="updateData('showSocialLinks', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Social Media Links</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Dynamic Carousel Widget -->
        <div v-else-if="widgetType === 'dynamic_carousel'">
          <div v-if="activeTab === 'Content'" class="space-y-4">
            <!-- Content Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Content Type</label>
              <select
                :value="elementData.contentType || 'members'"
                @change="updateData('contentType', ($event.target as HTMLSelectElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="members">Members</option>
                <option value="services">Services</option>
                <option value="news">News/Posts</option>
                <option value="events">Events</option>
                <option value="team_members">Team Members</option>
              </select>
            </div>

            <!-- Heading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
              <input
                type="text"
                :value="elementData.heading"
                @input="updateData('heading', ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Subheading -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Subheading (Optional)</label>
              <textarea
                :value="elementData.subheading"
                @input="updateData('subheading', ($event.target as HTMLTextAreaElement).value)"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Limit -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Number of Items</label>
              <input
                type="number"
                :value="elementData.limit"
                @input="updateData('limit', parseInt(($event.target as HTMLInputElement).value))"
                min="1"
                max="50"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Items Per View -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Items Per View (Desktop)</label>
              <select
                :value="elementData.itemsPerView || 4"
                @change="updateData('itemsPerView', parseInt(($event.target as HTMLSelectElement).value))"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="2">2 Items</option>
                <option value="3">3 Items</option>
                <option value="4">4 Items</option>
                <option value="5">5 Items</option>
                <option value="6">6 Items</option>
              </select>
            </div>

            <!-- Autoplay -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.autoplay !== false"
                  @change="updateData('autoplay', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Enable Autoplay</span>
              </label>
            </div>

            <!-- Autoplay Delay -->
            <div v-if="elementData.autoplay !== false">
              <label class="block text-sm font-medium text-gray-700 mb-2">Autoplay Delay (ms)</label>
              <input
                type="number"
                :value="elementData.autoplayDelay || 3000"
                @input="updateData('autoplayDelay', parseInt(($event.target as HTMLInputElement).value))"
                min="1000"
                max="10000"
                step="500"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Show Navigation -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showNavigation !== false"
                  @change="updateData('showNavigation', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Navigation Arrows</span>
              </label>
            </div>

            <!-- Show Pagination -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="elementData.showPagination !== false"
                  @change="updateData('showPagination', ($event.target as HTMLInputElement).checked)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Show Pagination Dots</span>
              </label>
            </div>
          </div>

          <!-- Style Tab -->
          <div v-if="activeTab === 'Style'" class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Navigation Arrows</h3>

            <!-- Navigation Color -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Arrow Color</label>
              <input
                type="color"
                :value="elementData.navigationColor || '#0ea5e9'"
                @input="updateData('navigationColor', ($event.target as HTMLInputElement).value)"
                class="w-full h-10 border border-gray-300 rounded-lg cursor-pointer"
              />
            </div>

            <!-- Navigation Background Color -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Arrow Background</label>
              <input
                type="color"
                :value="elementData.navigationBgColor || '#ffffff'"
                @input="updateData('navigationBgColor', ($event.target as HTMLInputElement).value)"
                class="w-full h-10 border border-gray-300 rounded-lg cursor-pointer"
              />
            </div>

            <!-- Navigation Hover Color -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Arrow Hover Background</label>
              <input
                type="color"
                :value="elementData.navigationHoverColor || elementData.navigationColor || '#0ea5e9'"
                @input="updateData('navigationHoverColor', ($event.target as HTMLInputElement).value)"
                class="w-full h-10 border border-gray-300 rounded-lg cursor-pointer"
              />
            </div>

            <hr class="my-4 border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Pagination Dots</h3>

            <!-- Pagination Color -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Dot Color (Inactive)</label>
              <input
                type="color"
                :value="elementData.paginationColor || '#cbd5e1'"
                @input="updateData('paginationColor', ($event.target as HTMLInputElement).value)"
                class="w-full h-10 border border-gray-300 rounded-lg cursor-pointer"
              />
            </div>

            <!-- Pagination Active Color -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Dot Color (Active)</label>
              <input
                type="color"
                :value="elementData.paginationActiveColor || elementData.navigationColor || '#0ea5e9'"
                @input="updateData('paginationActiveColor', ($event.target as HTMLInputElement).value)"
                class="w-full h-10 border border-gray-300 rounded-lg cursor-pointer"
              />
            </div>

            <!-- Reset Colors Button -->
            <div class="pt-4">
              <button
                @click="resetCarouselColors"
                class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors"
              >
                Reset to Default Colors
              </button>
            </div>
          </div>
        </div>

        <!-- Hero Slider Widget -->
        <div v-else-if="widgetType === 'hero_slider'">
          <ElementorHeroSliderSettings
            :widget="currentWidget"
            @update="handleHeroSliderUpdate"
          />
        </div>

        <!-- Generic Widget (for widgets without specific settings) -->
        <div v-else>
          <div class="text-center py-8">
            <p class="text-sm text-gray-500">Settings for this widget are managed in the Filament admin panel.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  selectedElement: { type: 'section' | 'column' | 'widget'; id: string }
  sections: any[]
}>()

const emit = defineEmits(['update', 'close'])

const activeTab = ref('Content')
const availableTabs = computed(() => {
  if (props.selectedElement.type === 'section') {
    return ['Content', 'Style', 'Advanced']
  }
  return ['Content', 'Style']
})

// Get element data
const elementData = computed(() => {
  if (props.selectedElement.type === 'section') {
    const section = props.sections.find(s => s.id === props.selectedElement.id)
    return section?.settings || {}
  } else if (props.selectedElement.type === 'widget') {
    for (const section of props.sections) {
      for (const column of section.columns) {
        const widget = column.widgets.find((w: any) => w.id === props.selectedElement.id)
        if (widget) return widget.data || {}
      }
    }
  }
  return {}
})

const widgetType = computed(() => {
  if (props.selectedElement.type === 'widget') {
    for (const section of props.sections) {
      for (const column of section.columns) {
        const widget = column.widgets.find((w: any) => w.id === props.selectedElement.id)
        if (widget) return widget.type
      }
    }
  }
  return null
})

const currentWidget = computed(() => {
  if (props.selectedElement.type === 'widget') {
    for (const section of props.sections) {
      for (const column of section.columns) {
        const widget = column.widgets.find((w: any) => w.id === props.selectedElement.id)
        if (widget) return widget
      }
    }
  }
  return null
})

const elementTitle = computed(() => {
  if (props.selectedElement.type === 'section') {
    return 'Section'
  } else if (props.selectedElement.type === 'widget') {
    const { widgetLibrary } = useElementorEditor()
    const widgetDef = widgetLibrary.find(w => w.type === widgetType.value)
    return widgetDef?.label || 'Widget'
  }
  return 'Element'
})

const elementSubtitle = computed(() => {
  if (props.selectedElement.type === 'section') {
    return 'Configure section layout and styling'
  } else if (props.selectedElement.type === 'widget') {
    return 'Edit widget content and appearance'
  }
  return ''
})

// Update functions
const updateSetting = (key: string, value: any) => {
  emit('update', { [key]: value })
}

const updateData = (key: string, value: any) => {
  emit('update', { [key]: value })
}

const updatePadding = (side: string, value: string) => {
  const padding = { ...elementData.value.padding }
  padding[side] = parseInt(value) || 0
  emit('update', { padding })
}

const updateMargin = (side: string, value: string) => {
  const margin = { ...elementData.value.margin }
  margin[side] = parseInt(value) || 0
  emit('update', { margin })
}

const updateTypography = (key: string, value: any) => {
  const typography = { ...elementData.value.typography }
  typography[key] = value
  emit('update', { typography })
}

// Info Cards Management
const addInfoCard = () => {
  const infoCards = [...(elementData.value.infoCards || [])]
  infoCards.push({
    header: 'NEW CARD',
    title: 'Title',
    description: '',
    progress: undefined,
    progressLabel: '',
    progressValue: '',
    progressColor: '#14b8a6'
  })
  emit('update', { infoCards })
}

const removeInfoCard = (index: number) => {
  const infoCards = [...(elementData.value.infoCards || [])]
  infoCards.splice(index, 1)
  emit('update', { infoCards })
}

const updateInfoCard = (index: number, key: string, value: any) => {
  const infoCards = [...(elementData.value.infoCards || [])]
  infoCards[index] = { ...infoCards[index], [key]: value }
  emit('update', { infoCards })
}

const updateImageSize = (key: string, value: any) => {
  const width = { ...elementData.value.width }
  width[key] = value
  emit('update', { width })
}

const resetCarouselColors = () => {
  emit('update', {
    navigationColor: '#0ea5e9',
    navigationBgColor: '#ffffff',
    navigationHoverColor: '#0ea5e9',
    paginationColor: '#cbd5e1',
    paginationActiveColor: '#0ea5e9'
  })
}

const handleHeroSliderUpdate = (data: any) => {
  emit('update', data)
}

const updateSpaceSize = (key: string, value: any) => {
  const space = { ...elementData.value.space }
  space[key] = value
  emit('update', { space })
}

const updateWeightSize = (key: string, value: any) => {
  const weight = { ...elementData.value.weight }
  weight[key] = value
  emit('update', { weight })
}

// Helper functions for array manipulation
const updateStatsItem = (index: number, key: string, value: any) => {
  const items = [...elementData.value.items]
  items[index] = { ...items[index], [key]: value }
  emit('update', { items })
}

const addStatsItem = () => {
  const items = [...elementData.value.items]
  items.push({ value: '0', suffix: '', label: 'New Stat' })
  emit('update', { items })
}

const removeStatsItem = (index: number) => {
  const items = [...elementData.value.items]
  items.splice(index, 1)
  emit('update', { items })
}

const updateCardItem = (index: number, key: string, value: any) => {
  const cards = [...elementData.value.cards]
  cards[index] = { ...cards[index], [key]: value }
  emit('update', { cards })
}

const addCardItem = () => {
  const cards = [...elementData.value.cards]
  cards.push({ image: '', title: 'New Card', description: '', link: '' })
  emit('update', { cards })
}

const removeCardItem = (index: number) => {
  const cards = [...elementData.value.cards]
  cards.splice(index, 1)
  emit('update', { cards })
}

// Icon Cards helpers
const updateIconCardItem = (index: number, key: string, value: any) => {
  const cards = [...elementData.value.cards]
  cards[index] = { ...cards[index], [key]: value }
  emit('update', { cards })
}

const addIconCardItem = () => {
  const cards = [...elementData.value.cards]
  cards.push({
    icon: 'star',
    iconColor: '#3B82F6',
    borderColor: '#3B82F6',
    title: 'New Card',
    description: 'Card description goes here.',
    link: '',
    linkText: ''
  })
  emit('update', { cards })
}

const removeIconCardItem = (index: number) => {
  const cards = [...elementData.value.cards]
  cards.splice(index, 1)
  emit('update', { cards })
}

// Who We Are helpers
const updateWhoWeAreFeature = (index: number, key: string, value: any) => {
  const features = [...elementData.value.features]
  features[index] = { ...features[index], [key]: value }
  emit('update', { features })
}

const addWhoWeAreFeature = () => {
  const features = [...elementData.value.features]
  features.push({
    icon: 'users',
    iconColor: '#1E40AF',
    iconBgColor: '#EBF5FF',
    title: 'New Feature',
    description: 'Feature description...'
  })
  emit('update', { features })
}

const removeWhoWeAreFeature = (index: number) => {
  const features = [...elementData.value.features]
  features.splice(index, 1)
  emit('update', { features })
}

const updateWhoWeAreStatCard = (index: number, key: string, value: any) => {
  const statCards = [...elementData.value.statCards]
  statCards[index] = { ...statCards[index], [key]: value }
  emit('update', { statCards })
}

const addWhoWeAreStatCard = () => {
  const statCards = [...elementData.value.statCards]
  statCards.push({
    label: '',
    tagBox: '',
    mainText: 'New',
    subtitle: 'STAT',
    description: '',
    backgroundColor: '#1E40AF',
    backgroundImage: '',
    showDecorations: false,
    textEffect: 'normal',
    size: 'normal'
  })
  emit('update', { statCards })
}

const removeWhoWeAreStatCard = (index: number) => {
  const statCards = [...elementData.value.statCards]
  statCards.splice(index, 1)
  emit('update', { statCards })
}

// Timeline helpers
const updateTimelineItem = (index: number, key: string, value: any) => {
  const items = [...elementData.value.items]
  items[index] = { ...items[index], [key]: value }
  emit('update', { items })
}

const addTimelineItem = () => {
  const items = [...elementData.value.items]
  items.push({
    year: new Date().getFullYear().toString(),
    yearColor: '#1E40AF',
    title: 'New Milestone',
    description: 'Description of this milestone...',
    backgroundColor: '#E5E7EB',
    size: 'normal',
    icon: 'none',
    showIcon: true
  })
  emit('update', { items })
}

const removeTimelineItem = (index: number) => {
  const items = [...elementData.value.items]
  items.splice(index, 1)
  emit('update', { items })
}

// Reset active tab when element changes
watch(() => props.selectedElement, () => {
  activeTab.value = 'Content'
})
</script>
