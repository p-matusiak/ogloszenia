<script setup lang="ts">
import { CONDITION_ORDER, conditionLabel } from '@/composables/useOfferLabels'
import type { AdCondition } from '@/types/api'

const props = defineProps<{ modelValue: AdCondition | null; invalid?: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [condition: AdCondition | null] }>()

const icons: Record<AdCondition, string> = {
  new: 'pi pi-sparkles',
  used: 'pi pi-history',
  damaged: 'pi pi-wrench',
}

const hints: Record<AdCondition, string> = {
  new: 'Nieużywany, zwykle w oryginalnym opakowaniu.',
  used: 'Nosi ślady użytkowania, w pełni sprawny.',
  damaged: 'Ma wady lub wymaga naprawy.',
}

/**
 * Kliknięcie w zaznaczoną kartę odznacza ją — stan jest polem opcjonalnym,
 * a natywne radio samo z siebie nie pozwala wrócić do „nie podano”.
 * Radio nie emituje `change` przy ponownym kliknięciu, więc łapiemy `click`.
 */
function onClick(condition: AdCondition): void {
  if (props.modelValue === condition) {
    emit('update:modelValue', null)
  }
}
</script>

<template>
  <div
    class="conditions"
    :class="{ 'conditions--invalid': invalid }"
  >
    <label
      v-for="condition in CONDITION_ORDER"
      :key="condition"
      class="condition"
      :class="{ 'condition--selected': modelValue === condition }"
    >
      <input
        type="radio"
        name="ad-condition"
        :value="condition"
        :checked="modelValue === condition"
        class="condition__input"
        @change="emit('update:modelValue', condition)"
        @click="onClick(condition)"
      >

      <i
        :class="icons[condition]"
        class="condition__icon"
        aria-hidden="true"
      />

      <span class="condition__name">{{ conditionLabel(condition) }}</span>
      <span class="condition__hint">{{ hints[condition] }}</span>

      <i
        class="pi pi-check-circle condition__check"
        aria-hidden="true"
      />
    </label>
  </div>
</template>

<style scoped>
.conditions {
  display: grid;
  gap: 0.625rem;
  grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
}

.condition {
  position: relative;
  display: grid;
  grid-template-columns: auto 1fr;
  grid-template-areas:
    'icon name'
    'icon hint';
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

.condition:hover {
  border-color: color-mix(in srgb, var(--p-primary-color) 45%, transparent);
}

.condition--selected {
  border-color: var(--p-primary-color);
  background: color-mix(in srgb, var(--p-primary-color) 6%, transparent);
}

.conditions--invalid .condition:not(.condition--selected) {
  border-color: var(--p-inputtext-invalid-border-color, #ef4444);
}

/* Radio zostaje w drzewie dostępności i obsługuje strzałki — tylko go nie widać. */
.condition__input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.condition:has(.condition__input:focus-visible) {
  outline: 2px solid var(--p-primary-color);
  outline-offset: 2px;
}

.condition__icon {
  grid-area: icon;
  display: grid;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  background: var(--surface-muted);
  color: var(--text-muted);
  font-size: 1rem;
  transition:
    background 0.15s ease,
    color 0.15s ease;
}

.condition--selected .condition__icon {
  background: color-mix(in srgb, var(--p-primary-color) 14%, transparent);
  color: var(--p-primary-color);
}

/* Miejsce po prawej należy do znacznika wyboru. */
.condition__name {
  grid-area: name;
  padding-right: 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.condition__hint {
  grid-area: hint;
  font-size: 0.7rem;
  line-height: 1.35;
  color: var(--text-muted);
}

.condition__check {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  font-size: 0.8125rem;
  color: var(--p-primary-color);
  opacity: 0;
  transition: opacity 0.15s ease;
}

.condition--selected .condition__check {
  opacity: 1;
}
</style>
