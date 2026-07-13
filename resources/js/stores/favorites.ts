import { defineStore } from 'pinia'
import { ref } from 'vue'

import { errorMessage } from '@/api/client'
import * as favoritesApi from '@/api/modules/v1/favorites'
import type { AdSummary, PaginationMeta } from '@/types/api'

export const useFavoritesStore = defineStore('favorites', () => {
  const ids = ref<Set<number>>(new Set())
  const favorites = ref<AdSummary[]>([])
  const meta = ref<PaginationMeta | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  /** Zbiór identyfikatorów ładujemy raz — serduszka na listach czytają go lokalnie. */
  let areIdsLoaded = false

  function isFavorited(adId: number): boolean {
    return ids.value.has(adId)
  }

  async function ensureIds(): Promise<void> {
    if (areIdsLoaded) {
      return
    }
    areIdsLoaded = true
    try {
      ids.value = new Set(await favoritesApi.fetchFavoriteIds())
    } catch {
      areIdsLoaded = false
    }
  }

  async function toggle(ad: { id: number; slug: string }): Promise<void> {
    if (isFavorited(ad.id)) {
      await favoritesApi.removeFavorite(ad.slug)
      ids.value.delete(ad.id)
    } else {
      await favoritesApi.addFavorite(ad.slug)
      ids.value.add(ad.id)
    }
    // Reassign so `.has()` readers re-evaluate — mutating a Set is not reactive.
    ids.value = new Set(ids.value)
  }

  async function loadFavorites(page = 1): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const result = await favoritesApi.fetchFavorites(page)
      favorites.value = result.data
      meta.value = result.meta
      ids.value = new Set([...ids.value, ...result.data.map((ad) => ad.id)])
      areIdsLoaded = true
    } catch (caught: unknown) {
      error.value = errorMessage(caught, 'Nie udało się pobrać ulubionych.')
      favorites.value = []
      meta.value = null
    } finally {
      isLoading.value = false
    }
  }

  function reset(): void {
    ids.value = new Set()
    favorites.value = []
    meta.value = null
    error.value = null
    areIdsLoaded = false
  }

  return { ids, favorites, meta, isLoading, error, isFavorited, ensureIds, toggle, loadFavorites, reset }
})
