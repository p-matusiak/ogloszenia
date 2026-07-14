import { createI18n, type I18n } from 'vue-i18n'

import en from '@/i18n/messages/en'
import pl from '@/i18n/messages/pl'
import ru from '@/i18n/messages/ru'

export function createTestI18n(): I18n {
  return createI18n({
    legacy: false,
    locale: 'pl',
    messages: { pl, en, ru },
  })
}