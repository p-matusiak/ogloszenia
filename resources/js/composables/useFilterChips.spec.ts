import { ref } from 'vue'
import { describe, expect, it } from 'vitest'

import { useFilterChips } from '@/composables/useFilterChips'
import type { AdFilters } from '@/types/api'

const categories = {
  pathOf: (slug: string) => {
    const paths: Record<string, { name: string }[]> = {
      motoryzacja: [{ name: 'Motoryzacja' }],
      samochody: [{ name: 'Motoryzacja' }, { name: 'Samochody' }],
    }

    return paths[slug] ?? []
  },
}

describe('useFilterChips', () => {
  it('builds chips for active filters', () => {
    const filters = ref<AdFilters>({
      q: 'rower',
      category: 'samochody',
      location: 'Warszawa',
      page: 1,
    })

    const { chips, hasActiveFilters } = useFilterChips(filters, categories)

    expect(hasActiveFilters.value).toBe(true)
    expect(chips.value.map((chip) => chip.label)).toEqual([
      '„rower”',
      'Motoryzacja > Samochody',
      'Warszawa',
    ])
  })

  it('falls back to the slug while the category tree is still loading', () => {
    const filters = ref<AdFilters>({ category: 'rowery', page: 1 })

    const { chips } = useFilterChips(filters, categories)

    expect(chips.value.map((chip) => chip.label)).toEqual(['rowery'])
  })

  it('shows radius in the location chip when geo filters are active', () => {
    const filters = ref<AdFilters>({
      location: 'Warszawa, województwo mazowieckie, Polska',
      lat: 52.2297,
      lng: 21.0122,
      radius_km: 10,
      page: 1,
    })

    const { chips } = useFilterChips(filters, categories)

    expect(chips.value.map((chip) => chip.label)).toEqual(['Warszawa (+10 km)'])
  })

  it('returns no chips when filters are empty', () => {
    const filters = ref<AdFilters>({ page: 1 })

    const { chips, hasActiveFilters } = useFilterChips(filters, categories)

    expect(hasActiveFilters.value).toBe(false)
    expect(chips.value).toEqual([])
  })
})