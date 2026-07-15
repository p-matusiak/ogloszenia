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

// The activation and post-registration cards already say the same thing, with
// their own resend button; the banner would only repeat it.
const showsVerificationBanner = computed(
  () => route.name !== 'email.verify' && route.name !== 'register',
)

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
      <EmailVerificationBanner v-if="showsVerificationBanner" />

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
