import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import DeliveryMethodIcons from '@/components/ads/DeliveryMethodIcons.vue'
import { createTestI18n } from '@/testing/i18n'

const tooltipStub = {
  mounted(): void {},
  updated(): void {},
  unmounted(): void {},
}

function mountIcons(methods: Array<'personal' | 'courier' | 'parcel_locker'>) {
  return mount(DeliveryMethodIcons, {
    props: { methods },
    global: {
      plugins: [createTestI18n()],
      directives: { tooltip: tooltipStub },
    },
  })
}

describe('DeliveryMethodIcons', () => {
  it('renders icons without delivery text labels', () => {
    const wrapper = mountIcons(['parcel_locker', 'courier', 'personal'])

    expect(wrapper.text()).toBe('')
    expect(wrapper.findAll('.delivery-icons__item')).toHaveLength(3)
    expect(wrapper.find('.pi-box').exists()).toBe(true)
    expect(wrapper.find('.pi-truck').exists()).toBe(true)
    expect(wrapper.find('.pi-home').exists()).toBe(true)
  })

  it('podaje etykietę metody dostawy pod tooltip i aria-label', () => {
    const wrapper = mountIcons(['parcel_locker', 'courier', 'personal'])

    const labels = wrapper.findAll('.delivery-icons__item').map((item) => item.attributes('aria-label'))
    expect(labels).toEqual(['Odbiór osobisty', 'Kurier', 'Paczkomat'])
  })

  it('renders nothing when there are no delivery methods', () => {
    const wrapper = mount(DeliveryMethodIcons, {
      props: { methods: [] },
      global: {
        plugins: [createTestI18n()],
        directives: { tooltip: tooltipStub },
      },
    })

    expect(wrapper.find('.delivery-icons').exists()).toBe(false)
  })
})