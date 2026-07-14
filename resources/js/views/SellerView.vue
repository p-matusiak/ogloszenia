<script setup lang="ts">
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Paginator from 'primevue/paginator'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'
import { storeToRefs } from 'pinia'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { errorMessage } from '@/api/client'
import { fetchSeller } from '@/api/modules/v1/sellers'
import EmptyState from '@/components/EmptyState.vue'
import AdCard from '@/components/ads/AdCard.vue'
import AdCardSkeleton from '@/components/ads/AdCardSkeleton.vue'
import AdListItem from '@/components/ads/AdListItem.vue'
import AdListItemSkeleton from '@/components/ads/AdListItemSkeleton.vue'
import { useListingLayout } from '@/composables/useListingLayout'
import { setDocumentTitle } from '@/composables/usePageTitle'
import { pruneFilters, routeFilters } from '@/composables/useRouteFilters'
import { useAdsStore } from '@/stores/ads'
import type { AdFilters, AdSort, SellerProfile } from '@/types/api'

const props = defineProps<{ sellerSlug: string }>()

const route = useRoute()
const router = useRouter()
const adsStore = useAdsStore()
const { layout, setLayout } = useListingLayout()

const { ads, meta, isLoading, error, isEmpty } = storeToRefs(adsStore)

const seller = ref<SellerProfile | null>(null)
const sellerError = ref<string | null>(null)
const isSellerLoading = ref(true)

const filters = computed<AdFilters>(() => ({
  ...routeFilters(route.query),
  seller: props.sellerSlug,
}))

const sellerInitials = computed(() => {
  const name = seller.value?.name ?? ''
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')
})

const layoutOptions = [
  { label: 'Siatka', value: 'grid', icon: 'pi pi-th-large' },
  { label: 'Lista', value: 'list', icon: 'pi pi-list' },
]

const sortOptions = computed<{ label: string; value: AdSort }[]>(() => [
  { label: 'Najnowsze', value: 'newest' },
  { label: 'Najtańsze', value: 'price_asc' },
  { label: 'Najdroższe', value: 'price_desc' },
])

async function changeFilters(patch: Partial<AdFilters>): Promise<void> {
  const next = { ...filters.value, ...patch, page: patch.page ?? undefined }

  await router
    .push({
      name: 'sellers.show',
      params: { sellerSlug: props.sellerSlug },
      query: pruneFilters({
        q: next.q,
        location: next.location,
        price_min: next.price_min,
        price_max: next.price_max,
        negotiable: next.negotiable,
        free: next.free,
        condition: next.condition,
        delivery: next.delivery,
        sort: next.sort,
        page: next.page,
      }),
    })
    .catch(() => undefined)
}

async function loadSeller(): Promise<void> {
  isSellerLoading.value = true
  sellerError.value = null

  if (props.sellerSlug === '') {
    sellerError.value = 'Nieprawidłowy adres sprzedawcy.'
    isSellerLoading.value = false
    return
  }

  try {
    seller.value = await fetchSeller(props.sellerSlug)
    setDocumentTitle(`Ogłoszenia — ${seller.value.name}`)
  } catch (caught: unknown) {
    sellerError.value = errorMessage(caught, 'Nie znaleziono sprzedawcy.')
  } finally {
    isSellerLoading.value = false
  }
}

onMounted(async () => {
  await loadSeller()
  if (seller.value !== null) {
    await adsStore.search(filters.value)
  }
})

watch(
  () => props.sellerSlug,
  async () => {
    await loadSeller()
    if (seller.value !== null) {
      await adsStore.search(filters.value)
    }
  },
)

watch(filters, (next) => {
  if (seller.value !== null) {
    void adsStore.search(next)
  }
})
</script>

