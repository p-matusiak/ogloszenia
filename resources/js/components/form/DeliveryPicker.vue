<script setup lang="ts">
import { computed, watch } from 'vue'

import MoneyInput from '@/components/form/MoneyInput.vue'
import { DELIVERY_ORDER, deliveryLabel } from '@/composables/useOfferLabels'
import type { DeliveryMethod } from '@/types/api'

type DeliveryPrices = Partial<Record<DeliveryMethod, string>>

const props = defineProps<{
  methods: DeliveryMethod[]
  prices: DeliveryPrices
}>()

/**
 * Metody i ich ceny wyjeżdżają razem, jednym zdarzeniem. Rozbicie na dwa
 * (`update:methods` + `update:prices`) sprawiało, że odznaczenie formy nie
 * działało: rodzic składa łatkę na `props.modelValue`, a ten odświeża się
 * dopiero przy przerysowaniu, więc druga łatka cofała pierwszą.
 */
const emit = defineEmits<{
  change: [value: { methods: DeliveryMethod[]; prices: DeliveryPrices }]
}>()

const icons: Record<DeliveryMethod, string> = {
  personal: 'pi pi-home',
  courier: 'pi pi-truck',
  parcel_locker: 'pi pi-box',
  post: 'pi pi-envelope',
  local: 'pi pi-car',
}

const hints: Record<DeliveryMethod, string> = {
  personal: 'Kupujący odbierze przedmiot osobiście, bez opłaty.',
  courier: 'Dostawa kurierem pod adres.',
  parcel_locker: 'Wysyłka do paczkomatu.',
  post: 'Wysyłka pocztą.',
  local: 'Dostawa w okolicy sprzedającego.',
}

/** Odbiór osobisty nic nie kosztuje, więc nie pytamy o jego cenę. */
const FREE_METHODS: readonly DeliveryMethod[] = ['personal']

function isFree(method: DeliveryMethod): boolean {
  return FREE_METHODS.includes(method)
}

/** Zawsze w kolejności z DELIVERY_ORDER, nie w kolejności klikania. */
const selected = computed(() => DELIVERY_ORDER.filter((method) => props.methods.includes(method)))

const priced = computed(() => selected.value.filter((method) => !isFree(method)))

const emptyPricesMessage = computed(() =>
  selected.value.length === 0
    ? 'Zaznacz formę dostawy, żeby podać jej koszt.'
    : 'Odbiór osobisty nie ma kosztu dostawy.',
)

/**
 * Ogłoszenie zapisane wcześniej mogło przynieść cenę odbioru osobistego.
 * Nie ma dla niej pola, więc nie zostawiamy jej w ładunku bez nadzoru.
 */
watch(
  () => props.prices,
  (prices) => {
    const stale = FREE_METHODS.filter((method) => prices[method] !== undefined)
    if (stale.length === 0) {
      return
    }

    const next = { ...prices }
    for (const method of stale) {
      delete next[method]
    }

    emit('change', { methods: props.methods, prices: next })
  },
  { immediate: true, deep: true },
)

function toggle(method: DeliveryMethod, checked: boolean): void {
  const methods = DELIVERY_ORDER.filter((candidate) =>
    candidate === method ? checked : props.methods.includes(candidate),
  )

  // Odznaczenie metody kasuje jej cenę — inaczej zostałaby sierota, którą
  // backend odrzuciłby jako cenę dla niewybranej metody dostawy.
  const prices = { ...props.prices }
  if (!checked) {
    delete prices[method]
  }

  emit('change', { methods, prices })
}

/** Pusta kwota znika z ładunku: reguła `nullable|numeric` odrzuciłaby „”. */
function setPrice(method: DeliveryMethod, value: string): void {
  const prices = { ...props.prices }

  if (value === '') {
    delete prices[method]
  } else {
    prices[method] = value
  }

  emit('change', { methods: props.methods, prices })
}
</script>

