<script setup lang="ts">
defineOptions({ name: 'LocationSearchField' })

import InputText from 'primevue/inputtext'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { useDebouncedCallback } from '@/composables/useDebouncedCallback'
import { searchLocations, type GeocodingResult } from '@/composables/useGeocoding'
import { isWholePolandLabel, resolveLocationLabel } from '@/composables/useListingGeoFilters'

export interface LocationSelection {
  label: string
  latitude: number | undefined
  longitude: number | undefined
}

const props = withDefaults(
  defineProps<{
    label?: string
    latitude?: number
    longitude?: number
    compact?: boolean
    inputClass?: string
    placeholder?: string
    ariaLabel?: string
    fluid?: boolean
    showFeedback?: boolean
  }>(),
  {
    label: '',
    latitude: undefined,
    longitude: undefined,
    compact: false,
    inputClass: undefined,
    placeholder: 'Cała Polska',
    ariaLabel: 'Lokalizacja',
    fluid: false,
    showFeedback: false,
  },
)

const emit = defineEmits<{
  change: [value: LocationSelection]
  submit: []
}>()

const query = ref(props.label)
const suggestions = ref<GeocodingResult[]>([])
const searchError = ref<string | null>(null)
const isSearching = ref(false)
const selectedLatitude = ref<number | undefined>(props.latitude)
const selectedLongitude = ref<number | undefined>(props.longitude)
const inputRef = ref<{ $el: HTMLElement } | null>(null)
const rootRef = ref<HTMLElement | null>(null)

const showSearchHint = ref(false)
const floatingStyle = ref<Record<string, string>>({})

function inputElement(): HTMLElement | null {
  const root = inputRef.value?.$el

  return root instanceof HTMLInputElement ? root : root?.querySelector('input') ?? null
}

function syncFromProps(label: string, latitude: number | undefined, longitude: number | undefined): void {
  query.value = label
  selectedLatitude.value = latitude
  selectedLongitude.value = longitude
  suggestions.value = []
  searchError.value = null
  showSearchHint.value = false
}

watch(
  () => [props.label, props.latitude, props.longitude] as const,
  ([label, latitude, longitude]) => {
    const normalizedLabel = label ?? ''

    if (
      normalizedLabel === query.value &&
      latitude === selectedLatitude.value &&
      longitude === selectedLongitude.value
    ) {
      return
    }

    if (inputElement() === document.activeElement) {
      return
    }

    syncFromProps(normalizedLabel, latitude, longitude)
  },
)

function emitChange(
  label: string,
  latitude: number | undefined,
  longitude: number | undefined,
): void {
  selectedLatitude.value = latitude
  selectedLongitude.value = longitude
  emit('change', { label, latitude, longitude })
}

function getSelection(): LocationSelection {
  return {
    label: query.value,
    latitude: selectedLatitude.value,
    longitude: selectedLongitude.value,
  }
}

function updateFloatingPosition(): void {
  const anchor = rootRef.value ?? inputElement()

  if (anchor === null) {
    return
  }

  const rect = anchor.getBoundingClientRect()

  floatingStyle.value = {
    position: 'fixed',
    top: `${rect.bottom + 4}px`,
    left: `${rect.left}px`,
    width: `${rect.width}px`,
    zIndex: '2000',
  }
}

async function waitForSearch(): Promise<void> {
  if (!isSearching.value) {
    return
  }

  await new Promise<void>((resolve) => {
    const stop = watch(isSearching, (searching) => {
      if (!searching) {
        stop()
        resolve()
      }
    })
  })
}

async function resolveSelection(): Promise<LocationSelection> {
  debouncedSearch.cancel()
  await waitForSearch()

  const trimmed = query.value.trim()

  if (isWholePolandLabel(trimmed)) {
    return { label: '', latitude: undefined, longitude: undefined }
  }

  if (selectedLatitude.value !== undefined && selectedLongitude.value !== undefined) {
    return getSelection()
  }

  const resolved = await resolveLocationLabel(trimmed)

  if (resolved === null) {
    return getSelection()
  }

  query.value = resolved.label
  selectedLatitude.value = resolved.lat
  selectedLongitude.value = resolved.lng

  return {
    label: resolved.label,
    latitude: resolved.lat,
    longitude: resolved.lng,
  }
}

function resetSuggestions(): void {
  suggestions.value = []
  searchError.value = null
  isSearching.value = false
  debouncedSearch.cancel()
}

function selectSuggestion(result: GeocodingResult): void {
  query.value = result.label
  resetSuggestions()
  emitChange(result.label, result.latitude, result.longitude)
}

function runSearch(value: string): void {
  void (async () => {
    isSearching.value = true
    searchError.value = null

    try {
      suggestions.value = await searchLocations(value)
      showSearchHint.value = value.length >= 3 && suggestions.value.length === 0

      if (props.compact && suggestions.value.length > 0) {
        updateFloatingPosition()
      }
    } catch {
      suggestions.value = []
      searchError.value = 'Nie udało się wyszukać lokalizacji.'
    } finally {
      isSearching.value = false
    }
  })()
}

const debouncedSearch = useDebouncedCallback(runSearch, 500)

