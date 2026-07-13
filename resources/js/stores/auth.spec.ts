import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types/api'

const currentUser = vi.hoisted(() => vi.fn())
const register = vi.hoisted(() => vi.fn())
const resendVerificationEmail = vi.hoisted(() => vi.fn())

vi.mock('@/api/modules/v1/auth', () => ({
  currentUser,
  register,
  resendVerificationEmail,
  login: vi.fn(),
  logout: vi.fn(),
}))

vi.mock('@/api/modules/v1/profile', () => ({ updateProfile: vi.fn() }))

function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 1,
    name: 'Jan Kowalski',
    email: 'jan@example.com',
    bio: null,
    avatar_url: null,
    is_admin: false,
    is_email_verified: true,
    phone: null,
    ...overrides,
  }
}

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

describe('isEmailVerified', () => {
  it('jest fałszem, dopóki nikt nie jest zalogowany', () => {
    expect(useAuthStore().isEmailVerified).toBe(false)
  })

  it('odbija flagę ze świeżo zarejestrowanego konta', async () => {
    register.mockResolvedValue(makeUser({ is_email_verified: false }))
    const auth = useAuthStore()

    await auth.register({
      name: 'Jan',
      email: 'jan@example.com',
      password: 'x',
      password_confirmation: 'x',
    })

    expect(auth.isEmailVerified).toBe(false)
  })
})

describe('resendVerification', () => {
  it('zdejmuje flagę ładowania także wtedy, gdy serwer odmówi', async () => {
    resendVerificationEmail.mockRejectedValue(new Error('409'))
    const auth = useAuthStore()

    await expect(auth.resendVerification()).rejects.toThrow('409')
    expect(auth.isLoading).toBe(false)
  })
})

describe('refresh', () => {
  it('pobiera użytkownika ponownie, choć resolve() już raz odpowiedział', async () => {
    currentUser
      .mockResolvedValueOnce(makeUser({ is_email_verified: false }))
      .mockResolvedValueOnce(makeUser({ is_email_verified: true }))

    const auth = useAuthStore()

    await auth.resolve()
    expect(auth.isEmailVerified).toBe(false)

    // resolve() alone would short-circuit on isResolved and never re-ask.
    await auth.resolve()
    expect(currentUser).toHaveBeenCalledTimes(1)

    await auth.refresh()
    expect(currentUser).toHaveBeenCalledTimes(2)
    expect(auth.isEmailVerified).toBe(true)
  })
})
