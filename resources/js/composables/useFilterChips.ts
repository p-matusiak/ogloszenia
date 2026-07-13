import { computed, type ComputedRef, type Ref } from 'vue'

import {
  CONDITION_ORDER,
  DELIVERY_ORDER,
  conditionLabel,
  deliveryLabel,
  parseList,
} from '@/composables/useOfferLabels'
import type { AdCondition, AdFilters, DeliveryMethod } from '@/types/api'
import type { FilterChip } from '@/types/ui'

interface CategoryLookup {
  pathOf: (slug: string) => { name: string }[]
}

export function useFilterChips(
  filters: Ref<AdFilters>,
  categories: CategoryLookup,
): { chips: ComputedRef<FilterChip[]>; hasActiveFilters: ComputedRef<boolean> } {
  const chips = computed<FilterChip[]>(() => {
    const f = filters.value
    const active: FilterChip[] = []

    if (f.q) {
      active.push({ key: 'q', label: `„${f.q}”` })
    }
    if (f.category) {
      active.push({ key: 'category', label: categoryPath(f.category) })
    }
    if (f.location) {
      active.push({ key: 'location', label: f.location })
    }
    if (f.price_min !== undefined || f.price_max !== undefined) {
      active.push({ key: 'price', label: `${f.price_min ?? 0}–${f.price_max ?? '∞'} zł` })
    }
    if (f.negotiable) {
      active.push({ key: 'negotiable', label: 'Do negocjacji', tone: 'info' })
    }
    if (f.free) {
      active.push({ key: 'free', label: 'Za darmo', tone: 'info' })
    }
    for (const method of parseList<DeliveryMethod>(f.delivery, DELIVERY_ORDER)) {
      active.push({ key: `delivery:${method}`, label: deliveryLabel(method), tone: 'success' })
    }
    for (const condition of parseList<AdCondition>(f.condition, CONDITION_ORDER)) {
      active.push({ key: `condition:${condition}`, label: conditionLabel(condition) })
    }

    return active
  })

  const hasActiveFilters = computed(() => chips.value.length > 0)

  /** Zanim drzewo się doładuje, `pathOf` nie zna jeszcze nazw — pokazujemy slug. */
  function categoryPath(slug: string): string {
    const names = categories.pathOf(slug).map((node) => node.name)

    return names.length > 0 ? names.join(' > ') : slug
  }

  return { chips, hasActiveFilters }
}