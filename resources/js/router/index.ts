import { watch } from 'vue'
import { createRouter, createWebHistory, type RouteLocationNormalized, type RouteRecordRaw } from 'vue-router'

import { setDocumentTitle } from '@/composables/usePageTitle'
import { i18n } from '@/i18n/index'
import { useAuthStore } from '@/stores/auth'

/**
 * `title` opisuje ten sam adres, co `config/seo.php` po stronie serwera: Laravel
 * renderuje go do `<head>` pierwszej odpowiedzi, a router podmienia po nawigacji
 * w SPA. Ogłoszenie tytułu tu nie ma — bierze go z pobranych danych.
 */
declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    requiresVerified?: boolean
    requiresAdmin?: boolean
    titleKey?: string
  }
}

function syncRouteTitle(to: RouteLocationNormalized): void {
  if (typeof to.meta.titleKey === 'string') {
    setDocumentTitle(i18n.global.t(to.meta.titleKey))
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'landing',
    component: () => import('@/views/LandingView.vue'),
    meta: { titleKey: 'routes.landing' },
  },
  {
    path: '/ogloszenia',
    name: 'listings',
    component: () => import('@/views/HomeView.vue'),
    meta: { titleKey: 'routes.listings' },
  },
  {
    // Ten sam listing co `listings`, tylko z kategorią w ścieżce. Tytuł ustawia
    // widok, gdy pozna nazwę kategorii z drzewa.
    path: '/kategoria/:slug',
    name: 'categories.show',
    component: () => import('@/views/HomeView.vue'),
  },
  {
    path: '/ogloszenie/:slug',
    name: 'ads.show',
    component: () => import('@/views/AdDetailView.vue'),
    props: true,
  },
  {
    path: '/sprzedawca/:sellerSlug',
    name: 'sellers.show',
    component: () => import('@/views/SellerView.vue'),
    props: true,
  },
  {
    path: '/dodaj-ogloszenie',
    name: 'ads.create',
    component: () => import('@/views/AdCreateView.vue'),
    meta: { requiresAuth: true, requiresVerified: true, titleKey: 'routes.adsCreate' },
  },
  {
    path: '/moje-ogloszenia',
    name: 'ads.mine',
    component: () => import('@/views/MyAdsView.vue'),
    meta: { requiresAuth: true, titleKey: 'routes.adsMine' },
  },
  {
    path: '/ulubione',
    name: 'favorites',
    component: () => import('@/views/FavoritesView.vue'),
    meta: { requiresAuth: true, titleKey: 'routes.favorites' },
  },
  {
    path: '/wiadomosci',
    name: 'messages',
    component: () => import('@/views/MessagesView.vue'),
    meta: { requiresAuth: true, titleKey: 'routes.messages' },
  },
  {
    path: '/wiadomosci/:id',
    name: 'messages.show',
    component: () => import('@/views/ConversationView.vue'),
    props: true,
    meta: { requiresAuth: true, titleKey: 'routes.conversation' },
  },
  {
    path: '/moje-ogloszenia/:slug/edytuj',
    name: 'ads.edit',
    component: () => import('@/views/AdEditView.vue'),
    props: true,
    meta: { requiresAuth: true, requiresVerified: true, titleKey: 'routes.adsEdit' },
  },
  {
    path: '/profil',
    name: 'profile',
    component: () => import('@/views/ProfileView.vue'),
    meta: { requiresAuth: true, titleKey: 'routes.profile' },
  },
  {
    path: '/logowanie',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { titleKey: 'routes.login' },
  },
  {
    path: '/rejestracja',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { titleKey: 'routes.register' },
  },
  {
    path: '/weryfikacja-email',
    name: 'email.verify',
    component: () => import('@/views/EmailVerificationView.vue'),
    meta: { titleKey: 'routes.emailVerify' },
  },
  {
    path: '/regulamin',
    name: 'terms',
    component: () => import('@/views/TermsView.vue'),
    meta: { titleKey: 'routes.terms' },
  },
  {
    path: '/polityka-prywatnosci',
    name: 'privacy',
    component: () => import('@/views/PrivacyView.vue'),
    meta: { titleKey: 'routes.privacy' },
  },
  {
    path: '/admin',
    name: 'admin',
    component: () => import('@/views/admin/AdminView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true, titleKey: 'routes.admin' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { titleKey: 'routes.notFound' },
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  const needsAuthGate =
    to.meta.requiresAuth === true
    || to.meta.requiresVerified === true
    || to.meta.requiresAdmin === true

  // Publiczne strony (np. detal ogłoszenia) nie muszą czekać na /auth/me —
  // sesję sprawdzamy w tle, a przed chronioną trasą czekamy już na wynik.
  if (needsAuthGate) {
    await auth.resolve()
  } else if (!auth.isResolved) {
    void auth.resolve()
  }

  if (to.meta.requiresAuth === true && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Mirrors the `verified` middleware on the write endpoints: without this the
  // form submits and only then discovers the API refuses it.
  if (to.meta.requiresVerified === true && !auth.isEmailVerified) {
    return { name: 'email.verify' }
  }

  if (to.meta.requiresAdmin === true && !auth.isAdmin) {
    return { name: 'landing' }
  }

  return true
})

router.afterEach((to) => {
  syncRouteTitle(to)
})

watch(i18n.global.locale, () => {
  syncRouteTitle(router.currentRoute.value)
})
