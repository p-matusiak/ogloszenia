<script setup lang="ts">
import Button from 'primevue/button'
import Drawer from 'primevue/drawer'
import Message from 'primevue/message'
import Paginator from 'primevue/paginator'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'
import { storeToRefs } from 'pinia'
import { computed, onMounted, ref, watch, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import AdSearchForm from '@/components/AdSearchForm.vue'
import ActiveFilters from '@/components/ActiveFilters.vue'
import EmptyState from '@/components/EmptyState.vue'
import AdCard from '@/components/ads/AdCard.vue'
import AdCardSkeleton from '@/components/ads/AdCardSkeleton.vue'
import AdListItem from '@/components/ads/AdListItem.vue'
import AdListItemSkeleton from '@/components/ads/AdListItemSkeleton.vue'
import FilterSidebar from '@/components/filters/FilterSidebar.vue'
import { useFilterChips } from '@/composables/useFilterChips'
import { useListingLayout } from '@/composables/useListingLayout'
import {
  CONDITION_ORDER,
  DELIVERY_ORDER,
  parseList,
} from '@/composables/useOfferLabels'
import { setDocumentTitle } from '@/composables/usePageTitle'
import { listingLocation, routeFilters } from '@/composables/useRouteFilters'
import { useAdsStore } from '@/stores/ads'
import { useCategoryStore } from '@/stores/categories'
import type { AdFilters, AdSort } from '@/types/api'

const route = useRoute()
const router = useRouter()
const adsStore = useAdsStore()
const categories = useCategoryStore()
const { layout, setLayout } = useListingLayout()

const { ads, meta, isLoading, error, isEmpty } = storeToRefs(adsStore)
const isFilterDrawerOpen = ref(false)

const categorySlug = computed(() =>
  typeof route.params.slug === 'string' ? route.params.slug : undefined,
)

const filters = computed<AdFilters>(() => routeFilters(route.query, categorySlug.value))
const { chips, hasActiveFilters } = useFilterChips(filters, categories)

/** Pusta, dopóki drzewo się nie doładuje — okruszki pojawiają się wtedy same. */
const breadcrumb = computed(() =>
  categorySlug.value === undefined ? [] : categories.pathOf(categorySlug.value),
)

const heading = computed(() => breadcrumb.value.at(-1)?.name ?? 'Ogłoszenia')

const layoutOptions = [
  { label: 'Siatka', value: 'grid', icon: 'pi pi-th-large' },
  { label: 'Lista', value: 'list', icon: 'pi pi-list' },
]

const sortOptions = computed<{ label: string; value: AdSort }[]>(() => {
  const base = [
    { label: 'Najnowsze', value: 'newest' as const },
    { label: 'Najtańsze', value: 'price_asc' as const },
    { label: 'Najdroższe', value: 'price_desc' as const },
  ]

  if (filters.value.q) {
    return [{ label: 'Trafność', value: 'relevance' as const }, ...base]
  }

  return base
})

async function navigate(next: AdFilters): Promise<void> {
  await router.push(listingLocation(next)).catch(() => undefined)
}

async function changeFilters(patch: Partial<AdFilters>): Promise<void> {
  isFilterDrawerOpen.value = false
  await navigate({ ...filters.value, ...patch, page: undefined })
}

async function removeChip(key: string): Promise<void> {
  const [group, value] = key.split(':')

  if (group === 'delivery' || group === 'condition') {
    const order = group === 'delivery' ? DELIVERY_ORDER : CONDITION_ORDER
    const remaining = parseList(filters.value[group], order).filter((item) => item !== value)

    await changeFilters({ [group]: remaining.length > 0 ? remaining.join(',') : undefined })
    return
  }

  if (key === 'price') {
    await changeFilters({ price_min: undefined, price_max: undefined })
    return
  }

  await changeFilters({ [key]: undefined })
}

async function searchQuery(query: string): Promise<void> {
  const trimmed = query.trim()
  let sort = filters.value.sort

  if (trimmed && !filters.value.q) {
    sort = 'relevance'
  } else if (!trimmed && sort === 'relevance') {
    sort = undefined
  }

  await changeFilters({
    q: trimmed || undefined,
    sort,
  })
}

onMounted(async () => {
  await categories.load()
  await adsStore.search(filters.value)
})

watch(filters, (next) => void adsStore.search(next))

// Serwer renderuje tytuł strony kategorii, ale nawigacja w SPA nie przeładowuje
// `<head>`. Nazwa kategorii bierze się z drzewa, więc tytuł dopina się dopiero
// po jego załadowaniu — stąd `watchEffect`, a nie jednorazowy zapis.
watchEffect(() =>
  setDocumentTitle(categorySlug.value === undefined ? undefined : `${heading.value} — ogłoszenia`),
)
</script>

<template>
  <div class="page">
    <FilterSidebar
      :filters="filters"
      :has-active-filters="hasActiveFilters"
      :result-count="meta?.total ?? null"
      class="page__sidebar"
      @change="changeFilters"
      @clear="navigate({})"
    />

    <section class="surface results">
      <header class="results__header">
        <div class="results__intro">
          <nav
            v-if="breadcrumb.length > 0"
            class="crumbs"
            aria-label="Ścieżka kategorii"
          >
            <RouterLink :to="{ name: 'home' }">
              Ogłoszenia
            </RouterLink>
            <template
              v-for="(node, index) in breadcrumb"
              :key="node.id"
            >
              <span
                class="crumbs__sep"
                aria-hidden="true"
              >›</span>
              <span
                v-if="index === breadcrumb.length - 1"
                aria-current="page"
              >{{ node.name }}</span>
              <RouterLink
                v-else
                :to="{ name: 'categories.show', params: { slug: node.slug } }"
              >
                {{ node.name }}
              </RouterLink>
            </template>
          </nav>

          <h1 class="results__title">
            {{ heading }}
            <span
              v-if="meta"
              class="results__count"
            >{{ meta.total }}</span>
          </h1>
          <p class="results__hint">
            Przeglądaj oferty lub zawęź wyniki filtrami.
          </p>
        </div>

        <AdSearchForm
          :query="filters.q ?? ''"
          show-filters-button
          class="results__search"
          @search="searchQuery"
          @open-filters="isFilterDrawerOpen = true"
        />
      </header>

      <div class="results__toolbar">
        <SelectButton
          :model-value="layout"
          :options="layoutOptions"
          option-label="label"
          option-value="value"
          aria-label="Układ listy"
          @update:model-value="setLayout($event as 'grid' | 'list')"
        >
          <template #option="{ option }">
            <i
              :class="option.icon"
              aria-hidden="true"
            />
            <span class="results__layout-label">{{ option.label }}</span>
          </template>
        </SelectButton>

        <Select
          :model-value="filters.sort ?? (filters.q ? 'relevance' : 'newest')"
          :options="sortOptions"
          option-label="label"
          option-value="value"
          aria-label="Sortowanie"
          size="small"
          @update:model-value="changeFilters({ sort: $event as AdSort })"
        />
      </div>

      <ActiveFilters
        v-if="hasActiveFilters"
        :chips="chips"
        :result-count="meta?.total ?? null"
        class="results__chips"
        @remove="removeChip"
        @clear="navigate({})"
      />

      <div
        v-if="isLoading"
        :class="layout === 'grid' ? 'results__grid' : 'results__list'"
      >
        <template v-if="layout === 'grid'">
          <AdCardSkeleton
            v-for="n in 8"
            :key="n"
          />
        </template>
        <template v-else>
          <AdListItemSkeleton
            v-for="n in 5"
            :key="n"
          />
        </template>
      </div>

      <Message
        v-else-if="error"
        severity="error"
        class="results__block"
      >
        <span>{{ error }}</span>
        <Button
          label="Spróbuj ponownie"
          text
          size="small"
          @click="adsStore.search(filters)"
        />
      </Message>

      <EmptyState
        v-else-if="isEmpty"
        icon="pi pi-search"
        title="Brak ogłoszeń spełniających kryteria"
        description="Spróbuj innych słów kluczowych albo wyczyść filtry."
        class="results__block"
      >
        <Button
          label="Wyczyść filtry"
          outlined
          @click="navigate({})"
        />
      </EmptyState>

      <template v-else>
        <div :class="layout === 'grid' ? 'results__grid' : 'results__list'">
          <template v-if="layout === 'grid'">
            <AdCard
              v-for="ad in ads"
              :key="ad.id"
              :ad="ad"
            />
          </template>
          <template v-else>
            <AdListItem
              v-for="ad in ads"
              :key="ad.id"
              :ad="ad"
            />
          </template>
        </div>

        <Paginator
          v-if="meta && meta.last_page > 1"
          :rows="meta.per_page"
          :total-records="meta.total"
          :first="(meta.current_page - 1) * meta.per_page"
          class="results__pager"
          @page="changeFilters({ page: $event.page + 1 })"
        />
      </template>
    </section>

    <Drawer
      v-model:visible="isFilterDrawerOpen"
      header="Filtry"
      class="page__drawer"
    >
      <FilterSidebar
        :filters="filters"
        :has-active-filters="hasActiveFilters"
        :result-count="meta?.total ?? null"
        @change="changeFilters"
        @clear="navigate({})"
      />
    </Drawer>
  </div>
</template>

<style scoped>
.page {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--stack-gap);
  align-items: start;
}

