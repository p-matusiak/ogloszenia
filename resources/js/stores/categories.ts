import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { fetchCategoryTree } from '@/api/modules/v1/categories'
import type { Category } from '@/types/api'

export const useCategoryStore = defineStore('categories', () => {
  const roots = ref<Category[]>([])
  const isLoading = ref(false)
  const isLoaded = ref(false)

  const hasCategories = computed(() => roots.value.length > 0)

  /** Cached for the session: the tree changes only when an admin edits it. */
  async function load(): Promise<void> {
    if (isLoaded.value || isLoading.value) {
      return
    }

    isLoading.value = true
    try {
      roots.value = await fetchCategoryTree()
      isLoaded.value = true
    } finally {
      isLoading.value = false
    }
  }

  function childrenOf(rootSlug: string | null): Category[] {
    if (rootSlug === null) {
      return []
    }

    return findBySlug(rootSlug)?.children ?? []
  }

  function findBySlug(slug: string): Category | null {
    return pathOf(slug).at(-1) ?? null
  }

  /**
   * Ścieżka od korzenia do węzła, np. [Elektronika, Komputery]. Zasila okruszki
   * na stronie kategorii i etykietę chipa filtra — oba muszą pokazać pełną
   * ścieżkę, choć adres niesie już tylko slug najgłębszego węzła.
   */
  function pathOf(slug: string): Category[] {
    return findPath(roots.value, slug) ?? []
  }

  return { roots, isLoading, isLoaded, hasCategories, load, childrenOf, findBySlug, pathOf }
})

function findPath(nodes: Category[], slug: string): Category[] | null {
  for (const node of nodes) {
    if (node.slug === slug) {
      return [node]
    }

    const rest = findPath(node.children ?? [], slug)
    if (rest !== null) {
      return [node, ...rest]
    }
  }

  return null
}
