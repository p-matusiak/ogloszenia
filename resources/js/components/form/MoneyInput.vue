<script setup lang="ts">
import { ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: string
    id?: string
    disabled?: boolean
    invalid?: boolean
    ariaLabel?: string
    /** 99 999 999,99 zł to górny limit ceny w StoreAdRequest. */
    maxMajorDigits?: number
  }>(),
  { id: undefined, disabled: false, invalid: false, ariaLabel: undefined, maxMajorDigits: 8 },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const majorEl = ref<HTMLInputElement | null>(null)
const minorEl = ref<HTMLInputElement | null>(null)

/** Złote i grosze żyją osobno, bo osobno się je wpisuje. */
const major = ref('')
const minor = ref('')

function onlyDigits(raw: string): string {
  return raw.replace(/\D/g, '')
}

/** Kanoniczna postać dla backendu: „1234.50”, kropka, bez spacji. Puste pole to ''. */
function canonical(majorPart: string, minorPart: string): string {
  if (majorPart === '' && minorPart === '') {
    return ''
  }

  return `${majorPart === '' ? '0' : majorPart}.${minorPart.padEnd(2, '0')}`
}

/**
 * „1234.5” to 1234 zł 50 gr, nie 5 gr — brakującą cyfrę dopisujemy z prawej.
 * Brak kropki w ogóle zostawia grosze puste, żeby placeholder nadal był widoczny.
 */
function parse(raw: string): [string, string] {
  const normalised = raw.trim().replace(',', '.')
  if (normalised === '') {
    return ['', '']
  }

  const [rawMajor, rawMinor] = normalised.split('.')

  return [
    onlyDigits(rawMajor ?? '').slice(0, props.maxMajorDigits),
    rawMinor === undefined ? '' : onlyDigits(rawMinor).padEnd(2, '0').slice(0, 2),
  ]
}

/** „1234” z zewnątrz i „1234,00” w polu to ta sama kwota — nie przepisujemy pola. */
function matchesModel(raw: string): boolean {
  const current = canonical(major.value, minor.value)

  if (raw === '' || current === '') {
    return raw === current
  }

  return Number(raw.replace(',', '.')) === Number(current)
}

watch(
  () => props.modelValue,
  (raw) => {
    if (matchesModel(raw)) {
      return
    }

    ;[major.value, minor.value] = parse(raw)
  },
  { immediate: true },
)

function publish(): void {
  emit('update:modelValue', canonical(major.value, minor.value))
}

function focusMajor(): void {
  majorEl.value?.focus()
  majorEl.value?.setSelectionRange(major.value.length, major.value.length)
}

function focusMinor(): void {
  minorEl.value?.focus()
  minorEl.value?.setSelectionRange(0, 0)
}

function caretAtEnd(el: HTMLInputElement): boolean {
  return el.selectionStart === el.value.length && el.selectionEnd === el.value.length
}

function caretAtStart(el: HTMLInputElement): boolean {
  return el.selectionStart === 0 && el.selectionEnd === 0
}

function onMajorInput(event: Event): void {
  const el = event.target as HTMLInputElement
  // Wiodące zera znikają, ale samotne „0” zostaje: to poprawna kwota.
  const cleaned = onlyDigits(el.value).replace(/^0+(?=\d)/, '').slice(0, props.maxMajorDigits)

  major.value = cleaned
  el.value = cleaned
  publish()
}

function onMinorInput(event: Event): void {
  const el = event.target as HTMLInputElement
  const cleaned = onlyDigits(el.value).slice(0, 2)

  minor.value = cleaned
  el.value = cleaned
  publish()
}

/** Strzałka w prawo z końca złotówek przechodzi do groszy; przecinek i kropka też. */
function onMajorKeydown(event: KeyboardEvent): void {
  const el = event.target as HTMLInputElement

  if (event.key === ',' || event.key === '.') {
    event.preventDefault()
    focusMinor()
    return
  }

  if (event.key === 'ArrowRight' && caretAtEnd(el)) {
    event.preventDefault()
    focusMinor()
  }
}

/** Z początku groszy strzałka w lewo i backspace wracają do złotówek. */
function onMinorKeydown(event: KeyboardEvent): void {
  const el = event.target as HTMLInputElement

  if ((event.key === 'ArrowLeft' || event.key === 'Backspace') && caretAtStart(el)) {
    event.preventDefault()
    focusMajor()
  }
}

