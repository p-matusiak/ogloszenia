import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import AdListItem from '@/components/ads/AdListItem.vue'
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
    location: 'Warszawa, Mokotów',
    district: null,
    status: 'active',
    published_at: '2026-07-01T10:00:00+00:00',
    expires_at: null,
    views_count: 3,
    ...overrides,
  }
}

function mountRow(ad: AdSummary) {
  return mount(AdListItem, {
    props: { ad },
    global: { stubs: { RouterLink: RouterLinkStub } },
  })
}

describe('AdListItem', () => {
  it('shows the title and the grouped price', () => {
    const wrapper = mountRow(makeAd())

    expect(wrapper.text()).toContain('Sprzedam rower')
    // Intl wstawia twardą spację jako separator tysięcy.
    expect(wrapper.text().replace(/[\s\u00a0\u202f]/g, ' ')).toContain('1 200')
  })

  it('says the price is negotiable when the ad has none', () => {
    // A missing price is not the same as a price of zero.
    expect(mountRow(makeAd({ price: null })).text()).toContain('Cena do uzgodnienia')
  })

  it('treats a price of zero as a real price', () => {
    expect(mountRow(makeAd({ price: 0 })).text()).not.toContain('Cena do uzgodnienia')
  })

  it('renders a placeholder when the ad has no photo', () => {
    const wrapper = mountRow(makeAd())

    expect(wrapper.find('img').exists()).toBe(false)
    expect(wrapper.find('[data-testid="image-placeholder"]').exists()).toBe(true)
  })

  it('renders the primary image lazily when there is one', () => {
    const wrapper = mountRow(makeAd({ primary_image_url: 'https://example.test/foto.jpg' }))

    expect(wrapper.find('img').attributes('src')).toBe('https://example.test/foto.jpg')
    expect(wrapper.find('img').attributes('loading')).toBe('lazy')
  })

  it('omits the location when the ad has none', () => {
    expect(mountRow(makeAd({ location: null })).find('[data-testid="location"]').exists()).toBe(
      false,
    )
  })

  it('shows the negotiable badge only when the seller allows it', () => {
    expect(mountRow(makeAd({ is_negotiable: false })).text()).not.toContain('Do negocjacji')
    expect(mountRow(makeAd({ is_negotiable: true })).text()).toContain('Do negocjacji')
  })

  it('joins the delivery methods into one badge, in Polish', () => {
    const wrapper = mountRow(makeAd({ delivery_methods: ['parcel_locker', 'courier', 'personal'] }))

    expect(wrapper.text()).toContain('Paczkomat, Kurier, Odbiór osobisty')
  })

  it('renders no delivery badge when the ad offers none', () => {
    expect(mountRow(makeAd({ delivery_methods: [] })).text()).not.toContain('Kurier')
  })

  it('appends the district to the city', () => {
    expect(mountRow(makeAd({ location: 'Warszawa', district: 'Mokotów' })).text()).toContain(
      'Warszawa, Mokotów',
    )
  })

  it('leaves no orphan comma when the district is missing', () => {
    expect(mountRow(makeAd({ location: 'Warszawa', district: null })).text()).not.toContain(
      'Warszawa,',
    )
  })
})
