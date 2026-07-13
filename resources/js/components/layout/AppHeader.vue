<script setup lang="ts">
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import { storeToRefs } from 'pinia'
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { pruneFilters } from '@/composables/useRouteFilters'
import { useTheme } from '@/composables/useTheme'
import { useAuthStore } from '@/stores/auth'
import type { AdFilters } from '@/types/api'

const props = defineProps<{ filters: AdFilters }>()

const router = useRouter()
const auth = useAuthStore()
const { isAdmin, isAuthenticated, user } = storeToRefs(auth)
const { isDark, toggle } = useTheme()

const query = ref(props.filters.q ?? '')
const location = ref(props.filters.location ?? '')

watch(
  () => props.filters,
  (next) => {
    query.value = next.q ?? ''
    location.value = next.location ?? ''
  },
)

async function pushSearch(nextQuery: string, nextLocation: string): Promise<void> {
  const trimmedQuery = nextQuery.trim()
  const trimmedLocation = nextLocation.trim()
  const hadQuery = Boolean(props.filters.q)
  const nextSort =
    trimmedQuery && !hadQuery
      ? 'relevance'
      : !trimmedQuery && props.filters.sort === 'relevance'
        ? 'newest'
        : props.filters.sort

  const query_ = pruneFilters({
    ...props.filters,
    q: trimmedQuery || undefined,
    location: trimmedLocation || undefined,
    sort: nextSort,
    page: undefined,
  })

  await router.push({ name: 'home', query: query_ }).catch(() => undefined)
}

function onQueryInput(value: string | undefined): void {
  query.value = value ?? ''
}

function onLocationInput(value: string | undefined): void {
  location.value = value ?? ''
}

async function search(): Promise<void> {
  await pushSearch(query.value, location.value)
}

async function logout(): Promise<void> {
  await auth.logout()
  await router.push({ name: 'home' }).catch(() => undefined)
}
</script>

<template>
  <header class="header">
    <div class="shell header__inner">
      <RouterLink
        :to="{ name: 'home' }"
        class="brand"
      >
        <span
          class="brand__mark"
          aria-hidden="true"
        >
          <span class="brand__ring" />
        </span>
        <span class="brand__text">Ogłoszenia<span class="brand__tld">.pl</span></span>
      </RouterLink>

      <form
        class="finder"
        role="search"
        @submit.prevent="search"
      >
        <IconField class="finder__field finder__field--query">
          <InputIcon class="pi pi-search" />
          <InputText
            :model-value="query"
            placeholder="Czego szukasz?"
            aria-label="Czego szukasz?"
            unstyled
            class="finder__input"
            @update:model-value="onQueryInput"
          />
        </IconField>

        <IconField class="finder__field finder__field--location">
          <InputIcon class="pi pi-map-marker" />
          <InputText
            :model-value="location"
            placeholder="Cała Polska"
            aria-label="Lokalizacja"
            unstyled
            class="finder__input"
            @update:model-value="onLocationInput"
          />
        </IconField>

        <Button
          type="submit"
          label="Szukaj"
          class="finder__submit"
        />
      </form>

      <nav class="actions">
        <Button
          :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
          :aria-label="isDark ? 'Włącz jasny motyw' : 'Włącz ciemny motyw'"
          severity="secondary"
          text
          rounded
          @click="toggle"
        />

        <RouterLink :to="{ name: isAuthenticated ? 'ads.mine' : 'login' }">
          <Button
            icon="pi pi-user"
            :aria-label="isAuthenticated ? 'Profil użytkownika' : 'Zaloguj się'"
            severity="secondary"
            text
            rounded
          />
        </RouterLink>

        <RouterLink
          v-if="isAuthenticated"
          :to="{ name: 'profile' }"
          class="actions__profile-link"
        >
          <span class="actions__profile-name">{{ user?.name }}</span>
        </RouterLink>

        <RouterLink
          v-if="isAdmin"
          :to="{ name: 'admin' }"
        >
          <Button
            label="Panel admina"
            icon="pi pi-shield"
            severity="secondary"
            outlined
          />
        </RouterLink>

        <Button
          v-if="isAuthenticated"
          label="Wyloguj"
          icon="pi pi-sign-out"
          severity="secondary"
          text
          @click="logout"
        />

        <RouterLink :to="{ name: 'ads.create' }">
          <Button
            label="Dodaj ogłoszenie"
            icon="pi pi-plus"
            class="actions__cta"
          />
        </RouterLink>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.header {
  position: sticky;
  top: 0;
  z-index: 30;
  background: var(--surface-card);
  border-bottom: 1px solid var(--surface-border);
}

.header__inner {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-block: 0.625rem;
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  color: inherit;
  flex-shrink: 0;
}

/* Niebieski kafelek z pierścieniem, jak w makiecie. */
.brand__mark {
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border-radius: 0.5rem;
  background: var(--p-primary-color);
}

.brand__ring {
  width: 0.9rem;
  height: 0.9rem;
  border: 2.5px solid #fff;
  border-radius: 50%;
}

.brand__text {
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.brand__tld {
  color: var(--text-muted);
  font-weight: 600;
}

/* Jedna zrośnięta kapsuła: pole frazy, pole lokalizacji i przycisk. */
.finder {
  display: none;
  flex: 1;
  max-width: 44rem;
  border: 1px solid var(--surface-border);
  border-radius: 0.625rem;
  overflow: hidden;
  background: var(--surface-card);
}

.finder__field {
  display: flex;
  align-items: center;
  flex: 1;
}

.finder__field--location {
  border-left: 1px solid var(--surface-border);
  flex: 0 1 16rem;
}

.finder__input {
  width: 100%;
  border: 0;
  background: transparent;
  color: inherit;
  padding: 0.7rem 0.75rem 0.7rem 2.25rem;
  font: inherit;
  outline: none;
}

.finder__submit {
  border-radius: 0;
}

.actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-left: auto;
}

.actions__cta :deep(.p-button-label) {
  display: none;
}

@media (width >= 62rem) {
  .finder {
    display: flex;
  }

  .actions__cta :deep(.p-button-label) {
    display: inline;
  }
}
</style>
