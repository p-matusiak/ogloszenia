<script setup lang="ts">
import InputNumber from 'primevue/inputnumber'

defineProps<{ min?: number; max?: number }>()

const emit = defineEmits<{
  'update:min': [value: number | undefined]
  'update:max': [value: number | undefined]
  submit: []
}>()

/** Wyczyszczone pole daje `null`, a filtr rozpoznaje tylko brak wartości. */
function normalise(value: number | null): number | undefined {
  return typeof value === 'number' ? value : undefined
}
</script>

<template>
  <div
    class="price"
    @keyup.enter="emit('submit')"
  >
    <InputNumber
      :model-value="min ?? null"
      placeholder="Od"
      aria-label="Cena od"
      :min="0"
      fluid
      @update:model-value="emit('update:min', normalise($event))"
    />
    <InputNumber
      :model-value="max ?? null"
      placeholder="Do"
      aria-label="Cena do"
      :min="0"
      fluid
      @update:model-value="emit('update:max', normalise($event))"
    />
  </div>
</template>

<style scoped>
.price {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}
</style>
