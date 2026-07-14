import { describe, expect, it } from 'vitest'

import { listingLocation, pruneFilters, routeFilters } from '@/composables/useRouteFilters'

describe('routeFilters', () => {
  it('reads every supported filter from the query string', () => {
    expect(
      routeFilters(
        {
          q: 'rower',
          location: 'Warszawa',
          price_min: '100',
          price_max: '900',
          sort: 'price_asc',
          seller: 'jan-kowalski',
          page: '3',
        },
        'samochody',
      ),
    ).toEqual({
      q: 'rower',
      category: 'samochody',
      location: 'Warszawa',
      lat: undefined,
      lng: undefined,
      radius_km: undefined,
      price_min: 100,
      price_max: 900,
      sort: 'price_asc',
      seller: 'jan-kowalski',
      page: 3,
    })
  })

  it('takes the category from the path, never from the query string', () => {
    // `/?category=x` to stary adres; przekierowuje go backend, a nie router.
    expect(routeFilters({ category: 'motoryzacja' }).category).toBeUndefined()
  })

  it('defaults to the first page and no filters', () => {
    expect(routeFilters({})).toEqual({
      q: undefined,
      category: undefined,
      location: undefined,
      lat: undefined,
      lng: undefined,
      radius_km: undefined,
      price_min: undefined,
      price_max: undefined,
      sort: undefined,
      seller: undefined,
      page: 1,
    })
  })

  it('reads the seller filter from the query string', () => {
    expect(routeFilters({ seller: 'jan-kowalski' }).seller).toBe('jan-kowalski')
  })

  it('reads geo filters from the query string', () => {
    expect(
      routeFilters({
        location: 'Warszawa, Polska',
        lat: '52.2297',
        lng: '21.0122',
        radius_km: '10',
      }),
    ).toEqual({
      q: undefined,
      category: undefined,
      location: 'Warszawa, Polska',
      lat: 52.2297,
      lng: 21.0122,
      radius_km: 10,
      price_min: undefined,
      price_max: undefined,
      sort: undefined,
      seller: undefined,
      page: 1,
    })
  })

  it('rejects an unknown sort key rather than passing it to the API', () => {
    expect(routeFilters({ sort: 'losowo' }).sort).toBeUndefined()
  })

  it('ignores non-numeric prices and repeated params', () => {
    expect(routeFilters({ price_min: 'abc' }).price_min).toBeUndefined()
    expect(routeFilters({ q: ['a', 'b'] }).q).toBeUndefined()
  })
})

describe('pruneFilters', () => {
  it('drops empty values so they never reach the URL', () => {
    expect(pruneFilters({ q: 'rower', category: undefined, location: '', page: 2 })).toEqual({
      q: 'rower',
      page: '2',
    })
  })

  it('keeps a zero, which is a real minimum price', () => {
    expect(pruneFilters({ price_min: 0 })).toEqual({ price_min: '0' })
  })

  it('sends flags as "1", because Laravel rejects the string "true"', () => {
    expect(pruneFilters({ negotiable: true, free: false })).toEqual({ negotiable: '1' })
  })
})

describe('listingLocation', () => {
  it('sends a category to its own path and leaves the rest in the query', () => {
    expect(listingLocation({ category: 'samochody', q: 'kombi', page: 2 })).toEqual({
      name: 'categories.show',
      params: { slug: 'samochody' },
      query: { q: 'kombi', page: '2' },
    })
  })

  it('falls back to the plain listing when no category is selected', () => {
    expect(listingLocation({ q: 'rower' })).toEqual({
      name: 'listings',
      query: { q: 'rower' },
    })
  })

  it('round-trips with routeFilters, so the url always rebuilds its filters', () => {
    const filters = routeFilters({ q: 'rower', sort: 'price_asc' }, 'rowery')
    const location = listingLocation(filters)

    expect(location).toMatchObject({ name: 'categories.show', params: { slug: 'rowery' } })
    expect(routeFilters({ q: 'rower', sort: 'price_asc' }, 'rowery')).toEqual(filters)
  })

  it('round-trips geo filters through the URL', () => {
    const filters = routeFilters({
      location: 'Warszawa, Polska',
      lat: '52.2297',
      lng: '21.0122',
      radius_km: '10',
    })
    const location = listingLocation(filters) as {
      name: string
      query: Record<string, string>
    }

    expect(location).toEqual({
      name: 'listings',
      query: {
        location: 'Warszawa, Polska',
        lat: '52.2297',
        lng: '21.0122',
        radius_km: '10',
        page: '1',
      },
    })
    expect(routeFilters(location.query)).toEqual(filters)
  })
})

describe('routeFilters flags', () => {
  it('reads only "1" as a set flag', () => {
    expect(routeFilters({ negotiable: '1' }).negotiable).toBe(true)
    expect(routeFilters({ negotiable: 'true' }).negotiable).toBeUndefined()
    expect(routeFilters({}).negotiable).toBeUndefined()
  })
})
