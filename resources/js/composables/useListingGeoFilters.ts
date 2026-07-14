import { DEFAULT_LISTING_RADIUS_KM, WHOLE_POLAND_LABELS } from '@/constants/geo'
import { searchLocations } from '@/composables/useGeocoding'
import type { AdFilters } from '@/types/api'

export interface LocationSelection {
  label: string
  lat: number
  lng: number
}

export function isWholePolandLabel(label: string | undefined): boolean {
  const trimmed = label?.trim()

  if (trimmed === undefined || trimmed === '') {
    return true
  }

  const normalized = trimmed.toLowerCase()

  return WHOLE_POLAND_LABELS.some((wholePolandLabel) => wholePolandLabel.toLowerCase() === normalized)
}

export function hasGeoFilters(filters: AdFilters): boolean {
  return filters.lat !== undefined && filters.lng !== undefined && filters.radius_km !== undefined
}

export function geoFiltersFromSelection(
  selection: LocationSelection,
  radiusKm = DEFAULT_LISTING_RADIUS_KM,
): Pick<AdFilters, 'location' | 'lat' | 'lng' | 'radius_km'> {
  return {
    location: selection.label,
    lat: selection.lat,
    lng: selection.lng,
    radius_km: radiusKm,
  }
}

export function clearGeoFilters(): Pick<AdFilters, 'location' | 'lat' | 'lng' | 'radius_km'> {
  return {
    location: undefined,
    lat: undefined,
    lng: undefined,
    radius_km: undefined,
  }
}

export function locationChipLabel(location: string, radiusKm?: number): string {
  const shortLabel = location.split(',')[0]?.trim() || location

  return radiusKm !== undefined ? `${shortLabel} (+${radiusKm} km)` : shortLabel
}

export async function resolveLocationLabel(label: string): Promise<LocationSelection | null> {
  const results = await searchLocations(label)
  const first = results[0]

  if (first === undefined) {
    return null
  }

  return {
    label: first.label,
    lat: first.latitude,
    lng: first.longitude,
  }
}

/**
 * Buduje filtry lokalizacji do URL/API.
 * Gdy są współrzędne — zawsze dokłada promień; bez nich próbuje geokodować etykietę.
 */
export async function buildLocationFilters(
  label: string | undefined,
  lat: number | undefined,
  lng: number | undefined,
): Promise<Pick<AdFilters, 'location' | 'lat' | 'lng' | 'radius_km'>> {
  const trimmed = label?.trim()

  if (isWholePolandLabel(trimmed)) {
    return clearGeoFilters()
  }

  if (lat !== undefined && lng !== undefined) {
    return geoFiltersFromSelection({ label: trimmed, lat, lng })
  }

  const resolved = await resolveLocationLabel(trimmed)

  if (resolved !== null) {
    return geoFiltersFromSelection(resolved)
  }

  return {
    location: trimmed,
    lat: undefined,
    lng: undefined,
    radius_km: undefined,
  }
}