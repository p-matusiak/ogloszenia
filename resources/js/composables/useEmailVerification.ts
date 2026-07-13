import { useToast } from 'primevue/usetoast'
import type { LocationQueryValue } from 'vue-router'

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

  async function resend(): Promise<void> {
    try {
      await auth.resendVerification()
      toast.add({
        severity: 'success',
        summary: 'Wysłano',
        detail: `Link aktywacyjny poleciał na ${auth.user?.email ?? 'Twój adres'}.`,
        life: 6000,
      })
    } catch (caught: unknown) {
      toast.add({
        severity: 'error',
        summary: 'Nie udało się wysłać',
        detail: errorMessage(caught, 'Spróbuj ponownie za chwilę.'),
        life: 6000,
      })
    }
  }

  return { resend }
}

export function verificationCopy(screen: VerificationScreen): VerificationCopy {
  switch (screen) {
    case 'verified':
      return {
        title: 'Konto aktywowane',
        subtitle: 'Adres e-mail został potwierdzony.',
        body: 'Możesz już publikować ogłoszenia. Dziękujemy!',
        icon: 'pi pi-check-circle',
        tone: 'success',
      }
    case 'already-verified':
      return {
        title: 'Konto jest już aktywne',
        subtitle: 'Ten adres potwierdzono wcześniej.',
        body: 'Nie musisz nic robić — możesz korzystać z serwisu bez ograniczeń.',
        icon: 'pi pi-check-circle',
        tone: 'success',
      }
    case 'required':
      return {
        title: 'Potwierdź adres e-mail',
        subtitle: 'Ta sekcja wymaga aktywnego konta.',
        body: 'Wysłaliśmy link aktywacyjny przy rejestracji. Sprawdź skrzynkę i folder spam albo poproś o nowy.',
        icon: 'pi pi-envelope',
        tone: 'pending',
      }
    case 'expired':
      return {
        title: 'Link wygasł',
        subtitle: 'Ten link aktywacyjny nie jest już ważny.',
        body: 'Link jest ważny przez ograniczony czas. Wyślij nowy, aby dokończyć aktywację.',
        icon: 'pi pi-clock',
        tone: 'failure',
      }
    case 'invalid':
      return {
        title: 'Nieprawidłowy link',
        subtitle: 'Nie rozpoznajemy tego linku aktywacyjnego.',
        body: 'Sprawdź, czy adres skopiował się w całości. Możesz też poprosić o nowy link.',
        icon: 'pi pi-times-circle',
        tone: 'failure',
      }
  }
}
