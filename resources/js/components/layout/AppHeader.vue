<script setup lang="ts">
import Button from 'primevue/button'
import Drawer from 'primevue/drawer'
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
const isMobileMenuOpen = ref(false)

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
  isMobileMenuOpen.value = false
  await router.push({ name: 'landing' }).catch(() => undefined)
}

function closeMobileMenu(): void {
  isMobileMenuOpen.value = false
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
        <LanguageSwitcher class="actions__desktop-only" />

        <Button
          :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
          :aria-label="isDark ? t('nav.themeLight') : t('nav.themeDark')"
          severity="secondary"
          text
          rounded
          class="actions__theme"
          @click="toggle"
        />

        <Button
          icon="pi pi-bars"
          :aria-label="t('nav.menu')"
          severity="secondary"
          text
          rounded
          class="actions__mobile-menu-button"
          @click="isMobileMenuOpen = true"
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
          class="actions__mine"
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
          class="actions__favorites"
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
          class="actions__messages"
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

    <Drawer
      v-model:visible="isMobileMenuOpen"
      position="right"
      :header="t('nav.menu')"
      class="mobile-menu"
    >
      <div class="mobile-menu__content">
        <div
          v-if="isAuthenticated"
          class="mobile-menu__profile"
        >
          <span class="mobile-menu__profile-label">{{ t('nav.profile') }}</span>
          <strong class="mobile-menu__profile-name">{{ user?.name }}</strong>
        </div>

        <div class="mobile-menu__section">
          <span class="mobile-menu__section-title">{{ t('nav.language') }}</span>
          <LanguageSwitcher :compact="false" />
        </div>

        <button
          type="button"
          class="mobile-menu__link mobile-menu__link--button"
          @click="toggle"
        >
          <i
            :class="isDark ? 'pi pi-sun' : 'pi pi-moon'"
            aria-hidden="true"
          />
          <span>{{ isDark ? t('nav.themeLight') : t('nav.themeDark') }}</span>
        </button>

        <div class="mobile-menu__links">
          <RouterLink
            v-if="isResolved && !isAuthenticated"
            :to="{ name: 'login' }"
            class="mobile-menu__link"
            @click="closeMobileMenu"
          >
            <i
              class="pi pi-sign-in"
              aria-hidden="true"
            />
            <span>{{ t('nav.login') }}</span>
          </RouterLink>

          <RouterLink
            v-if="isResolved && !isAuthenticated"
            :to="{ name: 'register' }"
            class="mobile-menu__link"
            @click="closeMobileMenu"
          >
            <i
              class="pi pi-user-plus"
              aria-hidden="true"
            />
            <span>{{ t('nav.register') }}</span>
          </RouterLink>

          <RouterLink
            v-if="isAuthenticated"
            :to="{ name: 'ads.mine' }"
            class="mobile-menu__link"
            @click="closeMobileMenu"
          >
            <i
              class="pi pi-list"
              aria-hidden="true"
            />
            <span>{{ t('routes.adsMine') }}</span>
          </RouterLink>

          <RouterLink
            v-if="isAuthenticated"
            :to="{ name: 'favorites' }"
            class="mobile-menu__link"
            @click="closeMobileMenu"
          >
            <i
              class="pi pi-heart"
              aria-hidden="true"
            />
            <span>{{ t('nav.favorites') }}</span>
          </RouterLink>

          <RouterLink
            v-if="isAuthenticated"
            :to="{ name: 'messages' }"
            class="mobile-menu__link"
            @click="closeMobileMenu"
          >
            <i
              class="pi pi-comments"
              aria-hidden="true"
            />
            <span>
              {{
                conversations.unreadCount > 0
                  ? t('nav.messagesUnread', { count: conversations.unreadCount })
                  : t('nav.messages')
              }}
            </span>
          </RouterLink>

          <RouterLink
            v-if="isAuthenticated"
            :to="{ name: 'profile' }"
            class="mobile-menu__link"
            @click="closeMobileMenu"
          >
            <i
              class="pi pi-user"
              aria-hidden="true"
            />
            <span>{{ t('nav.profile') }}</span>
          </RouterLink>

          <RouterLink
            v-if="showAdminPanel"
            :to="{ name: 'admin' }"
            class="mobile-menu__link"
            @click="closeMobileMenu"
          >
            <i
              class="pi pi-shield"
              aria-hidden="true"
            />
            <span>{{ t('nav.admin') }}</span>
          </RouterLink>

          <button
            v-if="isAuthenticated"
            type="button"
            class="mobile-menu__link mobile-menu__link--button"
            @click="logout"
          >
            <i
              class="pi pi-sign-out"
              aria-hidden="true"
            />
            <span>{{ t('nav.logout') }}</span>
          </button>
        </div>
      </div>
    </Drawer>
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

.actions__desktop-only,
.actions__mine,
.actions__favorites,
.actions__messages,
.actions__profile-link,
.actions__auth,
.actions__admin,
.actions__logout,
.actions__theme {
  display: none;
}

.actions__mobile-menu-button {
  display: inline-flex;
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

.actions__profile-link {
  text-decoration: none;
  color: inherit;
}

.actions__cta :deep(.p-button-label),
.actions__mobile-menu-button :deep(.p-button-label) {
  display: none;
}

.mobile-menu__content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.mobile-menu__profile {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.875rem 1rem;
  border-radius: 0.75rem;
  background: color-mix(in srgb, var(--brand-blue) 10%, var(--surface-muted));
}

.mobile-menu__profile-label,
.mobile-menu__section-title {
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.mobile-menu__profile-name {
  font-size: 1rem;
  line-height: 1.4;
}

.mobile-menu__section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.875rem 1rem;
  border: 1px solid var(--surface-border);
  border-radius: 0.75rem;
  background: var(--surface-card);
}

.mobile-menu__links {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mobile-menu__link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.9rem 1rem;
  border: 1px solid var(--surface-border);
  border-radius: 0.75rem;
  background: var(--surface-card);
  color: inherit;
  text-decoration: none;
  font: inherit;
  text-align: left;
  box-sizing: border-box;
}

.mobile-menu__link:hover {
  background: var(--surface-muted);
}

.mobile-menu__link--button {
  cursor: pointer;
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

  .actions__desktop-only,
  .actions__mine,
  .actions__favorites,
  .actions__messages,
  .actions__profile-link,
  .actions__auth,
  .actions__logout,
  .actions__admin,
  .actions__theme {
    display: inline-flex;
  }

  .actions__profile-name {
    display: inline-block;
    max-width: 8rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.875rem;
    font-weight: 600;
  }

  .actions__auth :deep(.p-button-label),
  .actions__logout :deep(.p-button-label),
  .actions__admin :deep(.p-button-label) {
    display: inline;
  }

  .actions__auth :deep(.p-button-icon) {
    display: none;
  }

  .actions__mobile-menu-button {
    display: none;
  }

  .actions__cta :deep(.p-button-label) {
    display: inline;
  }
}
</style>
