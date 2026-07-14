import type { LocationQuery, LocationQueryValue, RouteLocationRaw } from 'vue-router'

import type { AdFilters, AdSort } from '@/types/api'

const SORTS: readonly AdSort[] = ['relevance', 'newest', 'price_asc', 'price_desc']

/** `noUncheckedIndexedAccess` sprawia, że brakujący klucz jest `undefined`. */
type QueryValue = LocationQueryValue | LocationQueryValue[] | undefined

/**
 * URL jest jedynym źródłem prawdy o filtrach — dzięki temu wynik wyszukiwania
 * da się wysłać linkiem, a „wstecz” w przeglądarce działa bez dodatkowego stanu.
 *
 * Kategoria przychodzi z segmentu ścieżki, nie z query stringu: `/kategoria/rowery`
 * to indeksowalny landing page, a `/?category=rowery` był tylko wariantem listingu.
 */
export function routeFilters(query: LocationQuery, categorySlug?: string): AdFilters {
  return {
    q: text(query.q),
    category: categorySlug,
    location: text(query.location),
    lat: number(query.lat),
    lng: number(query.lng),
    radius_km: number(query.radius_km),
    price_min: number(query.price_min),
    price_max: number(query.price_max),
    negotiable: flag(query.negotiable),
    free: flag(query.free),
    condition: text(query.condition),
    delivery: text(query.delivery),
    sort: sort(query.sort),
    seller: text(query.seller),
    page: number(query.page) ?? 1,
  }
}

/**
 * Odwrotność `routeFilters`: kategoria wraca do ścieżki, reszta do query stringu.
 * Trzymane razem, bo rozjechanie się tych dwóch funkcji znaczyłoby, że adres
 * przestaje odtwarzać filtry, z których powstał.
 */
export function listingLocation(filters: AdFilters): RouteLocationRaw {
  const { category, ...rest } = filters
  const query = pruneFilters(rest)

  return category === undefined || category === ''
    ? { name: 'listings', query }
    : { name: 'categories.show', params: { slug: category }, query }
}

/**
 * Puste wartości muszą znikać z adresu, a nie trafiać do API jako `""`.
 * Flagi jadą jako `1`, bo Laravel nie uzna stringa `"true"` za boolean.
 */
export function pruneFilters(filters: AdFilters): Record<string, string> {
  const pruned: Record<string, string> = {}

  for (const [key, value] of Object.entries(filters)) {
    if (value === undefined || value === null || value === '' || value === false) {
      continue
    }

    pruned[key] = value === true ? '1' : String(value)
  }

  return pruned
}

function text(value: QueryValue): string | undefined {
  return typeof value === 'string' && value !== '' ? value : undefined
}

function flag(value: QueryValue): boolean | undefined {
  return text(value) === '1' ? true : undefined
}

function number(value: QueryValue): number | undefined {
  const raw = text(value)
  if (raw === undefined) {
    return undefined
  }

  const parsed = Number(raw)

  return Number.isFinite(parsed) ? parsed : undefined
}

function sort(value: QueryValue): AdSort | undefined {
  const raw = text(value)

  return SORTS.find((candidate) => candidate === raw)
}
