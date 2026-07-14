import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { fetchAds } from '@/api/modules/v1/ads'
import { useLandingPage } from '@/composables/useLandingPage'
import { useCategoryStore } from '@/stores/categories'
import type { AdSummary, Category } from '@/types/api'

vi.mock('@/api/modules/v1/ads', () => ({
  fetchAds: vi.fn(),
}))

const roots: Category[] = [
  {
    id: 1,
    parent_id: null,
    name: 'Motoryzacja',
    slug: 'motoryzacja',
    position: 0,
    is_visible: true,
    children: [],
  },
]

function ad(id: number): AdSummary {
  return {
    id,
    title: `Ad ${id}`,
    slug: `ad-${id}`,
    excerpt: 'Excerpt',
    price: 100,
    is_negotiable: false,
    condition: 'used',
    delivery_methods: [],
    location: 'Warszawa',
    latitude: null,
    longitude: null,
    status: 'active',
    published_at: '2026-07-14T00:00:00+00:00',
    expires_at: '2026-08-14T00:00:00+00:00',
    views_count: 1,
    primary_image_url: null,
  }
}

describe('useLandingPage', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.mocked(fetchAds).mockReset()

    const categories = useCategoryStore()
    categories.roots = roots
    categories.isLoaded = true
  })

  it('ładuje polecane ogłoszenia i liczniki kategorii', async () => {
    vi.mocked(fetchAds).mockImplementation((filters = {}) => {
      if (filters.category === 'motoryzacja') {
        return Promise.resolve({
          data: [ad(10)],
          meta: { current_page: 1, last_page: 1, per_page: 20, total: 42 },
          links: {},
        })
      }

      return Promise.resolve({
        data: [ad(1), ad(2), ad(3), ad(4), ad(5), ad(6)],
        meta: { current_page: 1, last_page: 1, per_page: 20, total: 5500 },
        links: {},
      })
    })

    vi.spyOn(useCategoryStore(), 'load').mockResolvedValue()

    const page = useLandingPage()
    await page.load()

    expect(page.featuredAds.value).toHaveLength(5)
    expect(page.totalAds.value).toBe(5500)
    expect(page.categoryTiles.value[0]?.count).toBe(42)
    expect(page.error.value).toBeNull()
  })
})