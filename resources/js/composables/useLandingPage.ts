import { computed, ref, type ComputedRef, type Ref } from 'vue'

import { fetchAds } from '@/api/modules/v1/ads'
import { landingCategoryIcon } from '@/constants/landingCategoryIcons'
import { useCategoryStore } from '@/stores/categories'
import type { AdSummary, Category } from '@/types/api'

export type LandingCategoryTile = {
  category: Category
  icon: string
  count: number | null
}

const FEATURED_LIMIT = 5

export type UseLandingPageReturn = {
  featuredAds: Ref<AdSummary[]>
  totalAds: Ref<number | null>
  categoryTiles: ComputedRef<LandingCategoryTile[]>
  isLoading: Ref<boolean>
  error: Ref<string | null>
  load: () => Promise<void>
}

export function useLandingPage(): UseLandingPageReturn {
  const categories = useCategoryStore()

  const featuredAds = ref<AdSummary[]>([])
  const totalAds = ref<number | null>(null)
  const categoryCounts = ref<Record<string, number>>({})
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const categoryTiles = computed<LandingCategoryTile[]>(() =>
    categories.roots.map((category) => ({
      category,
      icon: landingCategoryIcon(category.slug),
      count: categoryCounts.value[category.slug] ?? null,
    })),
  )

  async function load(): Promise<void> {
    if (isLoading.value) {
      return
    }

    isLoading.value = true
    error.value = null

    try {
      await categories.load()

      const [featured, totals, ...counts] = await Promise.all([
        fetchAds({ sort: 'newest', page: 1 }),
        fetchAds({ page: 1 }),
        ...categories.roots.map((root) =>
          fetchAds({ category: root.slug, page: 1 }).then((page) => ({
            slug: root.slug,
            total: page.meta.total,
          })),
        ),
      ])

      featuredAds.value = featured.data.slice(0, FEATURED_LIMIT)
      totalAds.value = totals.meta.total

      const nextCounts: Record<string, number> = {}
      for (const entry of counts) {
        nextCounts[entry.slug] = entry.total
      }
      categoryCounts.value = nextCounts
    } catch {
      error.value = 'landing.loadError'
    } finally {
      isLoading.value = false
    }
  }

  return {
    featuredAds,
    totalAds,
    categoryTiles,
    isLoading,
    error,
    load,
  }
}