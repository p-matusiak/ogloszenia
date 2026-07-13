<script setup lang="ts">
import Button from 'primevue/button'

import { formatAddedAt } from '@/composables/useFormatting'

defineProps<{ id: number; publishedAt: string | null; viewsCount: number }>()

const emit = defineEmits<{ report: [] }>()
</script>

<template>
  <div class="surface meta">
    <dl class="meta__list">
      <div class="meta__row">
        <dt><i class="pi pi-calendar" /> Dodano</dt>
        <dd>{{ formatAddedAt(publishedAt).replace('Dodano ', '') }}</dd>
      </div>
      <div class="meta__row">
        <dt><i class="pi pi-hashtag" /> ID ogłoszenia</dt>
        <dd>{{ id }}</dd>
      </div>
      <div class="meta__row">
        <dt><i class="pi pi-eye" /> Wyświetlenia</dt>
        <dd>{{ viewsCount }}</dd>
      </div>
    </dl>

    <Button
      label="Zgłoś naruszenie"
      icon="pi pi-exclamation-triangle"
      severity="danger"
      text
      size="small"
      class="meta__report"
      @click="emit('report')"
    />
  </div>
</template>

<style scoped>
.meta {
  display: flex;
  flex-direction: column;
  /* Pierwszy wiersz wnosi własne 0.75rem, więc góra dopełnia je do --card-padding. */
  padding: 0.5rem var(--card-padding) 0.75rem;
}

.meta__list {
  margin: 0;
}

.meta__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-block: 0.75rem;
  font-size: 0.875rem;
  border-bottom: 1px solid var(--surface-border);
}

/* Ostatni wiersz nie zamyka listy kreską — pod nią jest już tylko przycisk. */
.meta__row:last-child {
  border-bottom: 0;
}

.meta__row dt {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-muted);
}

.meta__row dd {
  margin: 0;
  font-weight: 600;
}

/* Własny padding przycisku tekstowego cofa etykietę do lewej krawędzi listy. */
.meta__report {
  align-self: flex-start;
  margin-top: 0.5rem;
  margin-inline-start: -0.5rem;
}
</style>
