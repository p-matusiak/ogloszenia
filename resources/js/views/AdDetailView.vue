<script setup lang="ts">
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'

import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { errorMessage } from '@/api/client'
import { fetchAd, fetchMoreFromSeller } from '@/api/modules/v1/ads'
import { listingLocation } from '@/composables/useRouteFilters'
import AdReportPanel from '@/components/AdReportPanel.vue'
import SendMessagePanel from '@/components/messages/SendMessagePanel.vue'
import AdDetailSkeleton from '@/components/ads/AdDetailSkeleton.vue'
import FavoriteButton from '@/components/ads/FavoriteButton.vue'
import AdGallery from '@/components/ads/AdGallery.vue'
import AdCard from '@/components/ads/AdCard.vue'
import AdDeliveryPanel from '@/components/ads/AdDeliveryPanel.vue'
import AdMetaPanel from '@/components/ads/AdMetaPanel.vue'
import SellerCard from '@/components/ads/SellerCard.vue'
import { formatPrice } from '@/composables/useFormatting'
import { locationLabel } from '@/composables/useOfferLabels'
import { setDocumentTitle } from '@/composables/usePageTitle'
import { useAuthStore } from '@/stores/auth'
import type { Ad, AdSummary, Category } from '@/types/api'

const props = defineProps<{ slug: string }>()

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const ad = ref<Ad | null>(null)
const isLoading = ref(true)
const loadError = ref<string | null>(null)
const isReportOpen = ref(false)
const isMessageOpen = ref(false)
const isDescriptionExpanded = ref(false)

const DESCRIPTION_CLAMP = 320

/** `ancestors` przychodzi od najbliższego przodka; breadcrumb czyta się od korzenia. */
const breadcrumb = computed<Category[]>(() => {
  const category = ad.value?.category
  if (!category) {
    return []
  }

  return [...(category.ancestors ?? [])].reverse().concat(category)
})

const isDescriptionLong = computed(() => (ad.value?.description.length ?? 0) > DESCRIPTION_CLAMP)

/** Serduszko pokazujemy na aktywnym ogłoszeniu; gość po kliknięciu trafia na logowanie. */
const showFavorite = computed(() => ad.value?.status === 'active')

/** Przycisk wiadomości na aktywnym, cudzym ogłoszeniu — gość trafia na logowanie. */
const showMessage = computed(
  () => ad.value?.status === 'active' && ad.value.is_own === false,
)

async function onMessageClick(): Promise<void> {
  if (!auth.isAuthenticated) {
    await router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }

  isMessageOpen.value = true
}

const moreFromSeller = ref<AdSummary[]>([])

const showMoreFromSeller = computed(
  () => moreFromSeller.value.length > 0 && ad.value?.seller?.slug,
)

const deliveryMethods = computed(() => ad.value?.delivery_methods ?? [])

const deliveryPrices = computed(() => ad.value?.delivery_prices ?? {})

async function loadMoreFromSeller(slug: string): Promise<void> {
  try {
    moreFromSeller.value = await fetchMoreFromSeller(slug)
  } catch {
    moreFromSeller.value = []
  }
}

async function loadAd(slug: string): Promise<void> {
  isLoading.value = true
  loadError.value = null
  isDescriptionExpanded.value = false
  isReportOpen.value = false
  isMessageOpen.value = false
  moreFromSeller.value = []
  ad.value = null

  try {
    const loaded = await fetchAd(slug)
    ad.value = loaded
    // Router nie zna tytułu ogłoszenia — zna go dopiero odpowiedź API. Kształt
    // „tytuł – lokalizacja” odwzorowuje `AdSeoText::title()` z backendu.
    setDocumentTitle(
      loaded.location ? `${loaded.title} – ${loaded.location}` : loaded.title,
    )

    if (loaded.status === 'active') {
      void loadMoreFromSeller(slug)
    }
  } catch (caught: unknown) {
    loadError.value = errorMessage(caught, 'Nie znaleziono ogłoszenia.')
  } finally {
    isLoading.value = false
  }
}

/** Ta sama trasa `ads.show` — bez watcha klik w „Więcej od sprzedawcy” nie odświeży widoku. */
watch(() => props.slug, (slug) => {
  void loadAd(slug)
}, { immediate: true })
</script>

