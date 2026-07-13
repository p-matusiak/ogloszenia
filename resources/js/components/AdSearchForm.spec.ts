import { mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import { describe, expect, it } from 'vitest'

import AdSearchForm from '@/components/AdSearchForm.vue'

function mountForm(query = '') {
  return mount(AdSearchForm, {
    props: { query },
    global: {
      plugins: [PrimeVue],
    },
  })
}

describe('AdSearchForm', () => {
  it('nie emituje wyszukiwania podczas wpisywania', async () => {
    const wrapper = mountForm()
    const input = wrapper.find('input[aria-label="Czego szukasz?"]')

    await input.setValue('rower')

    expect(wrapper.emitted('search')).toBeUndefined()
  })

  it('emituje wyszukiwanie po submit Enterem', async () => {
    const wrapper = mountForm()
    const input = wrapper.find('input[aria-label="Czego szukasz?"]')

    await input.setValue('  rower  ')
    await wrapper.find('form').trigger('submit')

    expect(wrapper.emitted('search')).toEqual([['rower']])
  })

  it('przycisk "Szukaj" jest submitterem formularza', async () => {
    const wrapper = mountForm('motor')
    const submitButton = wrapper.findComponent({ name: 'Button' })

    expect(submitButton.props('label')).toBe('Szukaj')

    await wrapper.find('form').trigger('submit')

    expect(wrapper.emitted('search')).toEqual([['motor']])
  })
})
