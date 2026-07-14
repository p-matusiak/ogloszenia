import { createTestingPinia } from '@pinia/testing'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'

import AdDetailView from '@/views/AdDetailView.vue'
import { createTestI18n } from '@/testing/i18n'
import type { Ad } from '@/types/api'

vi.mock('vue-router', () => ({
  useRoute: () => ({ fullPath: '/ogloszenie/pierwsze-ogloszenie', query: {} }),
  useRouter: () => ({ push: vi.fn() }),
}))

const fetchAd = vi.hoisted(() => vi.fn())
const fetchMoreFromSeller = vi.hoisted(() => vi.fn())

vi.mock('@/api/modules/v1/ads', () => ({
  fetchAd,
  fetchMoreFromSeller,
}))

vi.mock('@/composables/usePageTitle', () => ({
  setDocumentTitle: vi.fn(),
}))

const RouterLinkStub = defineComponent({
  props: { to: { type: Object, required: true } },
  setup(props, { slots }) {
    return () => h('a', { 'data-to': JSON.stringify(props.to) }, slots.default?.())
  },
})

function makeAd(overrides: Partial<Ad> = {}): Ad {
  return {
    id: 1,
    title: 'Pierwsze ogłoszenie',
    slug: 'pierwsze-ogloszenie',
    description: 'Opis testowy.',
    price: 100,
    is_negotiable: false,
    condition: 'used',
    delivery_methods: ['personal', 'courier'],
    delivery_prices: { courier: '20.00' },
    location: 'Warszawa',
    latitude: 52.2297,
    longitude: 21.0122,
    status: 'active',
    rejection_reason: null,
    is_refreshable: false,
    published_at: '2026-07-01T10:00:00+00:00',
    expires_at: null,
    created_at: '2026-07-01T09:00:00+00:00',
    views_count: 1,
    images: [],
    category: {
      id: 10,
      parent_id: 1,
      name: 'Rowery',
      slug: 'rowery',
      position: 1,
      is_visible: true,
      ancestors: [
        {
          id: 1,
          parent_id: null,
          name: 'Sport',
          slug: 'sport',
          position: 0,
          is_visible: true,
        },
      ],
    },
    seller: { id: 2, name: 'Jan', slug: 'jan-kowalski', avatar_url: null, member_since: 2024 },
    has_phone: false,
    contact_phone_masked: null,
    is_own: false,
    ...overrides,
  }
}

function mountDetail(slug = 'pierwsze-ogloszenie') {
  const pinia = createTestingPinia({ createSpy: vi.fn })

  return mount(AdDetailView, {
    props: { slug },
    global: {
      plugins: [pinia, createTestI18n()],
      stubs: {
        RouterLink: RouterLinkStub,
        AdDetailSkeleton: true,
        AdGallery: true,
        FavoriteButton: true,
        SellerCard: true,
        AdMetaPanel: true,
        AdCard: true,
        Dialog: true,
        AdReportPanel: true,
        SendMessagePanel: true,
        Message: true,
      },
    },
  })
}

describe('AdDetailView', () => {
  beforeEach(() => {
    fetchAd.mockReset()
    fetchMoreFromSeller.mockReset()
  })

  it('pokazuje pełne informacje o dostawie w szczegółach', async () => {
    fetchAd.mockResolvedValueOnce(makeAd())
    fetchMoreFromSeller.mockResolvedValueOnce([])

    const wrapper = mountDetail()
    await flushPromises()

    expect(wrapper.text()).toContain('Dostawa i odbiór')
    expect(wrapper.text()).toContain('Odbiór osobisty')
    expect(wrapper.text()).toContain('Kurier')
  })

  it('linkuje breadcrumb kategorii do /kategoria/:slug', async () => {
    fetchAd.mockResolvedValueOnce(makeAd())
    fetchMoreFromSeller.mockResolvedValueOnce([])

    const wrapper = mountDetail()
    await flushPromises()

    const links = wrapper.findAll('a[data-to]')
    const categoryLinks = links
      .map((link) => JSON.parse(link.attributes('data-to') ?? '{}') as Record<string, unknown>)
      .filter((to) => to.name === 'categories.show')

    expect(categoryLinks).toEqual([
      { name: 'categories.show', params: { slug: 'sport' }, query: {} },
      { name: 'categories.show', params: { slug: 'rowery' }, query: {} },
    ])
  })

  it('przeładowuje ogłoszenie po zmianie sluga w tej samej trasie', async () => {
    fetchAd
      .mockResolvedValueOnce(makeAd())
      .mockResolvedValueOnce(makeAd({ id: 2, title: 'Drugie ogłoszenie', slug: 'drugie-ogloszenie' }))
    fetchMoreFromSeller.mockResolvedValue([])

    const wrapper = mountDetail()
    await flushPromises()

    expect(wrapper.text()).toContain('Pierwsze ogłoszenie')

    fetchAd.mockClear()
    fetchMoreFromSeller.mockClear()

    await wrapper.setProps({ slug: 'drugie-ogloszenie' })
    await flushPromises()

    expect(fetchAd).toHaveBeenCalledOnce()
    expect(fetchAd).toHaveBeenCalledWith('drugie-ogloszenie')
    expect(wrapper.text()).toContain('Drugie ogłoszenie')
  })
})