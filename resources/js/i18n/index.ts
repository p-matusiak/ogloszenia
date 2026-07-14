import { createI18n } from 'vue-i18n'

import { DEFAULT_LOCALE, LOCALE_STORAGE_KEY } from '@/constants/locales'
import en from '@/i18n/messages/en'
import pl from '@/i18n/messages/pl'
import ru from '@/i18n/messages/ru'
import { isAppLocale, type AppLocale } from '@/i18n/types'

function readStoredLocale(): AppLocale {
  if (typeof window === 'undefined') {
    return DEFAULT_LOCALE
  }

  const stored = window.localStorage.getItem(LOCALE_STORAGE_KEY)

  if (stored !== null && isAppLocale(stored)) {
    return stored
  }

  return DEFAULT_LOCALE
}

export const i18n = createI18n({
  legacy: false,
  locale: readStoredLocale(),
  fallbackLocale: 'en',
  messages: { pl, en, ru },
})

export function applyDocumentLocale(locale: AppLocale): void {
  if (typeof document === 'undefined') {
    return
  }

  document.documentElement.lang = locale
}