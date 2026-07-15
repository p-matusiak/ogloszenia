import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import { setActivePinia } from 'pinia'
import { nextTick, ref } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import AppHeader from '@/components/layout/AppHeader.vue'
import { createTestI18n } from '@/testing/i18n'

const push = vi.hoisted(() => vi.fn())
const logout = vi.hoisted(() => vi.fn())
const initialise = vi.hoisted(() => vi.fn())
const toggleTheme = vi.hoisted(() => vi.fn())

vi.mock('vue-router', () => ({
  useRouter: () => ({ push }),
}))

vi.mock('@/composables/useTheme', () => ({
  useTheme: () => ({
    isDark: ref(false),
    toggle: toggleTheme,
    initialise,
  }),
}))

const baseUser = {
  id: 1,
  name: 'Jan Kowalski',
  email: 'jan@example.com',
  is_email_verified: true,
  phone: null,
  default_location: null,
  default_latitude: null,
  default_longitude: null,
}

function mountHeader(options?: {
  isAdmin?: boolean
  authenticated?: boolean
  filters?: Record<string, unknown>
}) {
  const pinia = createTestingPinia({
    createSpy: vi.fn,
    stubActions: false,
    initialState: {
      auth: {
        user: options?.authenticated
          ? { ...baseUser, is_admin: options.isAdmin === true }
          : null,
        isResolved: true,
      },
    },
  })
  setActivePinia(pinia)

  return mount(AppHeader, {
    props: { filters: options?.filters ?? {} },
    global: {
      plugins: [PrimeVue, createTestI18n(), pinia],
      stubs: {
        RouterLink: { template: '<a><slot /></a>' },
        LanguageSwitcher: true,
      },
    },
  })
}

beforeEach(() => {
  push.mockReset()
  push.mockResolvedValue(undefined)
  logout.mockReset()
  initialise.mockReset()
  toggleTheme.mockReset()
  document.body.innerHTML = ''
})

describe('AppHeader', () => {
  it('nie wyszukuje podczas wpisywania', async () => {
    const wrapper = mountHeader()

    await wrapper.find('input[aria-label="Czego szukasz?"]').setValue('rower')

    expect(push).not.toHaveBeenCalled()
  })

  it('pokazuje przyciski logowania i rejestracji gościowi', () => {
    const wrapper = mountHeader()

    expect(wrapper.text()).toContain('Zaloguj się')
    expect(wrapper.text()).toContain('Załóż konto')
  })

  it('nie pokazuje przycisków logowania zalogowanemu użytkownikowi', () => {
    const wrapper = mountHeader({ authenticated: true })

    expect(wrapper.text()).not.toContain('Zaloguj się')
    expect(wrapper.text()).not.toContain('Załóż konto')
  })

  it('nie pokazuje panelu admina gościowi', () => {
    const wrapper = mountHeader()

    expect(wrapper.text()).not.toContain('Panel admina')
  })

  it('nie pokazuje panelu admina zwykłemu użytkownikowi', () => {
    const wrapper = mountHeader({ authenticated: true, isAdmin: false })

    expect(wrapper.text()).not.toContain('Panel admina')
  })

  it('pokazuje panel admina tylko administratorowi', () => {
    const wrapper = mountHeader({ authenticated: true, isAdmin: true })

    expect(wrapper.text()).toContain('Panel admina')
  })

  it('wyszukuje dopiero po submit formularza', async () => {
    const wrapper = mountHeader()

    await wrapper.find('input[aria-label="Czego szukasz?"]').setValue('rower')
    await wrapper.find('form').trigger('submit')

    expect(push).toHaveBeenCalledOnce()
    expect(push).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'listings',
        query: expect.objectContaining({ q: 'rower', sort: 'relevance' }),
      }),
    )
  })

  it('nie ma pola lokalizacji — tylko fraza wyszukiwania', () => {
    const wrapper = mountHeader()

    expect(wrapper.find('input[aria-label="Lokalizacja"]').exists()).toBe(false)
    expect(wrapper.find('input[aria-label="Czego szukasz?"]').exists()).toBe(true)
  })

  it('ma kompaktowe klasy akcji dla wąskiego nagłówka', () => {
    const wrapper = mountHeader({ authenticated: true, isAdmin: true })

    expect(wrapper.find('.actions__logout').exists()).toBe(true)
    expect(wrapper.find('.actions__admin').exists()).toBe(true)
    expect(wrapper.find('.actions__profile-name').exists()).toBe(true)
  })

  it('na mobile zostawia w pasku tylko zmianę motywu, dodawanie i hamburger', () => {
    const wrapper = mountHeader({ authenticated: true, isAdmin: true })

    expect(wrapper.find('.actions__mobile-menu-button').exists()).toBe(true)
    expect(wrapper.find('.actions__desktop-only').exists()).toBe(true)
    expect(wrapper.find('.actions__cta').exists()).toBe(true)
    expect(wrapper.find('.actions__auth').exists()).toBe(false)
    expect(wrapper.find('.actions__logout').exists()).toBe(true)
  })

  it('otwiera mobilne menu z przeniesionymi akcjami', async () => {
    const wrapper = mountHeader({ authenticated: true, isAdmin: true })

    await wrapper.find('.actions__mobile-menu-button').trigger('click')
    await nextTick()

    expect(document.body.textContent).toContain('Menu')
    expect(document.body.textContent).toContain('Ulubione ogłoszenia')
    expect(document.body.textContent).toContain('Wiadomości')
    expect(document.body.textContent).toContain('Panel admina')
    expect(document.body.textContent).toContain('Wyloguj')
    expect(document.body.textContent).toContain('Język')
  })
})
