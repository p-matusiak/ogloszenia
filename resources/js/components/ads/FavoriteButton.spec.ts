import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import { describe, expect, it, vi } from 'vitest'

import FavoriteButton from '@/components/ads/FavoriteButton.vue'
import { useAuthStore } from '@/stores/auth'
import { useFavoritesStore } from '@/stores/favorites'

const routerPush = vi.fn()

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: routerPush }),
  useRoute: () => ({ fullPath: '/ogloszenie/ad-42' }),
}))

const ButtonStub = {
  props: ['icon', 'loading', 'ariaLabel', 'ariaPressed'],
  emits: ['click'],
  template: '<button :aria-label="ariaLabel" @click="$emit(\'click\')"><slot /></button>',
}

const signedInUser = {
  id: 1,
  name: 'Jan',
  email: 'jan@example.com',
  is_admin: false,
  is_email_verified: true,
  phone: null,
}

function mountButton(authenticated = false) {
  const pinia = createTestingPinia({ createSpy: vi.fn })
  setActivePinia(pinia)

  if (authenticated) {
    useAuthStore().$patch({ user: signedInUser, isResolved: true })
  }

  return mount(FavoriteButton, {
    props: { adId: 42, adSlug: 'ad-42' },
    global: {
      plugins: [pinia],
      stubs: { Button: ButtonStub },
    },
  })
}

describe('FavoriteButton', () => {
  beforeEach(() => {
    routerPush.mockClear()
  })

  it('toggles the favourite for its ad on click', async () => {
    const wrapper = mountButton(true)
    const store = useFavoritesStore()

    await wrapper.get('button').trigger('click')

    expect(store.toggle).toHaveBeenCalledWith({ id: 42, slug: 'ad-42' })
  })

  it('redirects guests to login on click', async () => {
    const wrapper = mountButton()
    const store = useFavoritesStore()

    await wrapper.get('button').trigger('click')

    expect(routerPush).toHaveBeenCalledWith({
      name: 'login',
      query: { redirect: '/ogloszenie/ad-42' },
    })
    expect(store.toggle).not.toHaveBeenCalled()
  })

  it('ensures the id set is loaded on mount when signed in', () => {
    mountButton(true)
    const store = useFavoritesStore()

    expect(store.ensureIds).toHaveBeenCalled()
  })

  it('does not load favorite ids for guests', () => {
    mountButton()
    const store = useFavoritesStore()

    expect(store.ensureIds).not.toHaveBeenCalled()
  })
})