.page__sidebar {
  display: none;
}

@media (width >= 62rem) {
  .page {
    grid-template-columns: 17.5rem minmax(0, 1fr);
  }

  .page__sidebar {
    display: block;
    position: sticky;
    top: 6rem;
  }

  .results__search {
    display: none;
  }
}

.results {
  overflow: hidden;
}

.results__header {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: var(--card-padding);
  border-bottom: 1px solid var(--surface-border);
}

.crumbs {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 0.4rem;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.crumbs a {
  color: inherit;
  text-decoration: none;
}

.crumbs a:hover {
  color: var(--p-primary-color);
}

.crumbs__sep {
  color: var(--surface-border);
}

.results__title {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 700;
}

.results__count {
  margin-left: 0.5rem;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  background: var(--surface-muted);
  color: var(--text-muted);
  font-size: 0.8125rem;
  font-weight: 600;
}

.results__hint {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.results__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.875rem var(--card-padding);
  border-bottom: 1px solid var(--surface-border);
}

.results__layout-label {
  display: none;
}

@media (width >= 30rem) {
  .results__layout-label {
    display: inline;
  }
}

.results__chips {
  padding: 0 var(--card-padding) 1rem;
}

.results__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  padding: var(--card-padding);
}

@media (width >= 36rem) {
  .results__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (width >= 75rem) {
  .results__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (width >= 90rem) {
  .results__grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.results__list {
  display: flex;
  flex-direction: column;
}

.results__block {
  margin: var(--card-padding);
}

.results__pager {
  padding: 0 var(--card-padding) var(--card-padding);
}
</style>