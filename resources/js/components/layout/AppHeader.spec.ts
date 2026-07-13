import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import { setActivePinia } from 'pinia'
import { ref } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import AppHeader from '@/components/layout/AppHeader.vue'

const push = vi.hoisted(() => vi.fn())
const logout = vi.hoisted(() => vi.fn())
const initialise = vi.hoisted(() => vi.fn())

vi.mock('vue-router', () => ({
  useRouter: () => ({ push }),
}))

vi.mock('@/composables/useTheme', () => ({
  useTheme: () => ({
    isDark: ref(false),
    toggle: vi.fn(),
    initialise,
  }),
}))

const baseUser = {
  id: 1,
  name: 'Jan Kowalski',
  email: 'jan@example.com',
  is_email_verified: true,
  phone: null,
}

function mountHeader(options?: { isAdmin?: boolean; authenticated?: boolean }) {
  const pinia = createTestingPinia({
    createSpy: vi.fn,
    stubActions: false,
    initialState: {
      auth: {
        user: options?.authenticated
          ? { ...baseUser, is_admin: options.isAdmin === true }
          : null,
        isResolved: true,
      },
    },
  })
  setActivePinia(pinia)

  return mount(AppHeader, {
    props: { filters: {} },
    global: {
      plugins: [PrimeVue, pinia],
      stubs: {
        RouterLink: { template: '<a><slot /></a>' },
      },
    },
  })
}

beforeEach(() => {
  push.mockReset()
  push.mockResolvedValue(undefined)
  logout.mockReset()
  initialise.mockReset()
})

describe('AppHeader', () => {
  it('nie wyszukuje podczas wpisywania', async () => {
    const wrapper = mountHeader()

    await wrapper.find('input[aria-label="Czego szukasz?"]').setValue('rower')

    expect(push).not.toHaveBeenCalled()
  })

  it('nie pokazuje panelu admina gościowi', () => {
    const wrapper = mountHeader()

    expect(wrapper.text()).not.toContain('Panel admina')
  })

  it('nie pokazuje panelu admina zwykłemu użytkownikowi', () => {
    const wrapper = mountHeader({ authenticated: true, isAdmin: false })

    expect(wrapper.text()).not.toContain('Panel admina')
  })

  it('pokazuje panel admina tylko administratorowi', () => {
    const wrapper = mountHeader({ authenticated: true, isAdmin: true })

    expect(wrapper.text()).toContain('Panel admina')
  })

  it('wyszukuje dopiero po submit formularza', async () => {
    const wrapper = mountHeader()

    await wrapper.find('input[aria-label="Czego szukasz?"]').setValue('rower')
    await wrapper.find('form').trigger('submit')

    expect(push).toHaveBeenCalledOnce()
    expect(push).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'home',
        query: expect.objectContaining({ q: 'rower', sort: 'relevance' }),
      }),
    )
  })
})