<template>
  <AdDetailSkeleton v-if="isLoading" />

  <Message
    v-else-if="loadError || !ad"
    severity="error"
  >
    {{ loadError ?? 'Nie znaleziono ogłoszenia.' }}
  </Message>

  <article
    v-else
    class="detail-page"
  >
    <nav
      class="crumbs"
      aria-label="Ścieżka kategorii"
    >
      <RouterLink :to="{ name: 'listings' }">
        Strona główna
      </RouterLink>
      <template
        v-for="node in breadcrumb"
        :key="node.id"
      >
        <span aria-hidden="true">›</span>
        <RouterLink :to="listingLocation({ category: node.slug })">
          {{ node.name }}
        </RouterLink>
      </template>
    </nav>

    <div class="detail">
      <div class="detail__main">
        <AdGallery
          :images="ad.images ?? []"
          :title="ad.title"
        />

        <div class="surface detail__body">
          <h1 class="detail__title">
            {{ ad.title }}
          </h1>

          <div class="detail__headline">
            <p class="detail__price">
              {{ formatPrice(ad.price) }}
            </p>
            <FavoriteButton
              v-if="showFavorite"
              :ad-id="ad.id"
              :ad-slug="ad.slug"
            />
          </div>

          <p
            v-if="ad.location"
            class="detail__location"
          >
            <i class="pi pi-map-marker" />{{ locationLabel(ad.location) }}
          </p>

          <AdDeliveryPanel
            :methods="deliveryMethods"
            :prices="deliveryPrices"
            class="detail__delivery detail__delivery--main"
          />

          <h2 class="detail__legend">
            Opis
          </h2>
          <p
            class="detail__description"
            :class="{ 'detail__description--clamped': isDescriptionLong && !isDescriptionExpanded }"
          >
            {{ ad.description }}
          </p>
          <button
            v-if="isDescriptionLong"
            type="button"
            class="detail__more"
            @click="isDescriptionExpanded = !isDescriptionExpanded"
          >
            {{ isDescriptionExpanded ? 'Pokaż mniej' : 'Pokaż więcej' }}
          </button>
        </div>
      </div>

      <aside class="detail__side">
        <SellerCard
          :slug="slug"
          :seller="ad.seller"
          :has-phone="ad.has_phone"
          :masked-phone="ad.contact_phone_masked"
          :show-message="showMessage"
          @message="onMessageClick"
        />

        <AdDeliveryPanel
          :methods="deliveryMethods"
          :prices="deliveryPrices"
          class="detail__delivery detail__delivery--side"
        />

        <AdMetaPanel
          :id="ad.id"
          :published-at="ad.published_at"
          :views-count="ad.views_count"
          @report="isReportOpen = true"
        />
      </aside>
    </div>

    <section
      v-if="showMoreFromSeller"
      class="more-seller surface"
    >
      <div class="more-seller__head">
        <h2 class="more-seller__title">
          Więcej od {{ ad.seller?.name }}
        </h2>
        <RouterLink
          :to="{ name: 'sellers.show', params: { sellerSlug: ad.seller?.slug ?? '' } }"
          class="more-seller__all"
        >
          Zobacz wszystkie
        </RouterLink>
      </div>

      <div class="more-seller__grid">
        <AdCard
          v-for="item in moreFromSeller"
          :key="item.id"
          :ad="item"
        />
      </div>
    </section>

    <Dialog
      v-model:visible="isReportOpen"
      header="Zgłoś naruszenie"
      modal
      :style="{ width: 'min(28rem, 92vw)' }"
    >
      <AdReportPanel
        :slug="slug"
        @sent="isReportOpen = false"
      />
    </Dialog>

    <Dialog
      v-model:visible="isMessageOpen"
      header="Wyślij wiadomość"
      modal
      :style="{ width: 'min(28rem, 92vw)' }"
    >
      <SendMessagePanel
        :slug="slug"
        @sent="isMessageOpen = false"
      />
    </Dialog>
  </article>
</template>

<style scoped>
.detail-page {
  min-width: 0;
  max-width: 100%;
  overflow-x: clip;
}

.crumbs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  padding-bottom: 1rem;
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

.detail {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--stack-gap);
  align-items: start;
}

@media (width >= 62rem) {
  .detail {
    grid-template-columns: minmax(0, 1fr) 22rem;
  }

  .detail__side {
    position: sticky;
    top: 6rem;
  }
}

.detail__main {
  display: flex;
  flex-direction: column;
  gap: var(--stack-gap);
  min-width: 0;
}

.detail__body {
  padding: var(--card-padding);
}

.detail__title {
  margin: 0 0 0.5rem;
  font-size: clamp(1.35rem, 1rem + 1.4vw, 1.85rem);
  line-height: 1.25;
}

.detail__headline {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  min-width: 0;
}

.detail__price {
  margin: 0 0 0.75rem;
  font-size: 1.75rem;
  font-weight: 700;
}

.detail__location {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0 0 1.25rem;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.detail__legend {
  margin: 0 0 0.5rem;
  font-size: var(--title-card);
  font-weight: 700;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-border);
}

.detail__description {
  margin: 0;
  white-space: pre-line;
  line-height: 1.7;
  overflow-wrap: anywhere;
}

.detail__description--clamped {
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.detail__more {
  margin-top: 0.5rem;
  padding: 0;
  border: 0;
  background: none;
  color: var(--p-primary-color);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

.detail__side {
  display: flex;
  flex-direction: column;
  gap: var(--stack-gap);
}

.detail__delivery--main {
  display: block;
}

.detail__delivery--side {
  display: none;
}

@media (width >= 62rem) {
  .detail__delivery--main {
    display: none;
  }

  .detail__delivery--side {
    display: block;
  }
}

.more-seller {
  margin-top: var(--stack-gap);
  padding: var(--card-padding);
}

.more-seller__head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.more-seller__title {
  margin: 0;
  font-size: var(--title-card);
  font-weight: 700;
}

.more-seller__all {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--p-primary-color);
  text-decoration: none;
}

.more-seller__all:hover {
  text-decoration: underline;
}

.more-seller__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

@media (width >= 36rem) {
  .more-seller__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (width >= 62rem) {
  .more-seller__grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}
</style>