<template>
  <Message
    v-if="sellerError || (!isSellerLoading && !seller)"
    severity="error"
  >
    {{ sellerError ?? 'Nie znaleziono sprzedawcy.' }}
  </Message>

  <div
    v-else
    class="page"
  >
    <section class="surface seller-hero">
      <div
        v-if="isSellerLoading"
        class="seller-hero__loading"
      >
        <i class="pi pi-spin pi-spinner" />
      </div>

      <template v-else-if="seller">
        <Avatar
          v-if="seller.avatar_url"
          :image="seller.avatar_url"
          :alt="`Avatar ${seller.name}`"
          shape="circle"
          size="xlarge"
          class="seller-hero__avatar"
        />
        <Avatar
          v-else
          :label="sellerInitials"
          shape="circle"
          size="xlarge"
          class="seller-hero__avatar seller-hero__avatar--fallback"
        />

        <div class="seller-hero__body">
          <h1 class="seller-hero__name">
            {{ seller.name }}
          </h1>
          <p
            v-if="seller.member_since"
            class="seller-hero__since"
          >
            W serwisie od {{ seller.member_since }}
          </p>
          <p
            v-if="seller.bio"
            class="seller-hero__bio"
          >
            {{ seller.bio }}
          </p>
          <p class="seller-hero__count">
            {{ seller.active_ads_count }} aktywnych ogłoszeń
          </p>
        </div>
      </template>
    </section>

    <section class="surface results">
      <header class="results__header">
        <h2 class="results__title">
          Ogłoszenia sprzedawcy
          <span
            v-if="meta"
            class="results__count"
          >{{ meta.total }}</span>
        </h2>
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
          :model-value="filters.sort ?? 'newest'"
          :options="sortOptions"
          option-label="label"
          option-value="value"
          aria-label="Sortowanie"
          size="small"
          @update:model-value="changeFilters({ sort: $event as AdSort })"
        />
      </div>

      <div
        v-if="isLoading"
        :class="layout === 'grid' ? 'results__grid' : 'results__list'"
      >
        <template v-if="layout === 'grid'">
          <AdCardSkeleton
            v-for="n in 6"
            :key="n"
          />
        </template>
        <template v-else>
          <AdListItemSkeleton
            v-for="n in 4"
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
        icon="pi pi-inbox"
        title="Brak aktywnych ogłoszeń"
        description="Ten sprzedawca nie ma teraz żadnych opublikowanych ofert."
        class="results__block"
      />

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
  </div>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: var(--stack-gap);
  min-width: 0;
  max-width: 100%;
}

.seller-hero {
  display: flex;
  gap: 1.25rem;
  align-items: flex-start;
  padding: var(--card-padding);
}

.seller-hero__loading {
  display: flex;
  justify-content: center;
  width: 100%;
  padding: 1rem;
  color: var(--text-muted);
}

.seller-hero__avatar {
  flex-shrink: 0;
}

.seller-hero__avatar--fallback {
  background: color-mix(in srgb, var(--p-primary-color) 15%, transparent);
  color: var(--p-primary-color);
  font-weight: 700;
}

.seller-hero__body {
  min-width: 0;
}

.seller-hero__name {
  margin: 0 0 0.35rem;
  font-size: clamp(1.25rem, 1rem + 1vw, 1.65rem);
  line-height: 1.25;
}

.seller-hero__since,
.seller-hero__count {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.seller-hero__bio {
  margin: 0 0 0.75rem;
  line-height: 1.6;
  white-space: pre-line;
}

.results {
  overflow: hidden;
}

.results__header {
  padding: var(--card-padding);
  border-bottom: 1px solid var(--surface-border);
}

.results__title {
  margin: 0;
  font-size: 1.2rem;
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

.results__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.875rem var(--card-padding);
  border-bottom: 1px solid var(--surface-border);
  min-width: 0;
}

.results__toolbar :deep(.p-select),
.results__toolbar :deep(.p-selectbutton) {
  min-width: 0;
  max-width: 100%;
}

.results__layout-label {
  display: none;
}

@media (width >= 30rem) {
  .results__layout-label {
    display: inline;
  }
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

@media (width >= 62rem) {
  .results__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
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