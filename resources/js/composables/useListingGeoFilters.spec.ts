import { afterEach, describe, expect, it, vi } from 'vitest'

import { DEFAULT_LISTING_RADIUS_KM } from '@/constants/geo'
import {
  buildLocationFilters,
  clearGeoFilters,
  geoFiltersFromSelection,
  hasGeoFilters,
  isWholePolandLabel,
  locationChipLabel,
} from '@/composables/useListingGeoFilters'

afterEach(() => {
  vi.restoreAllMocks()
})

describe('useListingGeoFilters', () => {
  it('detects active geo filters', () => {
    expect(hasGeoFilters({ lat: 52.2, lng: 21.0, radius_km: 10, page: 1 })).toBe(true)
    expect(hasGeoFilters({ lat: 52.2, lng: 21.0, page: 1 })).toBe(false)
  })

  it('builds geo patch with default radius', () => {
    expect(
      geoFiltersFromSelection({
        label: 'Warszawa, Polska',
        lat: 52.2297,
        lng: 21.0122,
      }),
    ).toEqual({
      location: 'Warszawa, Polska',
      lat: 52.2297,
      lng: 21.0122,
      radius_km: DEFAULT_LISTING_RADIUS_KM,
    })
  })

  it('clears all location-related filters', () => {
    expect(clearGeoFilters()).toEqual({
      location: undefined,
      lat: undefined,
      lng: undefined,
      radius_km: undefined,
    })
  })

  it('formats chip label with radius', () => {
    expect(locationChipLabel('Warszawa, województwo mazowieckie, Polska', 10)).toBe(
      'Warszawa (+10 km)',
    )
  })

  it('uses coordinates when present', async () => {
    const fetchSpy = vi.spyOn(globalThis, 'fetch')

    await expect(
      buildLocationFilters('Warszawa', 52.2297, 21.0122),
    ).resolves.toEqual({
      location: 'Warszawa',
      lat: 52.2297,
      lng: 21.0122,
      radius_km: DEFAULT_LISTING_RADIUS_KM,
    })

    expect(fetchSpy).not.toHaveBeenCalled()
  })

  it('geocodes label when coordinates are missing', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify([
          {
            display_name: 'Kraków, województwo małopolskie, Polska',
            lat: '50.0619474',
            lon: '19.9368564',
          },
        ]),
        { status: 200 },
      ),
    )

    await expect(buildLocationFilters('Kraków', undefined, undefined)).resolves.toEqual({
      location: 'Kraków, województwo małopolskie, Polska',
      lat: 50.0619474,
      lng: 19.9368564,
      radius_km: DEFAULT_LISTING_RADIUS_KM,
    })
  })

  it('falls back to text-only location when geocoding fails', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify([]), { status: 200 }))

    await expect(buildLocationFilters('Nieznane', undefined, undefined)).resolves.toEqual({
      location: 'Nieznane',
      lat: undefined,
      lng: undefined,
      radius_km: undefined,
    })
  })

  it('clears filters for empty label', async () => {
    await expect(buildLocationFilters('', undefined, undefined)).resolves.toEqual(clearGeoFilters())
  })

  it('traktuje „Cała Polska” jak brak filtra geo', async () => {
    const fetchSpy = vi.spyOn(globalThis, 'fetch')

    expect(isWholePolandLabel('Cała Polska')).toBe(true)
    expect(isWholePolandLabel('All of Poland')).toBe(true)
    expect(isWholePolandLabel('Warszawa')).toBe(false)

    await expect(buildLocationFilters('Cała Polska', undefined, undefined)).resolves.toEqual(
      clearGeoFilters(),
    )

    expect(fetchSpy).not.toHaveBeenCalled()
  })
})