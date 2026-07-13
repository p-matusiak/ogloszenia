import type { AdCondition, DeliveryMethod } from '@/types/api'

/** Jedno źródło polskich nazw dla wartości enumów z backendu. */
const DELIVERY_LABELS: Record<DeliveryMethod, string> = {
  personal: 'Odbiór osobisty',
  courier: 'Kurier',
  parcel_locker: 'Paczkomat',
  post: 'Poczta',
  local: 'Dostawa lokalna',
}

const CONDITION_LABELS: Record<AdCondition, string> = {
  new: 'Nowe',
  used: 'Używane',
  damaged: 'Uszkodzone',
}

/** Kolejność jak w sidebarze makiety, nie kolejność z bazy. */
export const DELIVERY_ORDER: readonly DeliveryMethod[] = [
  'personal',
  'courier',
  'parcel_locker',
  'post',
  'local',
]

export const CONDITION_ORDER: readonly AdCondition[] = ['new', 'used', 'damaged']

export function deliveryLabel(method: DeliveryMethod): string {
  return DELIVERY_LABELS[method]
}

export function conditionLabel(condition: AdCondition): string {
  return CONDITION_LABELS[condition]
}

/** „Paczkomat, Kurier, Odbiór osobisty” — jedna plakietka, nie pięć. */
export function deliverySummary(methods: DeliveryMethod[]): string {
  return methods.map(deliveryLabel).join(', ')
}

/** Zamienia listę z URL-a („new,used”) na tablicę i z powrotem. */
export function parseList<T extends string>(raw: string | undefined, allowed: readonly T[]): T[] {
  if (!raw) {
    return []
  }

  return raw.split(',').filter((value): value is T => (allowed as readonly string[]).includes(value))
}

export function serialiseList(values: readonly string[]): string | undefined {
  return values.length > 0 ? values.join(',') : undefined
}

/** „Warszawa, Mokotów” — bez osieroconego przecinka, gdy dzielnicy brak. */
export function locationLabel(city: string | null, district: string | null): string {
  return [city, district].filter(Boolean).join(', ')
}
