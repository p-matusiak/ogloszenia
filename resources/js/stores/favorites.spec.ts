import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useFavoritesStore } from '@/stores/favorites'
import type { AdSummary, Paginated } from '@/types/api'

const addFavorite = vi.hoisted(() => vi.fn())
const removeFavorite = vi.hoisted(() => vi.fn())
const fetchFavoriteIds = vi.hoisted(() => vi.fn())
const fetchFavorites = vi.hoisted(() => vi.fn())

vi.mock('@/api/modules/v1/favorites', () => ({
  addFavorite,
  removeFavorite,
  fetchFavoriteIds,
  fetchFavorites,
}))

function page(ids: number[]): Paginated<AdSummary> {
  return {
    data: ids.map((id) => ({
      id,
      title: `Ad ${id}`,
      slug: `ad-${id}`,
      excerpt: '',
      price: null,
      is_negotiable: false,
      condition: null,
      delivery_methods: [],
      location: null,
      latitude: null,
      longitude: null,
      status: 'active',
      published_at: null,
      expires_at: null,
      views_count: 0,
    })),
    meta: { current_page: 1, last_page: 1, per_page: 20, total: ids.length },
  }
}

describe('favorites store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    addFavorite.mockReset().mockResolvedValue(undefined)
    removeFavorite.mockReset().mockResolvedValue(undefined)
    fetchFavoriteIds.mockReset().mockResolvedValue([])
    fetchFavorites.mockReset().mockResolvedValue(page([]))
  })

  it('loads the id set only once', async () => {
    fetchFavoriteIds.mockResolvedValue([1, 2])
    const store = useFavoritesStore()

    await store.ensureIds()
    await store.ensureIds()

    expect(fetchFavoriteIds).toHaveBeenCalledTimes(1)
    expect(store.isFavorited(1)).toBe(true)
    expect(store.isFavorited(3)).toBe(false)
  })

  it('adds a favourite when toggling an unmarked ad', async () => {
    const store = useFavoritesStore()

    await store.toggle({ id: 5, slug: 'ad-5' })

    expect(addFavorite).toHaveBeenCalledWith('ad-5')
    expect(store.isFavorited(5)).toBe(true)
  })

  it('removes a favourite when toggling a marked ad', async () => {
    fetchFavoriteIds.mockResolvedValue([5])
    const store = useFavoritesStore()
    await store.ensureIds()

    await store.toggle({ id: 5, slug: 'ad-5' })

    expect(removeFavorite).toHaveBeenCalledWith('ad-5')
    expect(store.isFavorited(5)).toBe(false)
  })

  it('loads the favourites list and marks its ids', async () => {
    fetchFavorites.mockResolvedValue(page([7, 8]))
    const store = useFavoritesStore()

    await store.loadFavorites()

    expect(store.favorites).toHaveLength(2)
    expect(store.isFavorited(7)).toBe(true)
  })

  it('clears everything on reset', async () => {
    fetchFavoriteIds.mockResolvedValue([1])
    const store = useFavoritesStore()
    await store.ensureIds()

    store.reset()

    expect(store.isFavorited(1)).toBe(false)
    expect(store.favorites).toHaveLength(0)
  })
})
