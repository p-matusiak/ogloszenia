import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

import SendMessagePanel from '@/components/messages/SendMessagePanel.vue'
import { useConversationsStore } from '@/stores/conversations'

const add = vi.fn()
const push = vi.fn().mockResolvedValue(undefined)

vi.mock('primevue/usetoast', () => ({ useToast: () => ({ add }) }))

vi.mock('vue-router', () => ({
  useRouter: () => ({ push }),
}))

const ButtonStub = {
  props: ['label', 'loading', 'disabled'],
  emits: ['click'],
  template: '<button :disabled="disabled" @click="$emit(\'click\')">{{ label }}</button>',
}

const TextareaStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template:
    '<textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
}

function mountPanel() {
  return mount(SendMessagePanel, {
    props: { slug: 'test-ad' },
    global: {
      plugins: [createTestingPinia({ createSpy: vi.fn })],
      stubs: { Button: ButtonStub, Textarea: TextareaStub },
    },
  })
}

describe('SendMessagePanel', () => {
  it('sends a message through the store', async () => {
    const wrapper = mountPanel()
    const store = useConversationsStore()
    store.startFromAd = vi.fn().mockResolvedValue({ id: 7 })

    await wrapper.get('textarea').setValue('Dzień dobry')
    await wrapper.get('button').trigger('click')

    expect(store.startFromAd).toHaveBeenCalledWith('test-ad', 'Dzień dobry')
    expect(push).toHaveBeenCalledWith({ name: 'messages.show', params: { id: 7 } })
  })
})