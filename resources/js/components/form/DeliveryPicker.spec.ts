import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { defineComponent, h, ref } from 'vue'

import DeliveryPicker from '@/components/form/DeliveryPicker.vue'
import type { DeliveryMethod } from '@/types/api'

type Prices = Partial<Record<DeliveryMethod, string>>
interface Change {
  methods: DeliveryMethod[]
  prices: Prices
}

function mountPicker(methods: DeliveryMethod[] = [], prices: Prices = {}) {
  return mount(DeliveryPicker, { props: { methods, prices } })
}

/** Etykiety wierszy z cenami, w kolejności renderowania. */
function priceRows(wrapper: ReturnType<typeof mountPicker>): string[] {
  return wrapper.findAll('.prices__row .prices__name').map((row) => row.text())
}

function lastChange(wrapper: ReturnType<typeof mountPicker>): Change | undefined {
  return wrapper.emitted('change')?.at(-1)?.[0] as Change | undefined
}

describe('DeliveryPicker', () => {
  it('nie pokazuje żadnego pola ceny, zanim wybierzemy formę dostawy', () => {
    const wrapper = mountPicker()

    expect(priceRows(wrapper)).toEqual([])
    expect(wrapper.find('.prices__empty').text()).toBe('Zaznacz formę dostawy, żeby podać jej koszt.')
  })

  it('odsłania pole ceny dopiero dla zaznaczonej formy', () => {
    const wrapper = mountPicker(['courier'])

    expect(priceRows(wrapper)).toEqual(['Kurier'])
    expect(wrapper.find('.prices__empty').exists()).toBe(false)
  })

  it('nie pyta o cenę odbioru osobistego', () => {
    const wrapper = mountPicker(['personal'])

    expect(priceRows(wrapper)).toEqual([])
    expect(wrapper.find('.prices__empty').text()).toBe('Odbiór osobisty nie ma kosztu dostawy.')
  })

  it('pokazuje pola cen w kolejności DELIVERY_ORDER, nie klikania', () => {
    const wrapper = mountPicker(['post', 'courier', 'parcel_locker'])

    expect(priceRows(wrapper)).toEqual(['Kurier', 'Paczkomat', 'Poczta'])
  })

  it('usuwa cenę odbioru osobistego przyniesioną z zapisanego ogłoszenia', () => {
    const wrapper = mountPicker(['personal', 'courier'], { personal: '10.00', courier: '15.00' })

    expect(lastChange(wrapper)).toEqual({
      methods: ['personal', 'courier'],
      prices: { courier: '15.00' },
    })
  })

  it('nie emituje niczego, gdy ceny są już czyste', () => {
    const wrapper = mountPicker(['courier'], { courier: '15.00' })

    expect(wrapper.emitted('change')).toBeUndefined()
  })

  it('zaznaczenie formy dopisuje ją w kolejności DELIVERY_ORDER', async () => {
    const wrapper = mountPicker(['post'])

    await wrapper.findAll('.option__input')[1]!.setValue(true)

    expect(lastChange(wrapper)?.methods).toEqual(['courier', 'post'])
  })

  it('odznaczenie formy kasuje razem z nią jej cenę, jednym zdarzeniem', async () => {
    const wrapper = mountPicker(['courier'], { courier: '15.00' })

    await wrapper.findAll('.option__input')[1]!.setValue(false)

    expect(wrapper.emitted('change')).toHaveLength(1)
    expect(lastChange(wrapper)).toEqual({ methods: [], prices: {} })
  })

  it('wpisana kwota trafia do rodzica pod kluczem swojej metody', async () => {
    const wrapper = mountPicker(['courier'])
    const major = wrapper.find('.prices__row input')

    ;(major.element as HTMLInputElement).value = '15'
    await major.trigger('input')

    expect(lastChange(wrapper)).toEqual({ methods: ['courier'], prices: { courier: '15.00' } })
  })

  it('skasowanie kwoty i wyjście z pola usuwa klucz zamiast wysyłać pusty ciąg', async () => {
    const wrapper = mountPicker(['courier'], { courier: '15.00' })
    const major = wrapper.find('.prices__row input')

    ;(major.element as HTMLInputElement).value = ''
    await major.trigger('input')
    await wrapper.find('.prices__input').trigger('focusout')

    expect(lastChange(wrapper)).toEqual({ methods: ['courier'], prices: {} })
  })
})

/**
 * Rodzic składa łatkę na propie, który odświeża się dopiero przy przerysowaniu
 * — dokładnie tak działa `patch()` w AdForm. Dwa osobne zdarzenia z dziecka
 * gubiły się tutaj i odznaczenie formy dostawy nie działało.
 */
describe('DeliveryPicker w realistycznym rodzicu', () => {
  const Parent = defineComponent({
    setup() {
      const values = ref<Change>({ methods: ['courier'], prices: { courier: '15.00' } })

      return { values }
    },
    render() {
      return h(DeliveryPicker, {
        methods: this.values.methods,
        prices: this.values.prices,
        onChange: (next: Change) => {
          this.values = { ...this.values, ...next }
        },
      })
    },
  })

  it('odznaczenie formy naprawdę ją odznacza', async () => {
    const wrapper = mount(Parent)

    await wrapper.findAll('.option__input')[1]!.setValue(false)
    await wrapper.vm.$nextTick()

    expect(wrapper.vm.values).toEqual({ methods: [], prices: {} })
    expect((wrapper.findAll('.option__input')[1]!.element as HTMLInputElement).checked).toBe(false)
  })
})
