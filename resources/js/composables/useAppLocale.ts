import { usePrimeVue } from 'primevue/config'
import { computed, type WritableComputedRef } from 'vue'
import { useI18n } from 'vue-i18n'

import { client } from '@/api/client'
import { DEFAULT_LOCALE, LOCALE_BCP47, LOCALE_OPTIONS, LOCALE_STORAGE_KEY } from '@/constants/locales'
import { applyDocumentLocale, i18n } from '@/i18n/index'
import { isAppLocale, type AppLocale, type LocaleOption } from '@/i18n/types'

const PRIME_LOCALE: Record<AppLocale, Record<string, unknown>> = {
  pl: {
    firstDayOfWeek: '1',
    dayNamesMin: ['Nd', 'Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So'],
    monthNames: [
      'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
      'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień',
    ],
    monthNamesShort: ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'],
    today: 'Dzisiaj',
    clear: 'Wyczyść',
    emptySearchMessage: 'Brak wyników',
    emptyMessage: 'Brak dostępnych opcji',
  },
  en: {
    firstDayOfWeek: '0',
    dayNamesMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
    monthNames: [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December',
    ],
    monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    today: 'Today',
    clear: 'Clear',
    emptySearchMessage: 'No results found',
    emptyMessage: 'No available options',
  },
  ru: {
    firstDayOfWeek: '1',
    dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
    monthNames: [
      'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
      'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь',
    ],
    monthNamesShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
    today: 'Сегодня',
    clear: 'Очистить',
    emptySearchMessage: 'Результатов нет',
    emptyMessage: 'Нет доступных вариантов',
  },
}

function syncHttpLocale(locale: AppLocale): void {
  client.defaults.headers.common['Accept-Language'] = LOCALE_BCP47[locale]
}

export function useAppLocale(): {
  locale: WritableComputedRef<AppLocale>
  options: readonly LocaleOption[]
  setLocale: (next: AppLocale) => void
} {
  const { locale } = useI18n({ useScope: 'global' })
  const primevue = usePrimeVue()

  function setLocale(next: AppLocale): void {
    i18n.global.locale.value = next
    window.localStorage.setItem(LOCALE_STORAGE_KEY, next)
    applyDocumentLocale(next)
    syncHttpLocale(next)

    if (primevue.config.locale !== undefined) {
      Object.assign(
        primevue.config.locale as unknown as Record<string, unknown>,
        PRIME_LOCALE[next],
      )
    }
  }

  const currentLocale = computed({
    get: (): AppLocale => {
      const raw = String(locale.value)

      return isAppLocale(raw) ? raw : DEFAULT_LOCALE
    },
    set: setLocale,
  })

  return {
    locale: currentLocale,
    options: LOCALE_OPTIONS,
    setLocale,
  }
}

export function initialiseAppLocale(): void {
  const raw = String(i18n.global.locale.value)
  const current = isAppLocale(raw) ? raw : DEFAULT_LOCALE
  applyDocumentLocale(current)
  syncHttpLocale(current)
}