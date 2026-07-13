import { describe, expect, it } from 'vitest'

import {
  isVerified,
  parseVerificationStatus,
  verificationCopy,
} from '@/composables/useEmailVerification'
import type { EmailVerificationStatus } from '@/types/api'

describe('parseVerificationStatus', () => {
  it.each<EmailVerificationStatus>(['verified', 'already-verified', 'invalid', 'expired'])(
    'przepuszcza status "%s" z backendu',
    (status) => {
      expect(parseVerificationStatus(status)).toBe(status)
    },
  )

  it('bez parametru pokazuje ekran „potwierdź adres”, a nie błąd', () => {
    expect(parseVerificationStatus(undefined)).toBe('required')
  })

  it('traktuje wartość spoza enuma jako zepsuty link', () => {
    expect(parseVerificationStatus('cokolwiek')).toBe('invalid')
  })

  it('traktuje powtórzony parametr w URL jako zepsuty link', () => {
    expect(parseVerificationStatus(['verified', 'expired'])).toBe('invalid')
  })
})

describe('isVerified', () => {
  it('uznaje ponowne kliknięcie linku za sukces, nie za błąd', () => {
    expect(isVerified('verified')).toBe(true)
    expect(isVerified('already-verified')).toBe(true)
  })

  it('nie uznaje wygasłego ani nieznanego linku za sukces', () => {
    expect(isVerified('expired')).toBe(false)
    expect(isVerified('invalid')).toBe(false)
    expect(isVerified('required')).toBe(false)
  })
})

describe('verificationCopy', () => {
  it('daje ton sukcesu tylko dla potwierdzonych adresów', () => {
    expect(verificationCopy('verified').tone).toBe('success')
    expect(verificationCopy('already-verified').tone).toBe('success')
    expect(verificationCopy('required').tone).toBe('pending')
    expect(verificationCopy('expired').tone).toBe('failure')
    expect(verificationCopy('invalid').tone).toBe('failure')
  })

  it('każdy ekran ma komplet tekstów', () => {
    for (const screen of ['verified', 'already-verified', 'required', 'expired', 'invalid'] as const) {
      const copy = verificationCopy(screen)

      expect(copy.title).not.toBe('')
      expect(copy.subtitle).not.toBe('')
      expect(copy.body).not.toBe('')
      expect(copy.icon).toMatch(/^pi /)
    }
  })
})
