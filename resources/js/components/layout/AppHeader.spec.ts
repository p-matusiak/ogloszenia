import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import { ref } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import AppHeader from '@/components/layout/AppHeader.vue'

const push = vi.hoisted(() => vi.fn())
const logout = vi.hoisted(() => vi.fn())
const initialise = vi.hoisted(() => vi.fn())

vi.mock('vue-router', () => ({
  useRouter: () => ({ push }),
}))

vi.mock('@/composables/useTheme', () => ({
  useTheme: () => ({
    isDark: ref(false),
    toggle: vi.fn(),
    initialise,
  }),
}))

function mountHeader() {
  return mount(AppHeader, {
    props: { filters: {} },
    global: {
      plugins: [
        PrimeVue,
        createTestingPinia({
          createSpy: vi.fn,
          initialState: {
            auth: {
              user: null,
              isLoading: false,
              isResolved: true,
            },
          },
          stubActions: false,
        }),
      ],
      stubs: {
        RouterLink: true,
      },
    },
  })
}

beforeEach(() => {
  push.mockReset()
  push.mockResolvedValue(undefined)
  logout.mockReset()
  initialise.mockReset()
})

describe('AppHeader', () => {
  it('nie wyszukuje podczas wpisywania', async () => {
    const wrapper = mountHeader()

    await wrapper.find('input[aria-label="Czego szukasz?"]').setValue('rower')

    expect(push).not.toHaveBeenCalled()
  })

  it('wyszukuje dopiero po submit formularza', async () => {
    const wrapper = mountHeader()

    await wrapper.find('input[aria-label="Czego szukasz?"]').setValue('rower')
    await wrapper.find('form').trigger('submit')

    expect(push).toHaveBeenCalledOnce()
    expect(push).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'home',
        query: expect.objectContaining({ q: 'rower', sort: 'relevance' }),
      }),
    )
  })
})
