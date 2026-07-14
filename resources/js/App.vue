<script setup lang="ts">
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

import AppHeader from '@/components/layout/AppHeader.vue'
import CategoryNav from '@/components/layout/CategoryNav.vue'
import SiteFooter from '@/components/layout/SiteFooter.vue'
import EmailVerificationBanner from '@/components/auth/EmailVerificationBanner.vue'
import { useTheme } from '@/composables/useTheme'
import { useAuthStore } from '@/stores/auth'
import { useCategoryStore } from '@/stores/categories'
import { routeFilters } from '@/composables/useRouteFilters'

const route = useRoute()
const auth = useAuthStore()
const categories = useCategoryStore()
const { initialise } = useTheme()
const filters = computed(() => routeFilters(route.query))

const isLandingPage = computed(() => route.name === 'landing')

onMounted(async () => {
  initialise()
  await Promise.all([auth.resolve(), categories.load()])
})
</script>

<template>
  <div class="layout">
    <AppHeader
      :filters="filters"
      :show-search="!isLandingPage"
    />
    <CategoryNav v-if="!isLandingPage" />

    <main :class="['layout__main', isLandingPage ? 'layout__main--landing' : 'shell']">
      <!-- Suppressed on the activation page itself, which already says the same
           thing in its own card. -->
      <EmailVerificationBanner v-if="route.name !== 'email.verify'" />

      <RouterView />
    </main>

    <SiteFooter />

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<style scoped>
.layout {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  overflow-x: clip;
  max-width: 100%;
}

.layout__main {
  flex: 1;
  padding-block: 1.25rem 2.5rem;
}

.layout__main--landing {
  padding-block: 0;
}
</style>
