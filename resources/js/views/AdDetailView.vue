<script setup lang="ts">
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'

import { computed, onMounted, ref } from 'vue'

import { errorMessage } from '@/api/client'
import { fetchAd } from '@/api/modules/v1/ads'
import AdReportPanel from '@/components/AdReportPanel.vue'
import AdDetailSkeleton from '@/components/ads/AdDetailSkeleton.vue'
import AdGallery from '@/components/ads/AdGallery.vue'
import AdMetaPanel from '@/components/ads/AdMetaPanel.vue'
import SellerCard from '@/components/ads/SellerCard.vue'
import { formatPrice } from '@/composables/useFormatting'
import { locationLabel } from '@/composables/useOfferLabels'
import { setDocumentTitle } from '@/composables/usePageTitle'
import type { Ad, Category } from '@/types/api'

const props = defineProps<{ slug: string }>()

const ad = ref<Ad | null>(null)
const isLoading = ref(true)
const loadError = ref<string | null>(null)
const isReportOpen = ref(false)
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

onMounted(async () => {
  try {
    ad.value = await fetchAd(props.slug)
    // Router nie zna tytułu ogłoszenia — zna go dopiero odpowiedź API. Kształt
    // „tytuł – lokalizacja” odwzorowuje `AdSeoText::title()` z backendu.
    setDocumentTitle(
      ad.value.location ? `${ad.value.title} – ${ad.value.location}` : ad.value.title,
    )
  } catch (caught: unknown) {
    loadError.value = errorMessage(caught, 'Nie znaleziono ogłoszenia.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <AdDetailSkeleton v-if="isLoading" />

  <Message
    v-else-if="loadError || !ad"
    severity="error"
  >
    {{ loadError ?? 'Nie znaleziono ogłoszenia.' }}
  </Message>

  <article v-else>
    <nav
      class="crumbs"
      aria-label="Ścieżka kategorii"
    >
      <RouterLink :to="{ name: 'home' }">
        Strona główna
      </RouterLink>
      <template
        v-for="node in breadcrumb"
        :key="node.id"
      >
        <span aria-hidden="true">›</span>
        <RouterLink :to="{ name: 'home', query: { category: node.slug } }">
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

          <p class="detail__price">
            {{ formatPrice(ad.price) }}
          </p>

          <p
            v-if="ad.location"
            class="detail__location"
          >
            <i class="pi pi-map-marker" />{{ locationLabel(ad.location, ad.district) }}
          </p>

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
          :contact-email="ad.contact_email"
          :has-phone="ad.has_phone"
          :masked-phone="ad.contact_phone_masked"
        />

        <AdMetaPanel
          :id="ad.id"
          :published-at="ad.published_at"
          :views-count="ad.views_count"
          @report="isReportOpen = true"
        />
      </aside>
    </div>

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
  </article>
</template>

<style scoped>
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
</style>
