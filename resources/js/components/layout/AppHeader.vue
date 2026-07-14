<script setup lang="ts">
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import { storeToRefs } from 'pinia'
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import LanguageSwitcher from '@/components/layout/LanguageSwitcher.vue'
import { pruneFilters } from '@/composables/useRouteFilters'
import { useTheme } from '@/composables/useTheme'
import { useAuthStore } from '@/stores/auth'
import { useConversationsStore } from '@/stores/conversations'
import { useFavoritesStore } from '@/stores/favorites'
import type { AdFilters } from '@/types/api'

const props = withDefaults(
  defineProps<{
    filters: AdFilters
    showSearch?: boolean
  }>(),
  { showSearch: true },
)

const router = useRouter()
const { t } = useI18n()
const auth = useAuthStore()
const favorites = useFavoritesStore()
const conversations = useConversationsStore()
const { isAuthenticated, isResolved, user } = storeToRefs(auth)
const { isDark, toggle } = useTheme()

/** Panel admina tylko po ustaleniu sesji i wyłącznie dla kont z `is_admin`. */
const showAdminPanel = computed(
  () => isResolved.value && user.value?.is_admin === true,
)

const query = ref(props.filters.q ?? '')

/** Plik w `public/` — nie bundlujemy, serwuje go nginx bezpośrednio. */
const logoSrc = '/logo.png'

watch(
  () => props.filters.q,
  (next) => {
    query.value = next ?? ''
  },
)

async function pushSearch(nextQuery: string): Promise<void> {
  const trimmedQuery = nextQuery.trim()
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
    sort: nextSort,
    page: undefined,
  })

  await router.push({ name: 'listings', query: query_ }).catch(() => undefined)
}

function onQueryInput(value: string | undefined): void {
  query.value = value ?? ''
}

async function search(): Promise<void> {
  await pushSearch(query.value)
}

onMounted(() => {
  if (auth.isAuthenticated) {
    void conversations.refreshUnreadCount()
  }
})

watch(isAuthenticated, (loggedIn) => {
  if (loggedIn) {
    void conversations.refreshUnreadCount()
  } else {
    conversations.reset()
  }
})

async function logout(): Promise<void> {
  await auth.logout()
  favorites.reset()
  conversations.reset()
  await router.push({ name: 'landing' }).catch(() => undefined)
}
</script>

<template>
  <header class="header">
    <div
      class="shell header__inner"
      :class="{ 'header__inner--compact': !showSearch }"
    >
      <RouterLink
        :to="{ name: 'landing' }"
        class="brand"
      >
        <img
          :src="logoSrc"
          alt=""
          class="brand__logo"
          width="73"
          height="64"
        >
        <span class="brand__text">{{ t('nav.siteName') }}<span class="brand__tld">.pl</span></span>
      </RouterLink>

      <form
        v-if="showSearch"
        class="finder search-capsule search-capsule--nav"
        role="search"
        @submit.prevent="search"
      >
        <IconField class="search-capsule__field">
          <InputIcon class="pi pi-search search-capsule__icon" />
          <InputText
            :model-value="query"
            :placeholder="t('nav.searchPlaceholder')"
            :aria-label="t('nav.searchPlaceholder')"
            unstyled
            class="search-capsule__input"
            @update:model-value="onQueryInput"
          />
        </IconField>

        <Button
          type="submit"
          :label="t('nav.searchSubmit')"
          icon="pi pi-search"
          class="finder__submit search-capsule__submit"
        />
      </form>

      <nav class="actions">
        <LanguageSwitcher />

        <Button
          :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
          :aria-label="isDark ? t('nav.themeLight') : t('nav.themeDark')"
          severity="secondary"
          text
          rounded
          @click="toggle"
        />

        <template v-if="isResolved && !isAuthenticated">
          <RouterLink :to="{ name: 'login' }">
            <Button
              :label="t('nav.login')"
              icon="pi pi-sign-in"
              severity="secondary"
              outlined
              class="actions__auth"
            />
          </RouterLink>

          <RouterLink :to="{ name: 'register' }">
            <Button
              :label="t('nav.register')"
              icon="pi pi-user-plus"
              severity="secondary"
              outlined
              class="actions__auth"
            />
          </RouterLink>
        </template>

        <RouterLink
          v-else-if="isAuthenticated"
          :to="{ name: 'ads.mine' }"
        >
          <Button
            icon="pi pi-user"
            :aria-label="t('nav.profile')"
            severity="secondary"
            text
            rounded
          />
        </RouterLink>

        <RouterLink
          v-if="isAuthenticated"
          :to="{ name: 'favorites' }"
        >
          <Button
            icon="pi pi-heart"
            :aria-label="t('nav.favorites')"
            severity="secondary"
            text
            rounded
          />
        </RouterLink>

        <RouterLink
          v-if="isAuthenticated"
          :to="{ name: 'messages' }"
        >
          <Button
            icon="pi pi-comments"
            :aria-label="
              conversations.unreadCount > 0
                ? t('nav.messagesUnread', { count: conversations.unreadCount })
                : t('nav.messages')
            "
            severity="secondary"
            text
            rounded
            :badge="conversations.unreadCount > 0 ? String(conversations.unreadCount) : undefined"
            badge-severity="danger"
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
          v-if="showAdminPanel"
          :to="{ name: 'admin' }"
        >
          <Button
            :label="t('nav.admin')"
            icon="pi pi-shield"
            severity="secondary"
            outlined
            class="actions__admin"
          />
        </RouterLink>

        <Button
          v-if="isAuthenticated"
          :label="t('nav.logout')"
          icon="pi pi-sign-out"
          severity="secondary"
          text
          class="actions__logout"
          @click="logout"
        />

        <RouterLink :to="{ name: 'ads.create' }">
          <Button
            :label="t('nav.addAd')"
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
  box-shadow: 0 1px 0 color-mix(in srgb, var(--surface-border) 65%, transparent);
  overflow-x: clip;
  max-width: 100%;
}

