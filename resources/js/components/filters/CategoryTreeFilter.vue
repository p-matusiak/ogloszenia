<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { computed } from 'vue'

import CategoryTreeNode from '@/components/filters/CategoryTreeNode.vue'
import { useCategoryStore } from '@/stores/categories'
import type { Category } from '@/types/api'

const props = defineProps<{ category?: string }>()

const emit = defineEmits<{
  select: [value: { category?: string }]
}>()

const categories = useCategoryStore()
const { roots } = storeToRefs(categories)

/**
 * Wybór to jeden slug dowolnej głębokości — backend rozwija go na całe poddrzewo.
 * Ponowne kliknięcie zaznaczonego węzła zdejmuje filtr.
 */
const selectedSlug = computed(() => props.category)

function select(node: Category): void {
  emit('select', props.category === node.slug ? {} : { category: node.slug })
}
</script>

<template>
  <ul class="tree">
    <CategoryTreeNode
      v-for="root in roots"
      :key="root.id"
      :node="root"
      :depth="0"
      :selected-slug="selectedSlug"
      @select="select"
    />
  </ul>
</template>

<style scoped>
.tree {
  list-style: none;
  margin: 0;
  padding: 0;
}
</style>
