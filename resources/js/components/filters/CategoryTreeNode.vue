<script setup lang="ts">
import { computed, ref } from 'vue'

import type { Category } from '@/types/api'

const props = defineProps<{
  node: Category
  depth: number
  selectedSlug?: string
}>()

const emit = defineEmits<{ select: [node: Category] }>()

const children = computed(() => props.node.children ?? [])
const hasChildren = computed(() => children.value.length > 0)
const isSelected = computed(() => props.selectedSlug === props.node.slug)

/** Gałąź prowadząca do wybranego węzła jest rozwinięta od razu po wejściu z linku. */
const containsSelection = (node: Category): boolean =>
  node.slug === props.selectedSlug ||
  (node.children ?? []).some((child) => containsSelection(child))

const isExpanded = ref(hasChildren.value && containsSelection(props.node))

/** Liść jest wyborem, gałąź jest też wyborem — ale tylko liść dostaje checkbox. */
const showsCheckbox = computed(() => !hasChildren.value && props.depth > 0)
</script>

<template>
  <li class="node">
    <div class="node__row">
      <button
        v-if="hasChildren"
        type="button"
        class="node__toggle"
        :aria-expanded="isExpanded"
        :aria-label="`Rozwiń ${node.name}`"
        @click="isExpanded = !isExpanded"
      >
        <i :class="isExpanded ? 'pi pi-chevron-down' : 'pi pi-chevron-right'" />
      </button>
      <span
        v-else
        class="node__toggle node__toggle--empty"
      />

      <button
        type="button"
        class="node__label"
        :class="{ 'node__label--active': isSelected }"
        @click="emit('select', node)"
      >
        <span
          v-if="showsCheckbox"
          class="node__box"
          :class="{ 'node__box--checked': isSelected }"
          aria-hidden="true"
        >
          <i
            v-if="isSelected"
            class="pi pi-check"
          />
        </span>
        <i
          v-else
          class="node__folder"
          :class="isExpanded ? 'pi pi-folder-open' : 'pi pi-folder'"
        />
        {{ node.name }}
      </button>
    </div>

    <ul
      v-if="isExpanded && hasChildren"
      class="node__children"
    >
      <CategoryTreeNode
        v-for="child in children"
        :key="child.id"
        :node="child"
        :depth="depth + 1"
        :selected-slug="selectedSlug"
        @select="emit('select', $event)"
      />
    </ul>
  </li>
</template>

<style scoped>
.node,
.node__children {
  list-style: none;
}

.node__children {
  margin: 0;
  padding-left: 1.1rem;
}

.node__row {
  display: flex;
  align-items: center;
  gap: 0.125rem;
}

.node__toggle {
  display: grid;
  place-items: center;
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
  border: 0;
  background: transparent;
  color: var(--text-muted);
  font-size: 0.65rem;
  cursor: pointer;
}

.node__toggle--empty {
  cursor: default;
}

.node__label {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
  padding: 0.35rem 0.5rem;
  border: 0;
  border-radius: 0.375rem;
  background: transparent;
  color: inherit;
  font: inherit;
  font-size: 0.875rem;
  text-align: left;
  cursor: pointer;
}

.node__label:hover {
  background: var(--surface-muted);
}

.node__label--active {
  color: var(--p-primary-color);
  font-weight: 600;
  background: color-mix(in srgb, var(--p-primary-color) 10%, transparent);
}

.node__folder {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.node__box {
  display: grid;
  place-items: center;
  width: 1.05rem;
  height: 1.05rem;
  flex-shrink: 0;
  border: 1px solid var(--surface-border);
  border-radius: 0.25rem;
  font-size: 0.6rem;
}

.node__box--checked {
  background: var(--p-primary-color);
  border-color: var(--p-primary-color);
  color: #fff;
}
</style>
