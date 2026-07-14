import { afterEach, describe, expect, it, vi } from 'vitest'

import { searchLocations } from '@/composables/useGeocoding'

afterEach(() => {
  vi.restoreAllMocks()
})

describe('searchLocations', () => {
  it('returns an empty list for short queries without calling the API', async () => {
    const fetchSpy = vi.spyOn(globalThis, 'fetch')

    await expect(searchLocations('wa')).resolves.toEqual([])
    expect(fetchSpy).not.toHaveBeenCalled()
  })

  it('maps Nominatim results to label and coordinates', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify([
          {
            display_name: 'Warszawa, województwo mazowieckie, Polska',
            lat: '52.2296756',
            lon: '21.0122287',
          },
        ]),
        { status: 200 },
      ),
    )

    await expect(searchLocations('Warszawa')).resolves.toEqual([
      {
        label: 'Warszawa, województwo mazowieckie, Polska',
        latitude: 52.2296756,
        longitude: 21.0122287,
      },
    ])
  })
})