<script setup lang="ts">
import AdBadge from '@/components/ads/AdBadge.vue'
import DeliveryMethodIcons from '@/components/ads/DeliveryMethodIcons.vue'
import { formatAddedAt, formatPrice } from '@/composables/useFormatting'
import { locationLabel } from '@/composables/useOfferLabels'
import type { AdSummary } from '@/types/api'

defineProps<{ ad: AdSummary }>()
</script>

<template>
  <RouterLink
    :to="{ name: 'ads.show', params: { slug: ad.slug } }"
    class="row"
  >
    <div class="row__media">
      <img
        v-if="ad.primary_image_url"
        :src="ad.primary_image_url"
        :alt="ad.title"
        loading="lazy"
        decoding="async"
        class="row__img"
      >
      <div
        v-else
        data-testid="image-placeholder"
        class="row__img row__img--empty"
        aria-hidden="true"
      >
        <i class="pi pi-image" />
      </div>
    </div>

    <div class="row__body">
      <h3 class="row__title">
        {{ ad.title }}
      </h3>

      <p class="row__pricing">
        <span class="row__price">{{ formatPrice(ad.price) }}</span>
        <AdBadge
          v-if="ad.is_negotiable"
          label="Do negocjacji"
          tone="info"
        />
      </p>

      <p
        v-if="ad.location"
        data-testid="location"
        class="row__location"
      >
        <i class="pi pi-map-marker" />{{ locationLabel(ad.location) }}
      </p>

      <DeliveryMethodIcons
        v-if="ad.delivery_methods.length > 0"
        :methods="ad.delivery_methods"
        compact
        class="row__delivery"
      />

      <p class="row__added">
        {{ formatAddedAt(ad.published_at) }}
      </p>
    </div>
  </RouterLink>
</template>

<style scoped>
.row {
  display: flex;
  align-items: flex-start;
  gap: 1.125rem;
  min-width: 0;
  max-width: 100%;
  padding: var(--card-padding);
  text-decoration: none;
  color: inherit;
  border: 1px solid transparent;
  border-bottom-color: var(--surface-border);
  border-radius: var(--card-radius);
  transition:
    background 0.15s ease,
    border-color 0.15s ease;
}

.row:last-child {
  border-bottom-color: transparent;
}

.row:hover {
  background: color-mix(in srgb, var(--p-primary-color) 4%, transparent);
  border-color: color-mix(in srgb, var(--p-primary-color) 45%, transparent);
}

.row__media {
  flex-shrink: 0;
  width: 7rem;
}

@media (width >= 40rem) {
  .row__media {
    width: 11rem;
  }
}

.row__img {
  width: 100%;
  aspect-ratio: 4 / 3;
  object-fit: cover;
  border-radius: 0.5rem;
  background: var(--surface-muted);
}

.row__img--empty {
  display: grid;
  place-items: center;
  font-size: 1.5rem;
  color: var(--text-muted);
  opacity: 0.4;
}

.row__body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 0;
}

.row__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.row__pricing {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  margin: 0;
}

.row__price {
  font-size: 1.125rem;
  font-weight: 700;
}

.row__location,
.row__added {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.row__location {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.row__delivery {
  margin-top: -0.125rem;
}

.row__added {
  margin-top: auto;
  font-size: 0.75rem;
}
</style>
