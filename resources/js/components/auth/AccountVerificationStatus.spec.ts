import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import AccountVerificationStatus from '@/components/auth/AccountVerificationStatus.vue'
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
    phone: null,
  }
}

function mountStatus(isVerified: boolean) {
  return mount(AccountVerificationStatus, {
    global: {
      plugins: [
        PrimeVue,
        createTestingPinia({
          createSpy: vi.fn,
          initialState: { auth: { user: makeUser(isVerified), isLoading: false } },
        }),
      ],
    },
  })
}

beforeEach(() => {
  vi.clearAllMocks()
})

describe('AccountVerificationStatus', () => {
  it('nazywa konto potwierdzonym i nie proponuje wysyłki linku', () => {
    const wrapper = mountStatus(true)

    expect(wrapper.text()).toContain('Potwierdzone')
    expect(wrapper.text()).not.toContain('Niepotwierdzone')
    expect(wrapper.find('button').exists()).toBe(false)
  })

  it('nazywa konto niepotwierdzonym i pozwala wysłać link', () => {
    const wrapper = mountStatus(false)

    expect(wrapper.text()).toContain('Niepotwierdzone')
    expect(wrapper.text()).toContain('jan@example.com')
    expect(wrapper.find('button').exists()).toBe(true)
  })

  it('wysyła link ponownie i potwierdza to toastem', async () => {
    const wrapper = mountStatus(false)
    const auth = useAuthStore()

    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    expect(auth.resendVerification).toHaveBeenCalledOnce()
    expect(add).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }))
  })

  it('pokazuje błąd z serwera, gdy wysyłka się nie uda', async () => {
    const wrapper = mountStatus(false)
    const auth = useAuthStore()

    vi.mocked(auth.resendVerification).mockRejectedValueOnce(new Error('boom'))

    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    expect(add).toHaveBeenCalledWith(expect.objectContaining({ severity: 'error' }))
  })
})
