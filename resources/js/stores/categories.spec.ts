import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useCategoryStore } from '@/stores/categories'
import type { Category } from '@/types/api'

const fetchCategoryTree = vi.hoisted(() => vi.fn())

vi.mock('@/api/modules/v1/categories', () => ({ fetchCategoryTree }))

function node(id: number, slug: string, children: Category[] = []): Category {
  return { id, parent_id: null, name: slug, slug, position: 0, is_visible: true, children }
}

describe('categories store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    fetchCategoryTree.mockReset()
  })

  it('loads the tree once and caches it', async () => {
    fetchCategoryTree.mockResolvedValue([node(1, 'motoryzacja')])
    const store = useCategoryStore()

    await store.load()
    await store.load()

    expect(fetchCategoryTree).toHaveBeenCalledTimes(1)
    expect(store.hasCategories).toBe(true)
  })

  it('returns the children of a root and nothing for an unknown slug', async () => {
    fetchCategoryTree.mockResolvedValue([
      node(1, 'motoryzacja', [node(2, 'samochody')]),
      node(3, 'praca'),
    ])
    const store = useCategoryStore()
    await store.load()

    expect(store.childrenOf('motoryzacja').map((c) => c.slug)).toEqual(['samochody'])
    expect(store.childrenOf('praca')).toEqual([])
    expect(store.childrenOf('nie-ma')).toEqual([])
    expect(store.childrenOf(null)).toEqual([])
  })

  it('finds a node at either level of the tree', async () => {
    fetchCategoryTree.mockResolvedValue([node(1, 'motoryzacja', [node(2, 'samochody')])])
    const store = useCategoryStore()
    await store.load()

    expect(store.findBySlug('motoryzacja')?.id).toBe(1)
    expect(store.findBySlug('samochody')?.id).toBe(2)
    expect(store.findBySlug('nie-ma')).toBeNull()
  })
})
