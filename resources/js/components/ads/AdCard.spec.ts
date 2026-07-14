import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import AdCard from '@/components/ads/AdCard.vue'
import { createTestI18n } from '@/testing/i18n'
import type { AdSummary } from '@/types/api'

const RouterLinkStub = {
  props: ['to'],
  template: '<a><slot /></a>',
}

function makeAd(overrides: Partial<AdSummary> = {}): AdSummary {
  return {
    id: 1,
    title: 'Sprzedam rower',
    slug: 'sprzedam-rower',
    excerpt: 'Stan idealny.',
    price: 1200,
    is_negotiable: false,
    condition: 'used',
    delivery_methods: [],
    location: 'Warszawa',
    latitude: 52.2297,
    longitude: 21.0122,
    status: 'active',
    published_at: '2026-07-01T10:00:00+00:00',
    expires_at: null,
    views_count: 3,
    ...overrides,
  }
}

function mountCard(ad: AdSummary) {
  return mount(AdCard, {
    props: { ad },
    global: {
      plugins: [createTestI18n()],
      stubs: { RouterLink: RouterLinkStub },
    },
  })
}

describe('AdCard', () => {
  it('shows the title and the grouped price', () => {
    const wrapper = mountCard(makeAd())

    expect(wrapper.text()).toContain('Sprzedam rower')
    expect(wrapper.text().replace(/[\s\u00a0\u202f]/g, ' ')).toContain('1 200')
  })

  it('says the price is negotiable when the ad has none', () => {
    expect(mountCard(makeAd({ price: null })).text()).toContain('Cena do uzgodnienia')
  })

  it('treats a price of zero as a real price', () => {
    expect(mountCard(makeAd({ price: 0 })).text()).not.toContain('Cena do uzgodnienia')
  })

  it('renders a placeholder when the ad has no photo', () => {
    const wrapper = mountCard(makeAd())

    expect(wrapper.find('img').exists()).toBe(false)
    expect(wrapper.find('.card__image--empty').exists()).toBe(true)
  })

  it('renders the primary image lazily when there is one', () => {
    const wrapper = mountCard(makeAd({ primary_image_url: 'https://example.test/foto.jpg' }))

    expect(wrapper.find('img').attributes('src')).toBe('https://example.test/foto.jpg')
    expect(wrapper.find('img').attributes('loading')).toBe('lazy')
  })

  it('omits the location when the ad has none', () => {
    expect(mountCard(makeAd({ location: null })).find('[data-testid="location"]').exists()).toBe(
      false,
    )
  })

  it('shows delivery icons instead of text labels', () => {
    const wrapper = mountCard(makeAd({ delivery_methods: ['courier', 'post'] }))

    expect(wrapper.text()).not.toContain('Kurier')
    expect(wrapper.text()).not.toContain('Poczta')
    expect(wrapper.findAll('.delivery-icons__item')).toHaveLength(2)
  })

  it('links to the ad detail route by slug', () => {
    const wrapper = mountCard(makeAd({ slug: 'inny-ogloszenie' }))

    expect(wrapper.getComponent(RouterLinkStub).props('to')).toEqual({
      name: 'ads.show',
      params: { slug: 'inny-ogloszenie' },
    })
  })
})