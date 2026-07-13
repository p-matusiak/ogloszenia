<script setup lang="ts">
import Select from 'primevue/select'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'

import { useCategoryStore } from '@/stores/categories'
import type { Category } from '@/types/api'

const props = defineProps<{ modelValue: number | null; invalid?: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [id: number | null] }>()

const categories = useCategoryStore()
const { roots } = storeToRefs(categories)

const PLACEHOLDER = 'Wybierz lub wyszukaj kategorię'
const SEPARATOR = ' › '

interface CategoryOption {
  id: number
  name: string
  /** „Elektronika › Telefony › ” — przodkowie zakończeni separatorem, albo ''. */
  parents: string
  /** Pełna ścieżka z korzeniem; po niej filtruje wyszukiwarka. */
  fullPath: string
  /** Ścieżka bez korzenia: w liście korzeń jest już nagłówkiem grupy. */
  subPath: string
}

interface CategoryGroup {
  label: string
  items: CategoryOption[]
}

/**
 * StoreAdRequest odrzuca kategorię, która ma dzieci („Choose a subcategory”),
 * więc do wyboru trafiają wyłącznie liście — na dowolnej głębokości drzewa.
 */
function leavesOf(node: Category, ancestors: string[]): CategoryOption[] {
  const children = node.children ?? []

  if (children.length === 0) {
    return [
      {
        id: node.id,
        name: node.name,
        parents: ancestors.length > 0 ? ancestors.join(SEPARATOR) + SEPARATOR : '',
        fullPath: [...ancestors, node.name].join(SEPARATOR),
        subPath: ancestors.slice(1).join(SEPARATOR),
      },
    ]
  }

  return children.flatMap((child) => leavesOf(child, [...ancestors, node.name]))
}

const groups = computed<CategoryGroup[]>(() =>
  roots.value
    .map((root) => ({ label: root.name, items: leavesOf(root, []) }))
    .filter((group) => group.items.length > 0),
)

const selected = computed<CategoryOption | null>(() => {
  if (props.modelValue === null) {
    return null
  }

  for (const group of groups.value) {
    const match = group.items.find((item) => item.id === props.modelValue)
    if (match) {
      return match
    }
  }

  return null
})
</script>

<template>
  <Select
    :model-value="modelValue"
    :options="groups"
    option-group-label="label"
    option-group-children="items"
    option-label="name"
    option-value="id"
    :invalid="invalid"
    :loading="categories.isLoading"
    filter
    auto-filter-focus
    reset-filter-on-hide
    :filter-fields="['fullPath']"
    filter-placeholder="Szukaj kategorii"
    empty-filter-message="Brak pasującej kategorii"
    :placeholder="PLACEHOLDER"
    aria-label="Kategoria"
    fluid
    @update:model-value="emit('update:modelValue', $event)"
  >
    <template #value>
      <!-- Zamknięte pole pokazuje pełną ścieżkę: sama nazwa liścia bywa
           niejednoznaczna, „Akcesoria” wiszą pod kilkoma korzeniami. -->
      <span
        v-if="selected"
        class="picker__value"
      >
        <span class="picker__parents">{{ selected.parents }}</span>{{ selected.name }}
      </span>
      <span
        v-else
        class="picker__placeholder"
      >{{ PLACEHOLDER }}</span>
    </template>

    <template #option="{ option }">
      <span class="picker__option">
        <span>{{ option.name }}</span>
        <span
          v-if="option.subPath"
          class="picker__option-path"
        >{{ option.subPath }}</span>
      </span>
    </template>
  </Select>
</template>

<style scoped>
.picker__value {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.picker__parents {
  color: var(--text-muted);
}

.picker__placeholder {
  color: var(--text-muted);
}

.picker__option {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.picker__option-path {
  font-size: 0.7rem;
  color: var(--text-muted);
}
</style>
