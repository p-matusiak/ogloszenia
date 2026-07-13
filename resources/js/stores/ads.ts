import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { errorMessage } from '@/api/client'
import { fetchAds } from '@/api/modules/v1/ads'
import type { AdFilters, AdSummary, PaginationMeta } from '@/types/api'

export const useAdsStore = defineStore('ads', () => {
  const ads = ref<AdSummary[]>([])
  const meta = ref<PaginationMeta | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isEmpty = computed(() => !isLoading.value && error.value === null && ads.value.length === 0)

  /**
   * Guards against a slow first request overwriting a faster later one when the
   * visitor types quickly in the search box.
   */
  let latestRequestId = 0

  async function search(filters: AdFilters): Promise<void> {
    const requestId = ++latestRequestId

    isLoading.value = true
    error.value = null

    try {
      const page = await fetchAds(filters)

      if (requestId !== latestRequestId) {
        return
      }

      ads.value = page.data
      meta.value = page.meta
    } catch (caught: unknown) {
      if (requestId !== latestRequestId) {
        return
      }

      error.value = errorMessage(caught, 'Nie udało się pobrać ogłoszeń.')
      ads.value = []
      meta.value = null
    } finally {
      if (requestId === latestRequestId) {
        isLoading.value = false
      }
    }
  }

  return { ads, meta, isLoading, error, isEmpty, search }
})
