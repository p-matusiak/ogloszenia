<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { formatPrice } from '@/composables/useFormatting'
import {
  deliveryIcon,
  deliveryLabel,
  deliveryMethodsInOrder,
  isFreeDeliveryMethod,
} from '@/composables/useOfferLabels'
import type { DeliveryMethod } from '@/types/api'

const props = withDefaults(
  defineProps<{
    methods?: DeliveryMethod[]
    prices?: Partial<Record<DeliveryMethod, string>>
  }>(),
  {
    methods: () => [],
    prices: () => ({}),
  },
)

const { t } = useI18n()

const orderedMethods = computed(() => deliveryMethodsInOrder(props.methods))

function priceLabel(method: DeliveryMethod): string {
  if (isFreeDeliveryMethod(method)) {
    return t('filters.free')
  }

  const raw = props.prices[method]
  if (raw === undefined || raw === '') {
    return '—'
  }

  const amount = Number(raw)

  return Number.isNaN(amount) ? '—' : formatPrice(amount)
}
</script>

<template>
  <section
    v-if="orderedMethods.length > 0"
    class="surface delivery-panel"
  >
    <h2 class="delivery-panel__heading">
      {{ t('adDetail.deliveryHeading') }}
    </h2>

    <dl class="delivery-panel__list">
      <div
        v-for="method in orderedMethods"
        :key="method"
        class="delivery-panel__row"
      >
        <dt class="delivery-panel__name">
          <i
            :class="deliveryIcon(method)"
            aria-hidden="true"
          />
          {{ deliveryLabel(method) }}
        </dt>
        <dd class="delivery-panel__price">
          {{ priceLabel(method) }}
        </dd>
      </div>
    </dl>
  </section>
</template>

<style scoped>
.delivery-panel {
  padding: var(--card-padding);
}

.delivery-panel__heading {
  margin: 0 0 0.75rem;
  font-size: var(--title-card);
  font-weight: 700;
}

.delivery-panel__list {
  margin: 0;
}

.delivery-panel__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--surface-border);
}

.delivery-panel__row:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.delivery-panel__row:first-child {
  padding-top: 0;
}

.delivery-panel__name {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  font-weight: 600;
}

.delivery-panel__price {
  margin: 0;
  color: var(--text-muted);
  font-size: 0.9375rem;
  white-space: nowrap;
}
</style>