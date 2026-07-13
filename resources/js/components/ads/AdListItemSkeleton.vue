<script setup lang="ts">
import Skeleton from 'primevue/skeleton'
</script>

<template>
  <div
    class="skeleton"
    aria-hidden="true"
  >
    <!-- Zajmuje dokładnie tyle miejsca co AdListItem, żeby lista nie skakała
         w momencie podmiany szkieletu na prawdziwe dane. Komentarz stoi
         wewnątrz korzenia — przed nim zrobiłby z komponentu fragment. -->
    <Skeleton
      class="skeleton__media"
      border-radius="0.5rem"
    />
    <div class="skeleton__body">
      <Skeleton
        width="70%"
        height="1rem"
      />
      <Skeleton
        width="35%"
        height="1.2rem"
      />
      <Skeleton
        width="45%"
        height="0.8rem"
      />
      <Skeleton
        width="30%"
        height="0.7rem"
      />
    </div>
  </div>
</template>

<style scoped>
/* Wymiary muszą nadążać za .row w AdListItem — stąd te same wartości. */
.skeleton {
  display: flex;
  gap: 1.125rem;
  padding: var(--card-padding);
  border: 1px solid transparent;
  border-bottom-color: var(--surface-border);
}

.skeleton__media {
  width: 7rem;
  aspect-ratio: 4 / 3;
  flex-shrink: 0;
}

@media (width >= 40rem) {
  .skeleton__media {
    width: 11rem;
  }
}

.skeleton__body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
}
</style>
