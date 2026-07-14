import { mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import { describe, expect, it, vi } from 'vitest'

import FilterSidebar from '@/components/filters/FilterSidebar.vue'
import { createTestI18n } from '@/testing/i18n'
import type { AdFilters } from '@/types/api'

vi.mock('@/composables/useListingGeoFilters', async () => {
  const actual = await vi.importActual<typeof import('@/composables/useListingGeoFilters')>(
    '@/composables/useListingGeoFilters',
  )

  return {
    ...actual,
    buildLocationFilters: vi.fn((label?: string, lat?: number, lng?: number) => {
      if (label === undefined || label === '') {
        return Promise.resolve(actual.clearGeoFilters())
      }

      if (lat !== undefined && lng !== undefined) {
        return Promise.resolve(actual.geoFiltersFromSelection({ label, lat, lng }))
      }

      return Promise.resolve({
        location: label,
        lat: undefined,
        lng: undefined,
        radius_km: undefined,
      })
    }),
  }
})

/** Drzewo kategorii żyje na sklepie Pinia; tutaj liczy się tylko moment emisji. */
const CategoryTreeFilterStub = {
  props: ['category'],
  template: '<div />',
}

function mountSidebar(filters: AdFilters = {}, resultCount: number | null = 12) {
  return mount(FilterSidebar, {
    props: { filters, hasActiveFilters: false, resultCount },
    global: {
      plugins: [PrimeVue, createTestI18n()],
      stubs: { CategoryTreeFilter: CategoryTreeFilterStub },
    },
  })
}

type Sidebar = ReturnType<typeof mountSidebar>

function lastPatch(wrapper: Sidebar): Partial<AdFilters> | undefined {
  return wrapper.emitted('change')?.at(-1)?.[0] as Partial<AdFilters> | undefined
}

function submitLabel(wrapper: Sidebar): string {
  return wrapper.find('.sidebar__submit').text()
}

describe('FilterSidebar', () => {
  it('nie szuka przy zaznaczaniu — dopiero „Pokaż wyniki” wysyła kryteria', async () => {
    const wrapper = mountSidebar()

    await wrapper.find('#filter-free').setValue(true)
    await wrapper.find('#filter-negotiable').setValue(true)

    expect(wrapper.emitted('change')).toBeUndefined()

    await wrapper.find('.sidebar__submit').trigger('click')

    expect(wrapper.emitted('change')).toHaveLength(1)
    expect(lastPatch(wrapper)).toMatchObject({ free: true, negotiable: true })
  })

  it('Enter w polu lokalizacji zatwierdza filtry', async () => {
    const wrapper = mountSidebar()
    const location = wrapper.find('input[aria-label="Lokalizacja"]')

    await location.setValue('Kraków')

    expect(wrapper.emitted('change')).toBeUndefined()

    await location.trigger('keyup.enter')

    expect(lastPatch(wrapper)).toMatchObject({ location: 'Kraków' })
  })

  it('Enter w polu ceny zatwierdza filtry', async () => {
    const wrapper = mountSidebar()
    const from = wrapper.find('input[aria-label="Cena od"]')

    // InputNumber przepisuje wartość dopiero z klawiszy, nie z podmiany `value`,
    // a `modelValue` zatwierdza na `keydown` Entera — stąd pełna sekwencja.
    for (const key of ['1', '0', '0']) {
      await from.trigger('keypress', { key })
    }

    expect(wrapper.emitted('change')).toBeUndefined()

    await from.trigger('keydown', { key: 'Enter' })
    await from.trigger('keyup.enter')

    expect(lastPatch(wrapper)).toMatchObject({ price_min: 100 })
  })

  it('kilka zaznaczeń jedzie w jednej łatce, nie w kilku zapytaniach', async () => {
    const wrapper = mountSidebar()
    const checkboxes = wrapper.findAll('.group__item input')

    await checkboxes[0]!.setValue(true)
    await checkboxes[1]!.setValue(true)
    await wrapper.find('.sidebar__submit').trigger('click')

    expect(wrapper.emitted('change')).toHaveLength(1)
    expect(lastPatch(wrapper)?.delivery).toBe('personal,courier')
  })

  it('licznik znika z przycisku, gdy szkic wyprzedza zastosowane filtry', async () => {
    const wrapper = mountSidebar()

    expect(submitLabel(wrapper)).toBe('Pokaż wyniki (12)')

    await wrapper.find('#filter-free').setValue(true)

    expect(submitLabel(wrapper)).toBe('Pokaż wyniki')
  })

  it('ukrywa lokalizację, gdy showLocationFilter jest wyłączone', async () => {
    const wrapper = mountSidebar({ q: 'rower' }, 12)

    await wrapper.setProps({ showLocationFilter: false })

    expect(wrapper.text()).not.toContain('Lokalizacja')
    expect(wrapper.find('input[aria-label="Lokalizacja"]').exists()).toBe(false)
  })

  it('nawigacja z zewnątrz („wstecz”, „Wyczyść”) przestawia szkic', async () => {
    const wrapper = mountSidebar({ free: true, location: 'Kraków' })

    expect(submitLabel(wrapper)).toBe('Pokaż wyniki (12)')

    await wrapper.setProps({ filters: {} })

    expect((wrapper.find('#filter-free').element as HTMLInputElement).checked).toBe(false)
    expect((wrapper.find('input[aria-label="Lokalizacja"]').element as HTMLInputElement).value).toBe('')
    expect(submitLabel(wrapper)).toBe('Pokaż wyniki (12)')
  })
})
