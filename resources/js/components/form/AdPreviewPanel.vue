<script setup lang="ts">
import { computed } from 'vue'

import AdBadge from '@/components/ads/AdBadge.vue'
import { formatPrice } from '@/composables/useFormatting'
import { deliveryLabel, deliveryMethodsInOrder } from '@/composables/useOfferLabels'
import type { AdFormValues, DeliveryMethod } from '@/types/api'

const props = defineProps<{ values: AdFormValues; previewImage: string | null }>()

const price = computed(() => (props.values.price === null ? null : props.values.price))

const locationLabel = computed(() => props.values.location)
const selectedDeliveryMethods = computed(() => deliveryMethodsInOrder(props.values.delivery_methods))

/**
 * Pokazujemy wyłącznie wybrane metody. Brak ceny nie znaczy, że metoda znika:
 * dla dostawy płatnej autor może chcieć ustalić koszt indywidualnie.
 */
function priceFor(method: DeliveryMethod): string | null {
  const raw = props.values.delivery_prices[method]
  if (raw === undefined || raw === '') {
    return null
  }

  // MoneyInput trzyma kwotę kanonicznie („12.50”); kupujący widzi „12,50 zł”.
  const amount = Number(raw)

  return Number.isNaN(amount) ? null : formatPrice(amount)
}
</script>

<template>
  <aside class="surface preview">
    <h2 class="preview__heading">
      Podgląd ogłoszenia
    </h2>

    <div class="preview__head">
      <img
        v-if="previewImage"
        :src="previewImage"
        alt=""
        class="preview__image"
      >
      <div
        v-else
        class="preview__image preview__image--empty"
      >
        <i class="pi pi-image" />
      </div>

      <div class="preview__summary">
        <p class="preview__title">
          {{ values.title || 'Tytuł ogłoszenia' }}
        </p>
        <p class="preview__pricing">
          <span class="preview__price">{{ formatPrice(price) }}</span>
          <AdBadge
            v-if="values.is_negotiable"
            label="Do negocjacji"
            tone="info"
          />
        </p>
        <p
          v-if="locationLabel"
          class="preview__location"
        >
          <i class="pi pi-map-marker" />{{ locationLabel }}
        </p>
      </div>
    </div>

    <section class="preview__block">
      <h3 class="preview__legend">
        Dostawa i odbiór
      </h3>
      <dl
        v-if="selectedDeliveryMethods.length > 0"
        class="preview__delivery"
      >
        <div
          v-for="method in selectedDeliveryMethods"
          :key="method"
          class="preview__delivery-row"
        >
          <dt>{{ deliveryLabel(method) }}</dt>
          <dd>
            <AdBadge
              v-if="priceFor(method)"
              :label="priceFor(method) as string"
              tone="success"
            />
            <span
              v-else
              class="preview__dash"
            >Koszt do ustalenia</span>
          </dd>
        </div>
      </dl>
      <p
        v-else
        class="preview__delivery-empty"
      >
        Brak wybranych metod dostawy.
      </p>
    </section>

    <section class="preview__block">
      <h3 class="preview__legend">
        Opis
      </h3>
      <p class="preview__description">
        {{ values.description || 'Tu pojawi się treść ogłoszenia.' }}
      </p>
    </section>
  </aside>
</template>

<style scoped>
.preview {
  padding: var(--card-padding);
}

.preview__heading {
  margin: 0 0 1rem;
  font-size: var(--title-card);
  font-weight: 700;
}

.preview__head {
  display: flex;
  gap: 0.875rem;
}

.preview__image {
  width: 7rem;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  border-radius: 0.5rem;
  flex-shrink: 0;
  background: var(--surface-muted);
}

.preview__image--empty {
  display: grid;
  place-items: center;
  color: var(--text-muted);
  opacity: 0.4;
  font-size: 1.5rem;
}

.preview__summary {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.preview__title {
  margin: 0;
  font-weight: 600;
  line-height: 1.35;
}

.preview__pricing {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
}

.preview__price {
  font-size: 1.125rem;
  font-weight: 700;
}

.preview__location {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  margin: 0;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.preview__block {
  margin-top: 1.125rem;
  padding-top: 1.125rem;
  border-top: 1px solid var(--surface-border);
}

.preview__legend {
  margin: 0 0 0.625rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.preview__delivery {
  margin: 0;
}

.preview__delivery-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-block: 0.35rem;
  font-size: 0.8125rem;
}

.preview__delivery-row dt {
  color: var(--text-muted);
}

.preview__delivery-row dd {
  margin: 0;
}

.preview__dash {
  color: var(--text-muted);
}

.preview__delivery-empty {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.preview__description {
  margin: 0;
  white-space: pre-line;
  font-size: 0.8125rem;
  line-height: 1.6;
  color: var(--text-muted);
}
</style>
