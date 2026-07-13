import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useAdsStore } from '@/stores/ads'
import type { AdSummary, Paginated } from '@/types/api'

const fetchAds = vi.hoisted(() => vi.fn())

vi.mock('@/api/modules/v1/ads', () => ({ fetchAds }))

function page(titles: string[]): Paginated<AdSummary> {
  return {
    data: titles.map((title, index) => ({
      id: index + 1,
      title,
      slug: `slug-${index}`,
      excerpt: '',
      price: null,
      is_negotiable: false,
      condition: null,
      delivery_methods: [],
      location: null,
      district: null,
      status: 'active',
      published_at: null,
      expires_at: null,
      views_count: 0,
    })),
    meta: { current_page: 1, last_page: 1, per_page: 20, total: titles.length },
  }
}

describe('ads store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    fetchAds.mockReset()
  })

  it('loads a page of ads', async () => {
    fetchAds.mockResolvedValue(page(['Rower']))
    const store = useAdsStore()

    await store.search({ q: 'rower' })

    expect(fetchAds).toHaveBeenCalledWith({ q: 'rower' })
    expect(store.ads).toHaveLength(1)
    expect(store.isLoading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('reports an empty result rather than an error', async () => {
    fetchAds.mockResolvedValue(page([]))
    const store = useAdsStore()

    await store.search({})

    expect(store.isEmpty).toBe(true)
  })

  it('surfaces a failure and clears stale results', async () => {
    fetchAds.mockRejectedValue(new Error('network down'))
    const store = useAdsStore()

    await store.search({})

    expect(store.error).toBe('Nie udało się pobrać ogłoszeń.')
    expect(store.ads).toEqual([])
    expect(store.isEmpty).toBe(false)
  })

  it('ignores a slow response that a newer search has superseded', async () => {
    let resolveSlow: ((value: Paginated<AdSummary>) => void) | undefined
    const slow = new Promise<Paginated<AdSummary>>((resolve) => {
      resolveSlow = resolve
    })

    fetchAds.mockReturnValueOnce(slow).mockResolvedValueOnce(page(['Nowe']))

    const store = useAdsStore()
    const first = store.search({ q: 'stare' })
    const second = store.search({ q: 'nowe' })

    await second
    resolveSlow?.(page(['Stare']))
    await first

    // The stale request must not overwrite the newer results.
    expect(store.ads.map((ad) => ad.title)).toEqual(['Nowe'])
    expect(store.isLoading).toBe(false)
  })
})
