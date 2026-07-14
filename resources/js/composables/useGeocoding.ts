export interface GeocodingResult {
  label: string
  latitude: number
  longitude: number
}

interface NominatimResult {
  display_name: string
  lat: string
  lon: string
}

const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search'

/**
 * OpenStreetMap Nominatim — wywoływane dopiero po wpisaniu zapytania przez
 * użytkownika, nie przy prefillu z profilu ani z istniejącego ogłoszenia.
 */
export async function searchLocations(query: string): Promise<GeocodingResult[]> {
  const trimmed = query.trim()

  if (trimmed.length < 3) {
    return []
  }

  const params = new URLSearchParams({
    q: trimmed,
    format: 'json',
    limit: '5',
    countrycodes: 'pl',
    addressdetails: '0',
  })

  const response = await fetch(`${NOMINATIM_URL}?${params}`, {
    headers: {
      Accept: 'application/json',
      'Accept-Language': 'pl',
    },
  })

  if (!response.ok) {
    throw new Error('Nie udało się wyszukać lokalizacji.')
  }

  const results = (await response.json()) as NominatimResult[]

  return results.map((result) => ({
    label: result.display_name,
    latitude: Number(result.lat),
    longitude: Number(result.lon),
  }))
}