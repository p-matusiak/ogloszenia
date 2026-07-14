<script setup lang="ts">
import Button from 'primevue/button'

import { formatAdCount } from '@/composables/useFormatting'
import type { FilterChip } from '@/types/ui'

defineProps<{ chips: FilterChip[]; resultCount: number | null }>()

const emit = defineEmits<{
  remove: [key: string]
  clear: []
}>()
</script>

<template>
  <div
    v-if="chips.length > 0 || resultCount !== null"
    class="filters"
  >
    <p
      v-if="resultCount !== null"
      class="filters__count"
    >
      {{ formatAdCount(resultCount) }}
    </p>

    <button
      v-for="chip in chips"
      :key="chip.key"
      type="button"
      class="chip"
      :class="`chip--${chip.tone ?? 'neutral'}`"
      :aria-label="`Usuń filtr ${chip.label}`"
      @click="emit('remove', chip.key)"
    >
      <span class="chip__label">{{ chip.label }}</span>
      <i class="pi pi-times chip__remove" />
    </button>

    <Button
      v-if="chips.length > 0"
      label="Wyczyść filtry"
      text
      size="small"
      @click="emit('clear')"
    />
  </div>
</template>

<style scoped>
.filters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.filters__count {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.chip {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.7rem;
  border-radius: 0.5rem;
  border: 1px solid var(--surface-border);
  background: var(--surface-card);
  color: var(--text-strong);
  font: inherit;
  font-size: 0.8125rem;
  cursor: pointer;
}

.chip__remove {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.chip--info {
  border-color: transparent;
  background: color-mix(in srgb, var(--brand-orange) 11%, var(--surface-muted));
  color: color-mix(in srgb, var(--brand-orange) 82%, var(--text-strong));
}

.chip--success {
  border-color: color-mix(in srgb, var(--tone-success) 40%, transparent);
  background: color-mix(in srgb, var(--tone-success) 8%, transparent);
  color: var(--tone-success-text);
}

.chip--info .chip__remove,
.chip--success .chip__remove {
  color: inherit;
}
</style>
