import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import MoneyInput from '@/components/form/MoneyInput.vue'

function mountInput(modelValue = '') {
  const wrapper = mount(MoneyInput, {
    props: { modelValue },
    attachTo: document.body,
  })

  const [major, minor] = wrapper.findAll('input')

  return {
    wrapper,
    major: major!,
    minor: minor!,
    majorEl: major!.element as HTMLInputElement,
    minorEl: minor!.element as HTMLInputElement,
  }
}

/** Ostatnia kwota wypchnięta do rodzica. */
function lastEmit(wrapper: ReturnType<typeof mountInput>['wrapper']): string | undefined {
  const events = wrapper.emitted('update:modelValue')

  return events?.at(-1)?.[0] as string | undefined
}

describe('MoneyInput', () => {
  it('pokazuje przecinek zanim cokolwiek wpiszemy', () => {
    const { wrapper, majorEl, minorEl } = mountInput()

    expect(wrapper.text()).toContain(',')
    expect(majorEl.value).toBe('')
    expect(minorEl.value).toBe('')
  })

  it('rozdziela kwotę z modelu na złote i grosze', () => {
    const { majorEl, minorEl } = mountInput('1234.567')

    expect(majorEl.value).toBe('1234')
    expect(minorEl.value).toBe('56')
  })

  it('traktuje „1234.5” jako 50 groszy, nie 5', () => {
    const { minorEl } = mountInput('1234.5')

    expect(minorEl.value).toBe('50')
  })

  it('zostawia grosze puste, gdy model nie ma części dziesiętnej', () => {
    const { minorEl } = mountInput('1234')

    expect(minorEl.value).toBe('')
  })

  it('wpisane złotówki emituje w postaci kanonicznej z kropką', async () => {
    const { wrapper, major, majorEl } = mountInput()

    majorEl.value = '1250'
    await major.trigger('input')

    expect(lastEmit(wrapper)).toBe('1250.00')
  })

  it('odrzuca znaki inne niż cyfry i wiodące zera', async () => {
    const { wrapper, major, majorEl } = mountInput()

    majorEl.value = '00a12b'
    await major.trigger('input')

    expect(majorEl.value).toBe('12')
    expect(lastEmit(wrapper)).toBe('12.00')
  })

  it('składa złote i grosze w jedną kwotę', async () => {
    const { wrapper, major, majorEl, minor, minorEl } = mountInput()

    majorEl.value = '12'
    await major.trigger('input')
    minorEl.value = '5'
    await minor.trigger('input')

    expect(lastEmit(wrapper)).toBe('12.50')
  })

  it('strzałka w prawo z końca złotówek przechodzi do groszy', async () => {
    const { major, majorEl, minorEl } = mountInput('12')

    majorEl.focus()
    majorEl.setSelectionRange(2, 2)
    await major.trigger('keydown', { key: 'ArrowRight' })

    expect(document.activeElement).toBe(minorEl)
  })

  it('strzałka w prawo ze środka złotówek nie ucieka do groszy', async () => {
    const { major, majorEl, minorEl } = mountInput('1234')

    majorEl.focus()
    majorEl.setSelectionRange(1, 1)
    await major.trigger('keydown', { key: 'ArrowRight' })

    expect(document.activeElement).toBe(majorEl)
    expect(document.activeElement).not.toBe(minorEl)
  })

  it('przecinek też przeskakuje do groszy', async () => {
    const { major, majorEl, minorEl } = mountInput('12')

    majorEl.focus()
    await major.trigger('keydown', { key: ',' })

    expect(document.activeElement).toBe(minorEl)
  })

  it('backspace na początku groszy wraca do złotówek', async () => {
    const { minor, minorEl, majorEl } = mountInput('12.34')

    minorEl.focus()
    minorEl.setSelectionRange(0, 0)
    await minor.trigger('keydown', { key: 'Backspace' })

    expect(document.activeElement).toBe(majorEl)
  })

  it('wklejona kwota z przecinkiem trafia w obie części', async () => {
    const { wrapper, major, majorEl, minorEl } = mountInput()

    await major.trigger('paste', {
      clipboardData: { getData: () => '1 234,56' },
    })

    expect(majorEl.value).toBe('1234')
    expect(minorEl.value).toBe('56')
    expect(lastEmit(wrapper)).toBe('1234.56')
  })

  it('wyjście z pola dopełnia grosze do dwóch cyfr', async () => {
    const { wrapper, major, majorEl, minorEl } = mountInput()

    majorEl.value = '12'
    await major.trigger('input')
    await wrapper.trigger('focusout', { relatedTarget: document.body })

    expect(minorEl.value).toBe('00')
  })

  it('skasowanie złotówek nad zerowymi groszami czyści całą kwotę', async () => {
    const { wrapper, major, majorEl, minorEl } = mountInput('15.00')

    majorEl.value = ''
    await major.trigger('input')
    await wrapper.trigger('focusout')

    expect(minorEl.value).toBe('')
    expect(lastEmit(wrapper)).toBe('')
  })

  it('same grosze bez złotówek to prawdziwa kwota, nie puste pole', async () => {
    const { wrapper, minor, minorEl } = mountInput()

    minorEl.value = '50'
    await minor.trigger('input')
    await wrapper.trigger('focusout')

    expect(lastEmit(wrapper)).toBe('0.50')
  })

  it('puste pole zostaje puste po wyjściu i emituje pusty ciąg', async () => {
    const { wrapper, major, majorEl } = mountInput()

    majorEl.value = ''
    await major.trigger('input')
    await wrapper.trigger('focusout', { relatedTarget: document.body })

    expect(lastEmit(wrapper)).toBe('')
  })

  it('nie kasuje wpisanych groszy, gdy rodzic odeśle liczbę bez zer', async () => {
    const { wrapper, major, majorEl, minor, minorEl } = mountInput()

    majorEl.value = '1234'
    await major.trigger('input')
    minorEl.value = '00'
    await minor.trigger('input')

    // Rodzic trzyma cenę jako number, więc „1234.00” wraca jako „1234”.
    await wrapper.setProps({ modelValue: '1234' })

    expect(minorEl.value).toBe('00')
  })
})
