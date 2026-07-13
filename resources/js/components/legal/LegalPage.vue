<script setup lang="ts">
import { LAST_UPDATED } from '@/config/legal'

defineProps<{ title: string }>()
</script>

<template>
  <article class="surface legal">
    <header class="legal__header">
      <h1 class="legal__title">
        {{ title }}
      </h1>
      <p class="legal__updated">
        Ostatnia aktualizacja: {{ LAST_UPDATED }}
      </p>
    </header>

    <div class="legal__body">
      <slot />
    </div>
  </article>
</template>

<style scoped>
.legal {
  max-width: 48rem;
  margin: 0 auto;
  padding: var(--card-padding);
}

.legal__header {
  padding-bottom: 1rem;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid var(--surface-border);
}

.legal__title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.legal__updated {
  margin: 0.375rem 0 0;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.legal__body {
  line-height: 1.7;
}

.legal__body :deep(h2) {
  margin: 2rem 0 0.75rem;
  font-size: 1.125rem;
  font-weight: 700;
}

.legal__body :deep(h2:first-child) {
  margin-top: 0;
}

.legal__body :deep(h3) {
  margin: 1.5rem 0 0.5rem;
  font-size: 1rem;
  font-weight: 600;
}

.legal__body :deep(p) {
  margin: 0 0 0.875rem;
}

.legal__body :deep(ul),
.legal__body :deep(ol) {
  margin: 0 0 0.875rem;
  padding-left: 1.5rem;
}

.legal__body :deep(li) {
  margin-bottom: 0.375rem;
}

.legal__body :deep(dl) {
  margin: 0 0 0.875rem;
  padding-left: 1rem;
  border-left: 2px solid var(--surface-border);
}

.legal__body :deep(dt) {
  font-weight: 600;
  margin-top: 0.75rem;
}

.legal__body :deep(dt:first-child) {
  margin-top: 0;
}

.legal__body :deep(dd) {
  margin: 0.125rem 0 0;
  color: var(--text-muted);
  font-size: 0.9375rem;
}

/* Tabela rejestru czynności musi się przewijać sama, a nie rozpychać strony. */
.legal__body :deep(.legal__table-scroll) {
  overflow-x: auto;
  margin: 0 0 0.875rem;
}

.legal__body :deep(table) {
  width: 100%;
  min-width: 34rem;
  border-collapse: collapse;
  font-size: 0.9375rem;
}

.legal__body :deep(th),
.legal__body :deep(td) {
  padding: 0.5rem 0.625rem;
  text-align: left;
  vertical-align: top;
  border-bottom: 1px solid var(--surface-border);
}

.legal__body :deep(th) {
  font-weight: 600;
  white-space: nowrap;
}

.legal__body :deep(.legal__todo) {
  color: var(--p-primary-color);
  font-weight: 600;
}
</style>
