import { createPinia, setActivePinia } from 'pinia'
import PrimeVue from 'primevue/config'
import { mount } from '@vue/test-utils'
import { defineComponent } from 'vue'
import { createI18n } from 'vue-i18n'

import en from '@/i18n/messages/en'
import pl from '@/i18n/messages/pl'
import ru from '@/i18n/messages/ru'
import { LOCALE_STORAGE_KEY } from '@/constants/locales'
import { useAppLocale } from '@/composables/useAppLocale'

const Harness = defineComponent({
  setup() {
    return useAppLocale()
  },
  template: '<div />',
})

function mountHarness(): ReturnType<typeof mount> {
  const i18n = createI18n({
    legacy: false,
    locale: 'pl',
    messages: { pl, en, ru },
  })

  return mount(Harness, {
    global: {
      plugins: [createPinia(), i18n, [PrimeVue, {}]],
    },
  })
}

describe('useAppLocale', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    window.localStorage.clear()
  })

  it('persists the selected locale', () => {
    const wrapper = mountHarness()
    const { setLocale } = wrapper.vm as unknown as ReturnType<typeof useAppLocale>

    setLocale('en')

    expect(window.localStorage.getItem(LOCALE_STORAGE_KEY)).toBe('en')
    expect(document.documentElement.lang).toBe('en')
  })
})