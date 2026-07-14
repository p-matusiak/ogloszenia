<script setup lang="ts">
import Button from 'primevue/button'
import { useI18n } from 'vue-i18n'

import AdCard from '@/components/ads/AdCard.vue'
import AdCardSkeleton from '@/components/ads/AdCardSkeleton.vue'
import type { AdSummary } from '@/types/api'

defineProps<{
  ads: AdSummary[]
  isLoading: boolean
}>()

const { t } = useI18n()
</script>

<template>
  <section class="featured">
    <div class="featured__head">
      <h2 class="featured__title">
        {{ t('landing.featured.title') }}
      </h2>
      <RouterLink :to="{ name: 'listings' }">
        <Button
          :label="t('landing.featured.viewAll')"
          link
        />
      </RouterLink>
    </div>

    <div class="featured__grid">
      <template v-if="isLoading && ads.length === 0">
        <AdCardSkeleton
          v-for="n in 5"
          :key="n"
        />
      </template>
      <AdCard
        v-for="ad in ads"
        :key="ad.id"
        :ad="ad"
      />
    </div>
  </section>
</template>

<style scoped>
.featured {
  padding-block: 0 2.5rem;
}

.featured__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.featured__title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.featured__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

@media (width >= 48rem) {
  .featured__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (width >= 75rem) {
  .featured__grid {
    grid-template-columns: repeat(5, minmax(0, 1fr));
  }
}
</style>