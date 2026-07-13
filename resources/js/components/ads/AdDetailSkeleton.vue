<script setup lang="ts">
import Skeleton from 'primevue/skeleton'
</script>

<template>
  <div
    class="detail-skeleton"
    aria-hidden="true"
  >
    <Skeleton
      width="40%"
      height="0.85rem"
      class="detail-skeleton__crumbs"
    />
    <div class="detail-skeleton__layout">
      <Skeleton
        class="detail-skeleton__gallery"
        border-radius="var(--card-radius)"
      />
      <div class="detail-skeleton__side">
        <Skeleton
          height="12rem"
          border-radius="var(--card-radius)"
        />
        <Skeleton
          height="8rem"
          border-radius="var(--card-radius)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.detail-skeleton {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-skeleton__crumbs {
  margin-bottom: 0.25rem;
}

.detail-skeleton__layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--stack-gap);
}

@media (width >= 62rem) {
  .detail-skeleton__layout {
    grid-template-columns: minmax(0, 1fr) 22rem;
  }
}

.detail-skeleton__gallery {
  width: 100%;
  aspect-ratio: 16 / 10;
}

.detail-skeleton__side {
  display: flex;
  flex-direction: column;
  gap: var(--stack-gap);
}
</style>