/** Wklejone „1 234,56” trafia w całości, zamiast wylądować jako 123456 zł. */
function onMajorPaste(event: ClipboardEvent): void {
  const text = event.clipboardData?.getData('text') ?? ''
  if (!/[.,]/.test(text)) {
    return
  }

  event.preventDefault()
  ;[major.value, minor.value] = parse(text.replace(/\s/g, ''))
  publish()
}

/** Po wyjściu z pola kwota jest kompletna: „12” staje się „12,00”. */
function onFocusOut(event: FocusEvent): void {
  const next = event.relatedTarget
  if (next instanceof Node && (event.currentTarget as HTMLElement).contains(next)) {
    return
  }

  /*
   * Skasowanie złotówek nad groszami „00” znaczy „nie podaję ceny”, a nie
   * „0,00 zł” — inaczej pola raz wypełnionego nie dałoby się już wyczyścić.
   * Same grosze bez złotówek to jednak prawdziwa kwota: „,50” to 0,50 zł.
   */
  if (major.value === '' && Number(minor.value || '0') === 0) {
    if (minor.value !== '') {
      minor.value = ''
      publish()
    }

    return
  }

  if (major.value === '') {
    major.value = '0'
  }
  minor.value = minor.value.padEnd(2, '0')
  publish()
}
</script>

<template>
  <div
    class="p-inputtext money"
    :class="{ 'p-invalid': invalid, 'money--disabled': disabled }"
    @mousedown.self.prevent="focusMajor"
    @focusout="onFocusOut"
  >
    <!-- Komentarz zostaje w środku korzenia: postawiony przed <div> robi
         z komponentu fragment o dwóch węzłach i nasłuchy przestają go łapać.
         `p-inputtext` daje ramkę, padding i promień prosto z motywu PrimeVue,
         więc pole stoi równo z sąsiednimi inputami bez kopiowania wartości. -->
    <input
      :id="id"
      ref="majorEl"
      :value="major"
      :disabled="disabled"
      :aria-label="ariaLabel ? `${ariaLabel} — złote` : 'Złote'"
      class="money__part money__major"
      inputmode="numeric"
      autocomplete="off"
      placeholder="0"
      @input="onMajorInput"
      @keydown="onMajorKeydown"
      @paste="onMajorPaste"
    >

    <span
      class="money__comma"
      aria-hidden="true"
    >,</span>

    <input
      ref="minorEl"
      :value="minor"
      :disabled="disabled"
      :aria-label="ariaLabel ? `${ariaLabel} — grosze` : 'Grosze'"
      class="money__part money__minor"
      inputmode="numeric"
      autocomplete="off"
      placeholder="00"
      maxlength="2"
      @input="onMinorInput"
      @keydown="onMinorKeydown"
    >

    <span
      class="money__currency"
      aria-hidden="true"
    >zł</span>
  </div>
</template>

<style scoped>
.money {
  display: flex;
  align-items: baseline;
  gap: 0.1rem;
  cursor: text;
}

/* `:enabled:focus` z motywu nie łapie diva, więc pierścień rysujemy sami. */
.money:focus-within {
  border-color: var(--p-inputtext-focus-border-color, var(--p-primary-color));
  outline: 2px solid var(--p-primary-color);
  outline-offset: 2px;
}

.money--disabled {
  cursor: default;
  opacity: 0.6;
}

.money__part {
  min-width: 0;
  padding: 0;
  border: 0;
  background: none;
  color: inherit;
  font: inherit;
  font-variant-numeric: tabular-nums;
}

.money__part:focus {
  outline: none;
}

.money__part::placeholder {
  color: var(--text-muted);
  opacity: 0.7;
}

/* Złotówki rosną w lewo od przecinka, jak na paragonie. */
.money__major {
  flex: 1;
  text-align: right;
}

.money__minor {
  width: 2ch;
  flex-shrink: 0;
}

.money__comma {
  pointer-events: none;
}

.money__currency {
  margin-inline-start: 0.4rem;
  color: var(--text-muted);
  font-size: 0.8125rem;
  pointer-events: none;
}
</style>
