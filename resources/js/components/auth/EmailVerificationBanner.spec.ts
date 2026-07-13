import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import EmailVerificationBanner from '@/components/auth/EmailVerificationBanner.vue'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types/api'

const add = vi.hoisted(() => vi.fn())

vi.mock('primevue/usetoast', () => ({ useToast: () => ({ add }) }))

function makeUser(isVerified: boolean): User {
  return {
    id: 1,
    name: 'Jan',
    email: 'jan@example.com',
    bio: null,
    avatar_url: null,
    is_admin: false,
    is_email_verified: isVerified,
  }
}

interface State {
  user: User | null
  isResolved: boolean
}

function mountBanner(state: State) {
  return mount(EmailVerificationBanner, {
    global: {
      plugins: [
        PrimeVue,
        ToastService,
        createTestingPinia({
          createSpy: vi.fn,
          initialState: { auth: { ...state, isLoading: false } },
          stubActions: true,
        }),
      ],
    },
  })
}

beforeEach(() => {
  vi.clearAllMocks()
})

describe('EmailVerificationBanner', () => {
  it('milczy, dopóki serwer nie odpowie, kto jest zalogowany', () => {
    const wrapper = mountBanner({ user: null, isResolved: false })

    expect(wrapper.text()).toBe('')
  })

  it('milczy wobec gościa', () => {
    const wrapper = mountBanner({ user: null, isResolved: true })

    expect(wrapper.text()).toBe('')
  })

  it('milczy wobec potwierdzonego konta', () => {
    const wrapper = mountBanner({ user: makeUser(true), isResolved: true })

    expect(wrapper.text()).toBe('')
  })

  it('prosi o potwierdzenie adresu i pokazuje go użytkownikowi', () => {
    const wrapper = mountBanner({ user: makeUser(false), isResolved: true })

    expect(wrapper.text()).toContain('jan@example.com')
    expect(wrapper.find('button').exists()).toBe(true)
  })

  it('wysyła link ponownie i potwierdza to toastem', async () => {
    const wrapper = mountBanner({ user: makeUser(false), isResolved: true })
    const auth = useAuthStore()

    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    expect(auth.resendVerification).toHaveBeenCalledOnce()
    expect(add).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }))
  })

  it('pokazuje błąd z serwera, gdy wysyłka się nie uda', async () => {
    const wrapper = mountBanner({ user: makeUser(false), isResolved: true })
    const auth = useAuthStore()

    vi.mocked(auth.resendVerification).mockRejectedValueOnce(new Error('boom'))

    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    expect(add).toHaveBeenCalledWith(expect.objectContaining({ severity: 'error' }))
  })
})