function onFocus(): void {
  if (!isWholePolandLabel(query.value)) {
    return
  }

  query.value = ''
  selectedLatitude.value = undefined
  selectedLongitude.value = undefined
  resetSuggestions()
  emitChange('', undefined, undefined)
}

function onInput(value: string | undefined): void {
  const next = value ?? ''
  query.value = next
  showSearchHint.value = false
  selectedLatitude.value = undefined
  selectedLongitude.value = undefined

  if (isWholePolandLabel(next)) {
    resetSuggestions()
    emitChange('', undefined, undefined)
    return
  }

  if (next.trim().length >= 3) {
    debouncedSearch(next)
  } else {
    resetSuggestions()
  }
}

function onWindowChange(): void {
  if (props.compact && suggestions.value.length > 0) {
    updateFloatingPosition()
  }
}

onMounted(() => {
  window.addEventListener('resize', onWindowChange)
  window.addEventListener('scroll', onWindowChange, true)
})

onBeforeUnmount(() => {
  debouncedSearch.cancel()
  window.removeEventListener('resize', onWindowChange)
  window.removeEventListener('scroll', onWindowChange, true)
})

defineExpose({ getSelection, resolveSelection })
</script>

<template>
  <div
    ref="rootRef"
    class="location-search"
    :class="{ 'location-search--compact': compact }"
  >
    <i
      v-if="compact"
      class="pi pi-map-marker location-search__icon"
      aria-hidden="true"
    />

    <InputText
      ref="inputRef"
      :model-value="query"
      :placeholder="placeholder"
      :aria-label="ariaLabel"
      :fluid="fluid"
      :class="inputClass"
      name="listing-location-search"
      autocomplete="off"
      aria-autocomplete="list"
      :aria-controls="suggestions.length > 0 ? 'listing-location-suggestions' : undefined"
      @update:model-value="onInput"
      @focus="onFocus"
      @keyup.enter="emit('submit')"
    />

    <ul
      v-if="suggestions.length > 0 && !compact"
      id="listing-location-suggestions"
      class="location-search__suggestions"
    >
      <li
        v-for="(suggestion, index) in suggestions"
        :key="`${suggestion.latitude}-${suggestion.longitude}-${index}`"
      >
        <button
          type="button"
          class="location-search__suggestion"
          @click="selectSuggestion(suggestion)"
        >
          {{ suggestion.label }}
        </button>
      </li>
    </ul>

    <Teleport to="body">
      <ul
        v-if="suggestions.length > 0 && compact"
        id="listing-location-suggestions-compact"
        class="location-search__suggestions location-search__suggestions--floating"
        :style="floatingStyle"
      >
        <li
          v-for="(suggestion, index) in suggestions"
          :key="`${suggestion.latitude}-${suggestion.longitude}-${index}`"
        >
          <button
            type="button"
            class="location-search__suggestion"
            @click="selectSuggestion(suggestion)"
          >
            {{ suggestion.label }}
          </button>
        </li>
      </ul>
    </Teleport>

    <template v-if="!compact || showFeedback">
      <p
        v-if="isSearching"
        class="location-search__status"
      >
        Szukam…
      </p>

      <p
        v-else-if="searchError"
        class="location-search__status location-search__status--error"
      >
        {{ searchError }}
      </p>

      <p
        v-else-if="showSearchHint"
        class="location-search__hint"
      >
        Wpisz co najmniej 3 znaki i wybierz miejsce z listy. Wyniki pochodzą z OpenStreetMap.
      </p>
    </template>
  </div>
</template>

<style scoped>
.location-search {
  position: relative;
}

.location-search--compact {
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 0;
  width: 100%;
  position: relative;
}

.location-search__icon {
  position: absolute;
  left: 0.75rem;
  z-index: 1;
  color: var(--text-muted);
  pointer-events: none;
  line-height: 1;
}

.location-search--compact :deep(input) {
  min-width: 0;
  padding-left: 2.25rem;
  color: var(--text-strong);
  caret-color: var(--text-strong);
}

.location-search--compact :deep(input::placeholder) {
  color: var(--text-muted);
  opacity: 1;
}

.location-search__suggestions--floating {
  z-index: 2000;
}

.location-search__suggestions {
  position: absolute;
  top: calc(100% + 0.25rem);
  left: 0;
  right: 0;
  z-index: 50;
  margin: 0;
  padding: 0;
  list-style: none;
  border: 1px solid var(--surface-border);
  border-radius: 0.5rem;
  overflow: hidden;
  background: var(--surface-card);
  box-shadow: var(--shadow-card-hover);
  max-height: 14rem;
  overflow-y: auto;
}

.location-search__suggestion {
  display: block;
  width: 100%;
  padding: 0.65rem 0.75rem;
  border: 0;
  border-bottom: 1px solid var(--surface-border);
  background: transparent;
  text-align: left;
  font: inherit;
  font-size: 0.8125rem;
  cursor: pointer;
}

.location-search__suggestion:last-child {
  border-bottom: 0;
}

.location-search__suggestion:hover {
  background: var(--surface-muted);
}

.location-search__status,
.location-search__hint {
  margin: 0.35rem 0 0;
  font-size: 0.75rem;
  color: var(--text-muted);
}

.location-search__status--error {
  color: var(--p-red-500);
}
</style>