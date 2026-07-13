<script setup lang="ts">
import Skeleton from 'primevue/skeleton'
</script>

<template>
  <div
    class="skeleton"
    aria-hidden="true"
  >
    <Skeleton
      class="skeleton__media"
      border-radius="0"
    />
    <div class="skeleton__body">
      <Skeleton
        width="85%"
        height="0.9rem"
      />
      <Skeleton
        width="40%"
        height="1.1rem"
      />
      <Skeleton
        width="55%"
        height="0.75rem"
      />
    </div>
  </div>
</template>

<style scoped>
.skeleton {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--surface-border);
  border-radius: var(--card-radius);
  overflow: hidden;
  background: var(--surface-card);
}

.skeleton__media {
  width: 100%;
  aspect-ratio: 4 / 3;
}

.skeleton__body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.875rem 1rem 1rem;
}
</style>