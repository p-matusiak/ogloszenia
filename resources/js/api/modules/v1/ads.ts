import { client } from '@/api/client'
import { pruneFilters } from '@/composables/useRouteFilters'
import type {
  Ad,
  AdCategorySuggestion,
  AdFilters,
  AdFormValues,
  AdSummary,
  Paginated,
  ReportReason,
  ResourceEnvelope,
} from '@/types/api'

const BASE = '/api/v1'

export async function fetchAds(filters: AdFilters = {}): Promise<Paginated<AdSummary>> {
  // Axios serializuje `true` jako „true”, a reguła `boolean` Laravela przyjmuje
  // wyłącznie 1/0. pruneFilters zamienia flagi na „1” i usuwa puste wartości.
  const { data } = await client.get<Paginated<AdSummary>>(`${BASE}/ads`, {
    params: pruneFilters(filters),
  })

  return data
}

export async function fetchAd(slug: string): Promise<Ad> {
  const { data } = await client.get<ResourceEnvelope<Ad>>(`${BASE}/ads/${slug}`)

  return data.data
}

export async function fetchMoreFromSeller(slug: string): Promise<AdSummary[]> {
  const { data } = await client.get<{ data: AdSummary[] }>(`${BASE}/ads/${slug}/more-from-seller`)

  return data.data
}

export async function fetchMyAds(page = 1): Promise<Paginated<AdSummary>> {
  const { data } = await client.get<Paginated<AdSummary>>(`${BASE}/my/ads`, { params: { page } })

  return data
}

export async function suggestAdCategory(title: string): Promise<AdCategorySuggestion> {
  const { data } = await client.post<ResourceEnvelope<AdCategorySuggestion>>(`${BASE}/ads/suggest-category`, {
    title,
  })

  return data.data
}

export async function createAd(values: AdFormValues): Promise<Ad> {
  const { data } = await client.post<ResourceEnvelope<Ad>>(`${BASE}/ads`, toFormData(values))

  return data.data
}

/**
 * POST rather than PUT: the payload is multipart, which browsers cannot send
 * with PUT without method spoofing. The API route matches.
 */
export async function updateAd(slug: string, values: AdFormValues): Promise<Ad> {
  const { data } = await client.post<ResourceEnvelope<Ad>>(`${BASE}/ads/${slug}`, toFormData(values))

  return data.data
}

export async function deleteAd(slug: string): Promise<void> {
  await client.delete(`${BASE}/ads/${slug}`)
}

export async function refreshAd(slug: string): Promise<Ad> {
  const { data } = await client.post<ResourceEnvelope<Ad>>(`${BASE}/ads/${slug}/refresh`)

  return data.data
}

export async function reportAd(slug: string, reason: ReportReason, message?: string): Promise<void> {
  await client.post(`${BASE}/ads/${slug}/reports`, { reason, message })
}

/**
 * Numer telefonu nie jedzie w payloadzie ogłoszenia. Pobieramy go dopiero na
 * jawne kliknięcie „Pokaż numer”; endpoint jest limitowany po stronie serwera.
 */
export async function revealPhone(slug: string): Promise<string> {
  const { data } = await client.post<{ phone: string }>(`${BASE}/ads/${slug}/phone`)

  return data.phone
}

function toFormData(values: AdFormValues): FormData {
  const form = new FormData()

  form.append('title', values.title)
  form.append('description', values.description)
  form.append('category_id', String(values.category_id ?? ''))
  form.append('accept_terms', values.accept_terms ? '1' : '0')
  form.append('is_negotiable', values.is_negotiable ? '1' : '0')

  appendOptional(form, 'price', values.price === null ? '' : String(values.price))
  appendOptional(form, 'condition', values.condition ?? '')
  appendOptional(form, 'location', values.location)
  if (values.latitude !== null) {
    form.append('latitude', String(values.latitude))
  }
  if (values.longitude !== null) {
    form.append('longitude', String(values.longitude))
  }
  form.append('use_custom_phone', values.use_custom_phone ? '1' : '0')
  if (values.use_custom_phone) {
    appendOptional(form, 'contact_phone', values.contact_phone)
  }

  values.delivery_methods.forEach((method) => form.append('delivery_methods[]', method))

  // Puste pole ceny znaczy „nie podano”, więc w ogóle go nie wysyłamy.
  for (const method of values.delivery_methods) {
    const price = values.delivery_prices[method]
    if (price !== undefined && price !== '') {
      form.append(`delivery_prices[${method}]`, price)
    }
  }

  values.images.forEach((image) => form.append('images[]', image))
  values.removed_image_ids.forEach((id) => form.append('removed_image_ids[]', String(id)))

  return form
}

/** Laravel treats '' as present-but-empty, which fails `nullable|email`. */
function appendOptional(form: FormData, key: string, value: string): void {
  if (value !== '') {
    form.append(key, value)
  }
}
