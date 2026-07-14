<script setup lang="ts">
import Message from 'primevue/message'
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

import LandingCategoryGrid from '@/components/landing/LandingCategoryGrid.vue'
import LandingFeaturedAds from '@/components/landing/LandingFeaturedAds.vue'
import LandingHero from '@/components/landing/LandingHero.vue'
import LandingHowItWorks from '@/components/landing/LandingHowItWorks.vue'
import LandingStatsBar from '@/components/landing/LandingStatsBar.vue'
import { setDocumentTitle } from '@/composables/usePageTitle'
import { useLandingPage } from '@/composables/useLandingPage'

const { t } = useI18n()
const { featuredAds, totalAds, categoryTiles, isLoading, error, load } = useLandingPage()

onMounted(() => {
  setDocumentTitle(t('routes.landing'))
  void load()
})
</script>

<template>
  <div class="landing">
    <LandingHero />

    <div class="shell landing__content">
      <Message
        v-if="error"
        severity="warn"
        class="landing__error"
      >
        {{ t(error) }}
      </Message>

      <LandingCategoryGrid
        :tiles="categoryTiles"
        :is-loading="isLoading"
      />

      <LandingFeaturedAds
        :ads="featuredAds"
        :is-loading="isLoading"
      />
    </div>

    <LandingStatsBar :total-ads="totalAds" />

    <div class="shell">
      <LandingHowItWorks />
    </div>
  </div>
</template>

<style scoped>
.landing__error {
  margin-top: 1rem;
}
</style>