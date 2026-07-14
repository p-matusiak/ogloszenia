import { mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import { afterEach, describe, expect, it, vi } from 'vitest'

import LocationSearchField from '@/components/search/LocationSearchField.vue'

afterEach(() => {
  vi.restoreAllMocks()
})

describe('LocationSearchField', () => {
  it('nie czyści podpowiedzi, gdy rodzic odbija wpisywaną etykietę', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify([
          {
            display_name: 'Warszawa, Polska',
            lat: '52.2297',
            lon: '21.0122',
          },
        ]),
        { status: 200 },
      ),
    )

    const wrapper = mount(LocationSearchField, {
      props: { label: '' },
      global: { plugins: [PrimeVue] },
    })

    await wrapper.find('input').setValue('Warszawa')
    await vi.waitFor(() => expect(wrapper.findAll('.location-search__suggestion')).toHaveLength(1))

    await wrapper.setProps({ label: 'Warszawa' })

    expect(wrapper.findAll('.location-search__suggestion')).toHaveLength(1)
  })

  it('resolveSelection geokoduje etykietę bez współrzędnych', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify([
          {
            display_name: 'Warszawa, Polska',
            lat: '52.2297',
            lon: '21.0122',
          },
        ]),
        { status: 200 },
      ),
    )

    const wrapper = mount(LocationSearchField, {
      props: { label: '' },
      global: { plugins: [PrimeVue] },
    })

    await wrapper.find('input').setValue('Warszawa')

    await expect(wrapper.vm.resolveSelection()).resolves.toEqual({
      label: 'Warszawa, Polska',
      latitude: 52.2297,
      longitude: 21.0122,
    })
  })

  it('resolveSelection ignoruje domyślną etykietę „Cała Polska”', async () => {
    const wrapper = mount(LocationSearchField, {
      props: { label: 'Cała Polska' },
      global: { plugins: [PrimeVue] },
    })

    await expect(wrapper.vm.resolveSelection()).resolves.toEqual({
      label: '',
      latitude: undefined,
      longitude: undefined,
    })
  })

  it('czyści domyślną etykietę po focusie', async () => {
    const wrapper = mount(LocationSearchField, {
      props: { label: 'Cała Polska' },
      global: { plugins: [PrimeVue] },
    })

    await wrapper.find('input').trigger('focus')

    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('')
  })

  it('w trybie compact przyjmuje wpisywanie tekstu', async () => {
    const wrapper = mount(LocationSearchField, {
      props: { label: '', compact: true, fluid: true },
      global: { plugins: [PrimeVue] },
    })

    await wrapper.find('input').setValue('Warszawa')

    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('Warszawa')
    expect(wrapper.vm.getSelection().label).toBe('Warszawa')
  })

  it('zwraca aktualny szkic przez getSelection()', async () => {
    const wrapper = mount(LocationSearchField, {
      props: { label: '' },
      global: { plugins: [PrimeVue] },
    })

    await wrapper.find('input').setValue('Kraków')

    expect(wrapper.vm.getSelection()).toEqual({
      label: 'Kraków',
      latitude: undefined,
      longitude: undefined,
    })
  })
})