<script setup lang="ts">
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import { ref, watch } from 'vue'

const props = defineProps<{
  query: string
  showFiltersButton?: boolean
}>()

const emit = defineEmits<{
  search: [query: string]
  openFilters: []
}>()

const draft = ref(props.query)

watch(
  () => props.query,
  (value) => {
    draft.value = value
  },
)

function onInput(value: string | undefined): void {
  draft.value = value ?? ''
}

function submit(): void {
  emit('search', draft.value.trim())
}
</script>

<template>
  <form
    class="search"
    role="search"
    @submit.prevent="submit"
  >
    <IconField class="search__field">
      <InputIcon class="pi pi-search" />
      <InputText
        :model-value="draft"
        placeholder="Czego szukasz?"
        aria-label="Czego szukasz?"
        fluid
        @update:model-value="onInput"
      />
    </IconField>

    <Button
      v-if="showFiltersButton"
      type="button"
      icon="pi pi-sliders-h"
      aria-label="Filtry"
      severity="secondary"
      outlined
      @click="emit('openFilters')"
    />

    <Button
      type="submit"
      label="Szukaj"
      class="search__submit"
    />
  </form>
</template>

<style scoped>
.search {
  display: flex;
  align-items: stretch;
  gap: 0.5rem;
}

.search__field {
  flex: 1;
  min-width: 0;
}

.search__submit :deep(.p-button-label) {
  display: none;
}

@media (width >= 30rem) {
  .search__submit :deep(.p-button-label) {
    display: inline;
  }
}
</style>
