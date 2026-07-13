<script setup lang="ts">
import Checkbox from 'primevue/checkbox'
import { useId } from 'vue'

const props = defineProps<{
  options: readonly { value: string; label: string }[]
  selected: readonly string[]
}>()

const emit = defineEmits<{ change: [values: string[]] }>()

const groupId = useId()

function toggle(value: string, checked: boolean): void {
  const next = new Set(props.selected)

  if (checked) {
    next.add(value)
  } else {
    next.delete(value)
  }

  // Kolejność z `options`, nie kolejność klikania — URL musi być stabilny.
  emit(
    'change',
    props.options.map((option) => option.value).filter((value) => next.has(value)),
  )
}
</script>

<template>
  <ul class="group">
    <li
      v-for="option in options"
      :key="option.value"
      class="group__item"
    >
      <Checkbox
        :input-id="`${groupId}-${option.value}`"
        :model-value="selected.includes(option.value)"
        binary
        @update:model-value="toggle(option.value, $event)"
      />
      <label :for="`${groupId}-${option.value}`">{{ option.label }}</label>
    </li>
  </ul>
</template>

<style scoped>
.group {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.group__item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.group__item label {
  font-size: 0.875rem;
  cursor: pointer;
}
</style>