.header__inner {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  grid-template-areas:
    'brand actions'
    'finder finder';
  align-items: center;
  gap: 0.625rem 0.75rem;
  padding-block: 0.75rem;
  min-width: 0;
  max-width: 100%;
}

.header__inner--compact {
  grid-template-areas: 'brand actions';
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  color: inherit;
  flex-shrink: 0;
  grid-area: brand;
  min-width: 0;
}

.brand__logo {
  display: block;
  height: 4rem;
  width: auto;
  max-width: 4.75rem;
  object-fit: contain;
  flex-shrink: 0;
}

.brand__text {
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.02em;
  color: var(--brand-blue);
}

.brand__tld {
  color: var(--text-muted);
  font-weight: 600;
}

.finder {
  grid-area: finder;
  width: 100%;
  min-width: 0;
}

.finder__submit :deep(.p-button) {
  height: 100%;
}

.finder__submit :deep(.p-button-label) {
  display: none;
}

@media (width >= 48rem) {
  .finder__submit :deep(.p-button-label) {
    display: inline;
  }

  .finder__submit :deep(.p-button-icon) {
    display: none;
  }
}

.actions {
  display: flex;
  align-items: center;
  gap: 0.125rem;
  grid-area: actions;
  justify-self: end;
  min-width: 0;
  flex-shrink: 0;
}

.actions :deep(.p-button.p-button-text.p-button-secondary) {
  color: var(--text-muted);
}

.actions :deep(.p-button.p-button-text.p-button-secondary:enabled:hover) {
  color: var(--text-strong);
  background: var(--surface-muted);
}

.actions :deep(.p-button.p-button-outlined.p-button-secondary) {
  background: transparent;
}

.actions__profile-name {
  display: none;
}

.actions__profile-link {
  text-decoration: none;
  color: inherit;
}

.actions__auth :deep(.p-button-label),
.actions__cta :deep(.p-button-label),
.actions__logout :deep(.p-button-label),
.actions__admin :deep(.p-button-label) {
  display: none;
}

@media (width >= 40rem) {
  .actions__auth :deep(.p-button-label),
  .actions__logout :deep(.p-button-label),
  .actions__admin :deep(.p-button-label) {
    display: inline;
  }

  .actions__auth :deep(.p-button-icon) {
    display: none;
  }
}

@media (width >= 48rem) {
  .actions__profile-name {
    display: inline-block;
    max-width: 8rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.875rem;
    font-weight: 600;
  }
}

@media (width >= 62rem) {
  .header__inner {
    grid-template-columns: auto minmax(12rem, 1fr) auto;
    grid-template-areas: 'brand finder actions';
    gap: 1rem;
    padding-block: 0.625rem;
  }

  .header__inner--compact {
    grid-template-columns: auto auto;
    grid-template-areas: 'brand actions';
  }

  .finder {
    max-width: 42rem;
    justify-self: stretch;
  }

  .actions__cta :deep(.p-button-label) {
    display: inline;
  }
}
</style>