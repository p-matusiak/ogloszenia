<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import {
  deliveryIcon,
  deliveryLabel,
  deliveryMethodsInOrder,
} from '@/composables/useOfferLabels'
import type { DeliveryMethod } from '@/types/api'

const props = withDefaults(
  defineProps<{
    methods: DeliveryMethod[]
    /** Mniejsze kółka w poziomym rzędzie — widok listy. */
    compact?: boolean
  }>(),
  { compact: false },
)

const { t } = useI18n()

const orderedMethods = computed(() => deliveryMethodsInOrder(props.methods))
</script>

<template>
  <ul
    v-if="orderedMethods.length > 0"
    class="delivery-icons"
    :class="{ 'delivery-icons--compact': compact }"
    role="list"
    :aria-label="t('filters.delivery')"
  >
    <li
      v-for="method in orderedMethods"
      :key="method"
    >
      <span
        v-tooltip.top="{ value: deliveryLabel(method), showDelay: 250 }"
        class="delivery-icons__item"
        :aria-label="deliveryLabel(method)"
      >
        <i
          :class="deliveryIcon(method)"
          aria-hidden="true"
        />
      </span>
    </li>
  </ul>
</template>

<style scoped>
.delivery-icons {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.delivery-icons--compact {
  gap: 0.3rem;
}

.delivery-icons__item {
  display: inline-grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  border: 1px solid color-mix(in srgb, var(--brand-blue) 28%, var(--surface-border));
  background: var(--surface-muted);
  color: var(--brand-blue);
  font-size: 0.875rem;
  flex-shrink: 0;
  transition:
    border-color 0.15s ease,
    color 0.15s ease,
    background 0.15s ease;
}

.delivery-icons--compact .delivery-icons__item {
  width: 1.75rem;
  height: 1.75rem;
  font-size: 0.8125rem;
}

.delivery-icons__item:hover {
  border-color: color-mix(in srgb, var(--brand-orange) 55%, var(--surface-border));
  background: color-mix(in srgb, var(--brand-orange) 8%, var(--surface-muted));
  color: var(--brand-orange);
}
</style>