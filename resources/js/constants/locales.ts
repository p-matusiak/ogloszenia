import type { AppLocale, LocaleOption } from '@/i18n/types'

export const LOCALE_STORAGE_KEY = 'zunto.locale'

export const DEFAULT_LOCALE: AppLocale = 'pl'

export const LOCALE_OPTIONS: readonly LocaleOption[] = [
  { value: 'pl', label: 'Polski', flag: 'PL' },
  { value: 'en', label: 'English', flag: 'EN' },
  { value: 'ru', label: 'Русский', flag: 'RU' },
] as const

export const LOCALE_BCP47: Record<AppLocale, string> = {
  pl: 'pl-PL',
  en: 'en-US',
  ru: 'ru-RU',
}