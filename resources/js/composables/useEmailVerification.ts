import { useToast } from 'primevue/usetoast'
import type { LocationQueryValue } from 'vue-router'
import { useI18n } from 'vue-i18n'

import { errorMessage } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import type { EmailVerificationStatus } from '@/types/api'

/**
 * The backend only ever produces the four cases of App\Enums\EmailVerification-
 * Status. "required" is ours alone: it is the screen a visitor sees when the
 * router sends them here without an activation link at all.
 */
export type VerificationScreen = EmailVerificationStatus | 'required'

const BACKEND_STATUSES: readonly EmailVerificationStatus[] = [
  'verified',
  'already-verified',
  'invalid',
  'expired',
]

export type VerificationTone = 'success' | 'pending' | 'failure'

export interface VerificationCopy {
  title: string
  subtitle: string
  body: string
  icon: string
  tone: VerificationTone
}

/**
 * A visitor can type anything into the address bar, so an unrecognised value is
 * a broken link — but a missing one simply means they never followed one.
 */
export function parseVerificationStatus(
  raw: LocationQueryValue | LocationQueryValue[] | undefined,
): VerificationScreen {
  if (raw === undefined || raw === null) {
    return 'required'
  }

  return BACKEND_STATUSES.find((status) => status === raw) ?? 'invalid'
}

export function isVerified(screen: VerificationScreen): boolean {
  return screen === 'verified' || screen === 'already-verified'
}

/**
 * Resending is offered from the banner, the profile and the activation page.
 * All three must report the server's own message — "already verified" and
 * "too many requests" mean different things to the user.
 */
export function useResendVerification(): { resend: () => Promise<void> } {
  const auth = useAuthStore()
  const toast = useToast()
  const { t } = useI18n()

  async function resend(): Promise<void> {
    try {
      await auth.resendVerification()
      toast.add({
        severity: 'success',
        summary: t('common.save'),
        detail: t('auth.verification.banner', { email: auth.user?.email ?? t('auth.login.email') }),
        life: 6000,
      })
    } catch (caught: unknown) {
      toast.add({
        severity: 'error',
        summary: t('auth.verification.resend'),
        detail: errorMessage(caught, t('common.retry')),
        life: 6000,
      })
    }
  }

  return { resend }
}

export function verificationCopy(screen: VerificationScreen): VerificationCopy {
  const { t } = useI18n()

  switch (screen) {
    case 'verified':
      return {
        title: t('auth.verification.verified.title'),
        subtitle: t('auth.verification.verified.subtitle'),
        body: t('auth.verification.verified.body'),
        icon: 'pi pi-check-circle',
        tone: 'success',
      }
    case 'already-verified':
      return {
        title: t('auth.verification.alreadyVerified.title'),
        subtitle: t('auth.verification.alreadyVerified.subtitle'),
        body: t('auth.verification.alreadyVerified.body'),
        icon: 'pi pi-check-circle',
        tone: 'success',
      }
    case 'required':
      return {
        title: t('auth.verification.required.title'),
        subtitle: t('auth.verification.required.subtitle'),
        body: t('auth.verification.required.body'),
        icon: 'pi pi-envelope',
        tone: 'pending',
      }
    case 'expired':
      return {
        title: t('auth.verification.expired.title'),
        subtitle: t('auth.verification.expired.subtitle'),
        body: t('auth.verification.expired.body'),
        icon: 'pi pi-clock',
        tone: 'failure',
      }
    case 'invalid':
      return {
        title: t('auth.verification.invalid.title'),
        subtitle: t('auth.verification.invalid.subtitle'),
        body: t('auth.verification.invalid.body'),
        icon: 'pi pi-times-circle',
        tone: 'failure',
      }
  }
}
