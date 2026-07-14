import { i18n } from '@/i18n/index'
import type { AdCondition, DeliveryMethod } from '@/types/api'

/** Kolejność jak w sidebarze makiety, nie kolejność z bazy. */
export const DELIVERY_ORDER: readonly DeliveryMethod[] = [
  'personal',
  'courier',
  'parcel_locker',
  'post',
  'local',
]

export const CONDITION_ORDER: readonly AdCondition[] = ['new', 'used', 'damaged']

export const DELIVERY_ICONS: Record<DeliveryMethod, string> = {
  personal: 'pi pi-home',
  courier: 'pi pi-truck',
  parcel_locker: 'pi pi-box',
  post: 'pi pi-envelope',
  local: 'pi pi-car',
}

const FREE_DELIVERY_METHODS: readonly DeliveryMethod[] = ['personal']

export function deliveryLabel(method: DeliveryMethod): string {
  return i18n.global.t(`offers.delivery.${method}`)
}

export function deliveryIcon(method: DeliveryMethod): string {
  return DELIVERY_ICONS[method]
}

/** Kolejność z makiety, nie kolejność zapisana w ogłoszeniu. */
export function deliveryMethodsInOrder(methods: readonly DeliveryMethod[]): DeliveryMethod[] {
  return DELIVERY_ORDER.filter((method) => methods.includes(method))
}

export function isFreeDeliveryMethod(method: DeliveryMethod): boolean {
  return FREE_DELIVERY_METHODS.includes(method)
}

export function conditionLabel(condition: AdCondition): string {
  return i18n.global.t(`offers.condition.${condition}`)
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

export function locationLabel(location: string | null): string {
  return location ?? ''
}