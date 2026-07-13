<script setup lang="ts">
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

import AppHeader from '@/components/layout/AppHeader.vue'
import CategoryNav from '@/components/layout/CategoryNav.vue'
import EmailVerificationBanner from '@/components/auth/EmailVerificationBanner.vue'
import { useTheme } from '@/composables/useTheme'
import { useAuthStore } from '@/stores/auth'
import { useCategoryStore } from '@/stores/categories'
import { routeFilters } from '@/composables/useRouteFilters'

const route = useRoute()
const auth = useAuthStore()
const categories = useCategoryStore()
const { initialise } = useTheme()
const year = new Date().getFullYear()

const filters = computed(() => routeFilters(route.query))

onMounted(async () => {
  initialise()
  await Promise.all([auth.resolve(), categories.load()])
})
</script>

<template>
  <div class="layout">
    <AppHeader :filters="filters" />
    <CategoryNav />

    <main class="shell layout__main">
      <!-- Suppressed on the activation page itself, which already says the same
           thing in its own card. -->
      <EmailVerificationBanner v-if="route.name !== 'email.verify'" />

      <RouterView />
    </main>

    <footer class="layout__footer">
      <div class="shell layout__footer-inner">
        <p class="layout__footer-copy">
          © {{ year }} Ogłoszenia — serwis ogłoszeń drobnych.
        </p>
        <nav
          class="layout__footer-nav"
          aria-label="Informacje prawne"
        >
          <RouterLink :to="{ name: 'terms' }">
            Regulamin
          </RouterLink>
          <RouterLink :to="{ name: 'privacy' }">
            Polityka prywatności
          </RouterLink>
        </nav>
      </div>
    </footer>

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<style scoped>
.layout {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}

.layout__main {
  flex: 1;
  padding-block: 1.25rem 3rem;
}

.layout__footer {
  padding-block: 1.75rem;
  border-top: 1px solid var(--surface-border);
  background: var(--surface-card);
  font-size: 0.875rem;
}

.layout__footer-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  text-align: center;
}

@media (width >= 48rem) {
  .layout__footer-inner {
    flex-direction: row;
    justify-content: space-between;
    text-align: left;
  }
}

.layout__footer-copy {
  margin: 0;
  color: var(--text-muted);
}

.layout__footer-nav {
  display: flex;
  gap: 1.25rem;
}

.layout__footer-nav a {
  color: var(--text-muted);
  text-decoration: none;
}

.layout__footer-nav a:hover {
  color: var(--p-primary-color);
}
</style>
