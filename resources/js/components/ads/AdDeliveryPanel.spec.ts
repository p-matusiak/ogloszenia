import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import AdDeliveryPanel from '@/components/ads/AdDeliveryPanel.vue'
import { createTestI18n } from '@/testing/i18n'

function mountPanel(
  methods: Array<'personal' | 'courier' | 'parcel_locker'>,
  prices: Partial<Record<'personal' | 'courier' | 'parcel_locker', string>> = {},
) {
  return mount(AdDeliveryPanel, {
    props: { methods, prices },
    global: { plugins: [createTestI18n()] },
  })
}

describe('AdDeliveryPanel', () => {
  it('shows delivery labels and prices on the detail page', () => {
    const wrapper = mountPanel(['personal', 'courier'], { courier: '15.00' })

    expect(wrapper.text()).toContain('Dostawa i odbiór')
    expect(wrapper.text()).toContain('Odbiór osobisty')
    expect(wrapper.text()).toContain('Za darmo')
    expect(wrapper.text()).toContain('Kurier')
    expect(wrapper.text().replace(/[\s\u00a0\u202f]/g, ' ')).toContain('15 zł')
  })

  it('hides the section when the ad offers no delivery methods', () => {
    const wrapper = mountPanel([])

    expect(wrapper.find('section.delivery-panel').exists()).toBe(false)
  })

  it('does not render an unselected method even if a stray price exists for it', () => {
    const wrapper = mountPanel(['personal'], { courier: '15.00' })

    expect(wrapper.text()).toContain('Odbiór osobisty')
    expect(wrapper.text()).not.toContain('Kurier')
    expect(wrapper.text()).not.toContain('15 zł')
  })
})
