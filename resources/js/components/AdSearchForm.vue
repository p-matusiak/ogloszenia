<script setup lang="ts">
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

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
    <div class="search__capsule search-capsule">
      <IconField class="search-capsule__field">
        <InputIcon class="pi pi-search search-capsule__icon" />
        <InputText
          :model-value="draft"
          :placeholder="t('nav.searchPlaceholder')"
          :aria-label="t('nav.searchPlaceholder')"
          unstyled
          class="search-capsule__input"
          @update:model-value="onInput"
        />
      </IconField>

      <Button
        type="submit"
        :label="t('nav.searchSubmit')"
        icon="pi pi-search"
        class="search__submit search-capsule__submit"
      />
    </div>

    <Button
      v-if="showFiltersButton"
      type="button"
      icon="pi pi-sliders-h"
      :aria-label="t('filters.openAria')"
      severity="secondary"
      outlined
      class="search__filters"
      @click="emit('openFilters')"
    />
  </form>
</template>

<style scoped>
.search {
  display: flex;
  align-items: stretch;
  gap: 0.5rem;
}

.search__capsule {
  flex: 1;
  min-width: 0;
}

.search__filters {
  flex-shrink: 0;
}

.search__submit :deep(.p-button-label) {
  display: none;
}

@media (width >= 30rem) {
  .search__submit :deep(.p-button-label) {
    display: inline;
  }

  .search__submit :deep(.p-button-icon) {
    display: none;
  }
}
</style>
