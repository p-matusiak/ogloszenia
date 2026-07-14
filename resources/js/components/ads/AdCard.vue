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
    class="card"
  >
    <div class="card__media">
      <img
        v-if="ad.primary_image_url"
        :src="ad.primary_image_url"
        :alt="ad.title"
        loading="lazy"
        decoding="async"
        class="card__image"
      >
      <div
        v-else
        data-testid="image-placeholder"
        class="card__image card__image--empty"
        aria-hidden="true"
      >
        <i class="pi pi-image" />
      </div>
    </div>

    <div class="card__body">
      <h3 class="card__title">
        {{ ad.title }}
      </h3>

      <p class="card__pricing">
        <span class="card__price">{{ formatPrice(ad.price) }}</span>
        <AdBadge
          v-if="ad.is_negotiable"
          label="Do negocjacji"
          tone="info"
        />
      </p>

      <p
        v-if="ad.location"
        data-testid="location"
        class="card__meta"
      >
        <i class="pi pi-map-marker" />{{ locationLabel(ad.location) }}
      </p>

      <DeliveryMethodIcons
        v-if="ad.delivery_methods.length > 0"
        :methods="ad.delivery_methods"
        class="card__delivery"
      />

      <p class="card__added">
        {{ formatAddedAt(ad.published_at) }}
      </p>
    </div>
  </RouterLink>
</template>

<style scoped>
.card {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-width: 0;
  max-width: 100%;
  text-decoration: none;
  color: inherit;
  border: 1px solid var(--surface-border);
  border-radius: var(--card-radius);
  background: var(--surface-card);
  overflow: hidden;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease,
    transform 0.15s ease;
}

.card:hover {
  border-color: color-mix(in srgb, var(--brand-orange) 55%, transparent);
  box-shadow: var(--shadow-card-hover);
}

.card:hover .card__title {
  color: var(--brand-blue);
}

.card__media {
  aspect-ratio: 4 / 3;
  background: var(--surface-muted);
}

.card__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card__image--empty {
  display: grid;
  place-items: center;
  font-size: 2rem;
  color: var(--text-muted);
  opacity: 0.35;
}

.card__body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.875rem 1rem 1rem;
  flex: 1;
}

.card__title {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  transition: color 0.15s ease;
}

.card__pricing {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 0;
}

.card__price {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-strong);
}

.card__meta {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--text-muted);
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.card__delivery {
  margin-top: -0.125rem;
}

.card__added {
  margin: auto 0 0;
  font-size: 0.75rem;
  color: var(--text-muted);
}
</style>