<template>
  <div class="delivery">
    <section class="step">
      <header class="step__header">
        <h3 class="step__title">
          Formy dostawy
        </h3>
        <span
          v-if="selected.length > 0"
          class="step__count"
        >Wybrano {{ selected.length }}</span>
      </header>

      <div class="options">
        <label
          v-for="method in DELIVERY_ORDER"
          :key="method"
          class="option"
          :class="{ 'option--selected': methods.includes(method) }"
        >
          <input
            type="checkbox"
            :checked="methods.includes(method)"
            class="option__input"
            @change="toggle(method, ($event.target as HTMLInputElement).checked)"
          >

          <span
            class="option__box"
            aria-hidden="true"
          >
            <i class="pi pi-check" />
          </span>

          <i
            :class="icons[method]"
            class="option__icon"
            aria-hidden="true"
          />

          <span class="option__name">{{ deliveryLabel(method) }}</span>
          <span class="option__hint">{{ hints[method] }}</span>
        </label>
      </div>
    </section>

    <section class="step">
      <header class="step__header">
        <h3 class="step__title">
          Koszt dostawy
        </h3>
        <span class="step__count">Opcjonalne</span>
      </header>

      <!-- Ceny pojawiają się dopiero po wyborze formy: pusty formularz nie
           pokazuje pięciu pól, z których cztery i tak byłyby wyłączone. -->
      <Transition
        name="reveal"
        mode="out-in"
      >
        <ul
          v-if="priced.length > 0"
          key="prices"
          class="prices"
        >
          <li
            v-for="method in priced"
            :key="method"
            class="prices__row"
          >
            <label
              :for="`delivery-price-${method}`"
              class="prices__name"
            >
              <i
                :class="icons[method]"
                aria-hidden="true"
              />
              {{ deliveryLabel(method) }}
            </label>

            <MoneyInput
              :id="`delivery-price-${method}`"
              :model-value="prices[method] ?? ''"
              :aria-label="`Koszt dostawy — ${deliveryLabel(method)}`"
              :max-major-digits="4"
              class="prices__input"
              @update:model-value="setPrice(method, $event)"
            />
          </li>
        </ul>

        <p
          v-else
          key="empty"
          class="prices__empty"
        >
          {{ emptyPricesMessage }}
        </p>
      </Transition>

      <p
        v-if="priced.length > 0"
        class="step__note"
      >
        <i
          class="pi pi-info-circle"
          aria-hidden="true"
        />
        Puste pole znaczy, że koszt ustalisz z kupującym.
      </p>
    </section>
  </div>
</template>

<style scoped>
.delivery {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.step__header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.step__title {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
}

.step__count {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.step__note {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0.75rem 0 0;
  font-size: 0.7rem;
  color: var(--text-muted);
}

.options {
  display: grid;
  gap: 0.625rem;
  grid-template-columns: repeat(auto-fit, minmax(12.5rem, 1fr));
}

/* Karta powtarza język ConditionPicker; kwadrat zamiast ptaszka w kółku,
   bo form dostawy wybiera się kilka naraz. */
.option {
  position: relative;
  display: grid;
  grid-template-columns: auto auto 1fr;
  grid-template-areas:
    'box icon name'
    'box icon hint';
  align-items: center;
  gap: 0 0.625rem;
  padding: 0.875rem;
  border: 1px solid var(--surface-border);
  border-radius: 0.625rem;
  background: var(--surface-card);
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    background 0.15s ease;
}

.option:hover {
  border-color: color-mix(in srgb, var(--p-primary-color) 45%, transparent);
}

.option--selected {
  border-color: var(--p-primary-color);
  background: color-mix(in srgb, var(--p-primary-color) 6%, transparent);
}

.option:has(.option__input:focus-visible) {
  outline: 2px solid var(--p-primary-color);
  outline-offset: 2px;
}

/* Checkbox zostaje w drzewie dostępności — tylko go nie widać. */
.option__input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.option__box {
  grid-area: box;
  display: grid;
  place-items: center;
  width: 1.15rem;
  height: 1.15rem;
  border: 1px solid var(--surface-border);
  border-radius: 0.3rem;
  background: var(--surface-card);
  color: transparent;
  font-size: 0.6rem;
  transition:
    background 0.15s ease,
    border-color 0.15s ease,
    color 0.15s ease;
}

.option--selected .option__box {
  border-color: var(--p-primary-color);
  background: var(--p-primary-color);
  color: #fff;
}

.option__icon {
  grid-area: icon;
  color: var(--text-muted);
  font-size: 1.05rem;
  transition: color 0.15s ease;
}

.option--selected .option__icon {
  color: var(--p-primary-color);
}

.option__name {
  grid-area: name;
  font-size: 0.875rem;
  font-weight: 600;
}

.option__hint {
  grid-area: hint;
  font-size: 0.7rem;
  line-height: 1.35;
  color: var(--text-muted);
}

.prices {
  list-style: none;
  margin: 0;
  padding: 0;
  border: 1px solid var(--surface-border);
  border-radius: 0.625rem;
  background: var(--surface-muted);
}

.prices__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.625rem 0.875rem;
}

.prices__row + .prices__row {
  border-top: 1px solid var(--surface-border);
}

.prices__name {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
}

.prices__name i {
  color: var(--text-muted);
}

.prices__input {
  width: 9.5rem;
  flex-shrink: 0;
  background: var(--surface-card);
}

.prices__empty {
  margin: 0;
  padding: 1.25rem 0.875rem;
  border: 1px dashed var(--surface-border);
  border-radius: 0.625rem;
  text-align: center;
  font-size: 0.75rem;
  color: var(--text-muted);
}

.reveal-enter-active,
.reveal-leave-active {
  transition:
    opacity 0.18s ease,
    transform 0.18s ease;
}

.reveal-enter-from,
.reveal-leave-to {
  opacity: 0;
  transform: translateY(-0.25rem);
}
</style>
