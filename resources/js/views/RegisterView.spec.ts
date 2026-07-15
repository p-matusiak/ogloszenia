import { createTestingPinia } from '@pinia/testing'
import { flushPromises, mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import { AxiosError, AxiosHeaders } from 'axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'

import RegisterView from '@/views/RegisterView.vue'
import { createTestI18n } from '@/testing/i18n'
import { useAuthStore } from '@/stores/auth'

const push = vi.hoisted(() => vi.fn())

vi.mock('vue-router', () => ({
  useRoute: () => ({ query: {} }),
  useRouter: () => ({ push }),
}))

vi.mock('primevue/usetoast', () => ({ useToast: () => ({ add: vi.fn() }) }))

const RouterLinkStub = defineComponent({
  props: { to: { type: Object, required: true } },
  setup(_, { slots }) {
    return () => h('a', {}, slots.default?.())
  },
})

function mountView() {
  return mount(RegisterView, {
    global: {
      plugins: [
        PrimeVue,
        ToastService,
        createTestI18n(),
        createTestingPinia({ createSpy: vi.fn, stubActions: true }),
      ],
      stubs: { RouterLink: RouterLinkStub },
    },
  })
}

async function fillAndSubmit(wrapper: ReturnType<typeof mountView>): Promise<void> {
  await wrapper.find('#name').setValue('Jan Kowalski')
  await wrapper.find('#email').setValue('jan@example.com')
  await wrapper.find('form').trigger('submit')
  await flushPromises()
}

/** A 422 from Laravel: the one case that still belongs inside the form. */
function validationError(): AxiosError {
  const error = new AxiosError('Unprocessable', 'ERR_BAD_REQUEST')

  error.response = {
    status: 422,
    statusText: 'Unprocessable Content',
    data: { message: 'Dane są nieprawidłowe.', errors: { email: ['Ten e-mail jest już zajęty.'] } },
    headers: {},
    config: { headers: new AxiosHeaders() },
  }

  return error
}

beforeEach(() => {
  vi.clearAllMocks()
})

describe('RegisterView', () => {
  it('po rejestracji prosi o kliknięcie linku aktywacyjnego zamiast przenosić na stronę główną', async () => {
    const wrapper = mountView()

    await fillAndSubmit(wrapper)

    expect(wrapper.text()).toContain('Konto założone')
    expect(wrapper.text()).toContain('jan@example.com')
    expect(wrapper.text()).toContain('Kliknij link z wiadomości')
    expect(wrapper.find('form').exists()).toBe(false)
    expect(push).not.toHaveBeenCalled()
  })

  it('pozwala wysłać link aktywacyjny ponownie z ekranu potwierdzenia', async () => {
    const wrapper = mountView()
    const auth = useAuthStore()

    await fillAndSubmit(wrapper)
    await wrapper.findAll('button')[0]?.trigger('click')

    expect(auth.resendVerification).toHaveBeenCalledOnce()
  })

  it('zostawia użytkownika w formularzu, gdy serwer odrzuci dane', async () => {
    const wrapper = mountView()
    const auth = useAuthStore()

    vi.mocked(auth.register).mockRejectedValueOnce(validationError())

    await fillAndSubmit(wrapper)

    expect(wrapper.find('form').exists()).toBe(true)
    expect(wrapper.text()).toContain('Ten e-mail jest już zajęty.')
    expect(wrapper.text()).not.toContain('Konto założone')
  })
})
