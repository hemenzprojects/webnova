<script setup lang="ts">
import type { MenuItem } from '~/composables/useMenu'

interface Props {
  items: MenuItem[]
  mode?: 'desktop' | 'mobile' | 'dropdown'
  currentPath?: string
  depth?: number
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'desktop',
  currentPath: '',
  depth: 0
})

const route = useRoute()
const { isMenuItemActive, hasActiveChild } = useMenu()

// Get current path from route if not provided
const activePath = computed(() => props.currentPath || route.path)

// Track expanded state for mobile accordion
const expandedItems = ref<Set<number>>(new Set())

const toggleExpanded = (itemId: number) => {
  if (expandedItems.value.has(itemId)) {
    expandedItems.value.delete(itemId)
  } else {
    expandedItems.value.add(itemId)
  }
}

const isExpanded = (itemId: number) => expandedItems.value.has(itemId)

const isActive = (item: MenuItem) => isMenuItemActive(item, activePath.value)
const hasActiveDescendant = (item: MenuItem) => hasActiveChild(item, activePath.value)

const getLinkComponent = (item: MenuItem) => {
  // Category items are non-clickable
  if (item.type === 'category' || !item.url) {
    return 'div'
  }

  // External links use regular anchor
  if (item.url.startsWith('http://') || item.url.startsWith('https://') || item.open_in_new_tab) {
    return 'a'
  }

  // Internal links use NuxtLink
  return resolveComponent('NuxtLink')
}

const getLinkProps = (item: MenuItem) => {
  if (item.type === 'category' || !item.url) {
    return {}
  }

  if (item.url.startsWith('http://') || item.url.startsWith('https://') || item.open_in_new_tab) {
    return {
      href: item.url,
      target: item.open_in_new_tab ? '_blank' : '_self',
      rel: item.open_in_new_tab ? 'noopener noreferrer' : undefined
    }
  }

  return {
    to: item.url
  }
}

const hasChildren = (item: MenuItem) => item.children && item.children.length > 0
</script>

<template>
  <!-- Desktop Mode: Horizontal with hover dropdowns -->
  <template v-if="mode === 'desktop'">
    <ul :class="depth === 0 ? 'flex items-center space-x-1' : 'absolute left-0 top-full mt-2 min-w-[200px] bg-white shadow-lg rounded-md py-2 hidden group-hover:block z-50'">
      <li
        v-for="item in items"
        :key="item.id"
        :class="[
          'relative group',
          depth === 0 ? '' : 'w-full'
        ]"
      >
        <component
          :is="getLinkComponent(item)"
          v-bind="getLinkProps(item)"
          :class="[
            'flex items-center gap-2 px-4 py-2 transition-colors',
            depth === 0
              ? 'text-gray-700 hover:text-primary-600 font-medium rounded-md hover:bg-gray-50'
              : 'text-gray-600 hover:bg-gray-50 hover:text-primary-600',
            isActive(item) ? 'text-primary-600 font-semibold' : '',
            hasActiveDescendant(item) && !isActive(item) ? 'text-primary-500' : '',
            item.type === 'category' ? 'font-semibold text-gray-900 cursor-default' : 'cursor-pointer',
            item.css_class || ''
          ]"
          :aria-current="isActive(item) ? 'page' : undefined"
          :aria-haspopup="hasChildren(item) ? 'true' : undefined"
          :aria-expanded="hasChildren(item) ? 'false' : undefined"
        >
          <span v-if="item.icon" :class="item.icon" class="w-5 h-5" />
          <span>{{ item.label }}</span>
          <svg
            v-if="hasChildren(item)"
            class="w-4 h-4 ml-auto"
            :class="depth === 0 ? '' : 'rotate-[-90deg]'"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </component>

        <!-- Nested dropdown -->
        <MenuItems
          v-if="hasChildren(item)"
          :items="item.children!"
          mode="desktop"
          :current-path="activePath"
          :depth="depth + 1"
        />
      </li>
    </ul>
  </template>

  <!-- Mobile Mode: Vertical accordion -->
  <template v-else-if="mode === 'mobile'">
    <ul :class="depth === 0 ? 'space-y-1' : 'ml-4 mt-2 space-y-1 border-l-2 border-gray-200 pl-4'">
      <li
        v-for="item in items"
        :key="item.id"
      >
        <div class="flex items-center">
          <component
            :is="getLinkComponent(item)"
            v-bind="getLinkProps(item)"
            :class="[
              'flex-1 flex items-center gap-3 px-4 py-3 rounded-md transition-colors',
              isActive(item)
                ? 'bg-primary-50 text-primary-700 font-semibold'
                : 'text-gray-700 hover:bg-gray-50',
              hasActiveDescendant(item) && !isActive(item) ? 'text-primary-600' : '',
              item.type === 'category' ? 'font-semibold cursor-default' : '',
              item.css_class || ''
            ]"
            :aria-current="isActive(item) ? 'page' : undefined"
          >
            <span v-if="item.icon" :class="item.icon" class="w-5 h-5" />
            <span class="flex-1">{{ item.label }}</span>
          </component>

          <!-- Expand/collapse button for items with children -->
          <button
            v-if="hasChildren(item)"
            type="button"
            @click="toggleExpanded(item.id)"
            :class="[
              'p-2 rounded-md transition-colors',
              isExpanded(item.id) ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600'
            ]"
            :aria-expanded="isExpanded(item.id)"
            :aria-label="`${isExpanded(item.id) ? 'Collapse' : 'Expand'} ${item.label}`"
          >
            <svg
              class="w-5 h-5 transition-transform"
              :class="isExpanded(item.id) ? 'rotate-180' : ''"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>

        <!-- Nested items with accordion animation -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 max-h-0"
          enter-to-class="opacity-100 max-h-screen"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 max-h-screen"
          leave-to-class="opacity-0 max-h-0"
        >
          <MenuItems
            v-if="hasChildren(item) && isExpanded(item.id)"
            :items="item.children!"
            mode="mobile"
            :current-path="activePath"
            :depth="depth + 1"
          />
        </Transition>
      </li>
    </ul>
  </template>

  <!-- Dropdown Mode: Simple vertical list for nested dropdowns -->
  <template v-else-if="mode === 'dropdown'">
    <ul class="py-1">
      <li
        v-for="item in items"
        :key="item.id"
      >
        <component
          :is="getLinkComponent(item)"
          v-bind="getLinkProps(item)"
          :class="[
            'block px-4 py-2 text-sm transition-colors',
            isActive(item)
              ? 'bg-primary-50 text-primary-700 font-semibold'
              : 'text-gray-700 hover:bg-gray-50 hover:text-primary-600',
            item.type === 'category' ? 'font-semibold text-gray-900 cursor-default' : '',
            item.css_class || ''
          ]"
          :aria-current="isActive(item) ? 'page' : undefined"
        >
          <span v-if="item.icon" :class="item.icon" class="inline-block w-4 h-4 mr-2" />
          {{ item.label }}
        </component>

        <!-- Nested items -->
        <MenuItems
          v-if="hasChildren(item)"
          :items="item.children!"
          mode="dropdown"
          :current-path="activePath"
          :depth="depth + 1"
          class="ml-4"
        />
      </li>
    </ul>
  </template>
</template>
