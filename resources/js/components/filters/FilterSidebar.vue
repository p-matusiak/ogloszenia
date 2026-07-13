<script setup lang="ts">
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import InputText from 'primevue/inputtext'
import { computed, reactive, watch } from 'vue'

import CategoryTreeFilter from '@/components/filters/CategoryTreeFilter.vue'
import CheckboxGroupFilter from '@/components/filters/CheckboxGroupFilter.vue'
import PriceRangeFilter from '@/components/filters/PriceRangeFilter.vue'
import {
  CONDITION_ORDER,
  DELIVERY_ORDER,
  conditionLabel,
  deliveryLabel,
  parseList,
  serialiseList,
} from '@/composables/useOfferLabels'
import type { AdCondition, AdFilters, DeliveryMethod } from '@/types/api'

const props = defineProps<{
  filters: AdFilters
  hasActiveFilters: boolean
  resultCount: number | null
}>()

const emit = defineEmits<{
  change: [patch: Partial<AdFilters>]
  clear: []
}>()

/** Kryteria panelu. `q`, `sort` i `page` należą do listy wyników, nie do szkicu. */
type FilterDraft = Pick<
  AdFilters,
  | 'category'
  | 'location'
  | 'price_min'
  | 'price_max'
  | 'negotiable'
  | 'free'
  | 'delivery'
  | 'condition'
>

function draftOf(filters: AdFilters): FilterDraft {
  return {
    category: filters.category,
    location: filters.location,
    price_min: filters.price_min,
    price_max: filters.price_max,
    negotiable: filters.negotiable,
    free: filters.free,
    delivery: filters.delivery,
    condition: filters.condition,
  }
}

/**
 * Zaznaczenie zmienia tylko szkic — wyszukiwanie rusza dopiero po `apply()`.
 * Dzięki temu wybór trzech kategorii to jedno zapytanie, a nie trzy.
 */
const draft = reactive<FilterDraft>(draftOf(props.filters))

/** Adres pozostaje źródłem prawdy: link, „wstecz” i „Wyczyść” przestawiają szkic. */
watch(
  () => props.filters,
  (applied) => Object.assign(draft, draftOf(applied)),
)

const appliedDraft = computed(() => draftOf(props.filters))

const isDirty = computed(() => {
  const applied = appliedDraft.value

  return (Object.keys(applied) as (keyof FilterDraft)[]).some((key) => draft[key] !== applied[key])
})

/** Licznik pasuje tylko do zatwierdzonych kryteriów, więc znika, gdy szkic je wyprzedza. */
const submitLabel = computed(() =>
  isDirty.value || props.resultCount === null ? 'Pokaż wyniki' : `Pokaż wyniki (${props.resultCount})`,
)

const deliveryOptions = DELIVERY_ORDER.map((value) => ({ value, label: deliveryLabel(value) }))
const conditionOptions = CONDITION_ORDER.map((value) => ({ value, label: conditionLabel(value) }))

const selectedDelivery = computed(() => parseList<DeliveryMethod>(draft.delivery, DELIVERY_ORDER))
const selectedCondition = computed(() => parseList<AdCondition>(draft.condition, CONDITION_ORDER))

function selectCategory(value: { category?: string }): void {
  draft.category = value.category
}

function apply(): void {
  emit('change', { ...draft })
}
</script>

<template>
  <aside class="surface sidebar">
    <header class="sidebar__header">
      <h2 class="sidebar__title">
        Filtry
      </h2>
      <Button
        v-if="hasActiveFilters"
        label="Wyczyść"
        text
        size="small"
        @click="emit('clear')"
      />
    </header>

    <section class="sidebar__section">
      <h3 class="sidebar__legend">
        Kategorie
      </h3>
      <CategoryTreeFilter
        :category="draft.category"
        @select="selectCategory"
      />
    </section>

    <section class="sidebar__section">
      <h3 class="sidebar__legend">
        Cena (zł)
      </h3>
      <PriceRangeFilter
        v-model:min="draft.price_min"
        v-model:max="draft.price_max"
        @submit="apply"
      />

      <ul class="flags">
        <li>
          <Checkbox
            input-id="filter-negotiable"
            :model-value="draft.negotiable === true"
            binary
            @update:model-value="draft.negotiable = $event || undefined"
          />
          <label for="filter-negotiable">Do negocjacji</label>
        </li>
        <li>
          <Checkbox
            input-id="filter-free"
            :model-value="draft.free === true"
            binary
            @update:model-value="draft.free = $event || undefined"
          />
          <label for="filter-free">Za darmo</label>
        </li>
      </ul>
    </section>

    <section class="sidebar__section">
      <h3 class="sidebar__legend">
        Lokalizacja
      </h3>
      <InputText
        :model-value="draft.location ?? ''"
        placeholder="Cała Polska"
        aria-label="Lokalizacja"
        fluid
        @update:model-value="draft.location = $event || undefined"
        @keyup.enter="apply"
      />
    </section>

    <section class="sidebar__section">
      <h3 class="sidebar__legend">
        Sposób wysyłki
      </h3>
      <CheckboxGroupFilter
        :options="deliveryOptions"
        :selected="selectedDelivery"
        @change="draft.delivery = serialiseList($event)"
      />
    </section>

    <section class="sidebar__section">
      <h3 class="sidebar__legend">
        Stan
      </h3>
      <CheckboxGroupFilter
        :options="conditionOptions"
        :selected="selectedCondition"
        @change="draft.condition = serialiseList($event)"
      />
    </section>

    <Button
      :label="submitLabel"
      fluid
      class="sidebar__submit"
      @click="apply"
    />
  </aside>
</template>

<style scoped>
.sidebar {
  padding: var(--card-padding);
  align-self: start;
}

.sidebar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.sidebar__title {
  margin: 0;
  font-size: var(--title-card);
  font-weight: 700;
}

.sidebar__section + .sidebar__section {
  margin-top: 1.125rem;
  padding-top: 1.125rem;
  border-top: 1px solid var(--surface-border);
}

.sidebar__legend {
  margin: 0 0 0.625rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.flags {
  list-style: none;
  margin: 0.75rem 0 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.flags li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.flags label {
  font-size: 0.875rem;
  cursor: pointer;
}

.sidebar__submit {
  margin-top: 1.25rem;
}
</style>
