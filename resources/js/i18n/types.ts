export const APP_LOCALES = ['pl', 'en', 'ru'] as const

export type AppLocale = (typeof APP_LOCALES)[number]

export function isAppLocale(value: string): value is AppLocale {
  return (APP_LOCALES as readonly string[]).includes(value)
}

export type LocaleOption = {
  value: AppLocale
  label: string
  flag: string
}