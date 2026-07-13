import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

import { setDocumentTitle } from '@/composables/usePageTitle'
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
    title?: string
  }
}

const routes: RouteRecordRaw[] = [
  { path: '/', name: 'home', component: () => import('@/views/HomeView.vue') },
  {
    // Ten sam listing co `home`, tylko z kategorią w ścieżce. Tytuł ustawia
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
    path: '/dodaj-ogloszenie',
    name: 'ads.create',
    component: () => import('@/views/AdCreateView.vue'),
    meta: { requiresAuth: true, requiresVerified: true, title: 'Dodaj ogłoszenie' },
  },
  {
    path: '/moje-ogloszenia',
    name: 'ads.mine',
    component: () => import('@/views/MyAdsView.vue'),
    meta: { requiresAuth: true, title: 'Moje ogłoszenia' },
  },
  {
    path: '/ulubione',
    name: 'favorites',
    component: () => import('@/views/FavoritesView.vue'),
    meta: { requiresAuth: true, title: 'Ulubione ogłoszenia' },
  },
  {
    path: '/wiadomosci',
    name: 'messages',
    component: () => import('@/views/MessagesView.vue'),
    meta: { requiresAuth: true, title: 'Wiadomości' },
  },
  {
    path: '/wiadomosci/:id',
    name: 'messages.show',
    component: () => import('@/views/ConversationView.vue'),
    props: true,
    meta: { requiresAuth: true, title: 'Rozmowa' },
  },
  {
    path: '/moje-ogloszenia/:slug/edytuj',
    name: 'ads.edit',
    component: () => import('@/views/AdEditView.vue'),
    props: true,
    meta: { requiresAuth: true, requiresVerified: true, title: 'Edycja ogłoszenia' },
  },
  {
    path: '/profil',
    name: 'profile',
    component: () => import('@/views/ProfileView.vue'),
    meta: { requiresAuth: true, title: 'Profil' },
  },
  {
    path: '/logowanie',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { title: 'Logowanie' },
  },
  {
    path: '/rejestracja',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { title: 'Rejestracja' },
  },
  {
    path: '/weryfikacja-email',
    name: 'email.verify',
    component: () => import('@/views/EmailVerificationView.vue'),
    meta: { title: 'Weryfikacja adresu e-mail' },
  },
  {
    path: '/regulamin',
    name: 'terms',
    component: () => import('@/views/TermsView.vue'),
    meta: { title: 'Regulamin serwisu' },
  },
  {
    path: '/polityka-prywatnosci',
    name: 'privacy',
    component: () => import('@/views/PrivacyView.vue'),
    meta: { title: 'Polityka prywatności' },
  },
  {
    path: '/admin',
    name: 'admin',
    component: () => import('@/views/admin/AdminView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true, title: 'Panel administratora' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { title: 'Nie znaleziono strony' },
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // The session lives in an httpOnly cookie, so the only way to know who the
  // visitor is, is to ask the server once before the first guarded route.
  await auth.resolve()

  if (to.meta.requiresAuth === true && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Mirrors the `verified` middleware on the write endpoints: without this the
  // form submits and only then discovers the API refuses it.
  if (to.meta.requiresVerified === true && !auth.isEmailVerified) {
    return { name: 'email.verify' }
  }

  if (to.meta.requiresAdmin === true && !auth.isAdmin) {
    return { name: 'home' }
  }

  return true
})

router.afterEach((to) => {
  setDocumentTitle(to.meta.title)
})
