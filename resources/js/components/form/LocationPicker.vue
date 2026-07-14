<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import L from 'leaflet'
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'
import 'leaflet/dist/leaflet.css'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { useDebouncedCallback } from '@/composables/useDebouncedCallback'
import { searchLocations, type GeocodingResult } from '@/composables/useGeocoding'

const props = defineProps<{
  location: string
  latitude: number | null
  longitude: number | null
  invalid?: boolean
}>()

const emit = defineEmits<{
  change: [value: { location: string; latitude: number | null; longitude: number | null }]
}>()

delete (L.Icon.Default.prototype as { _getIconUrl?: unknown })._getIconUrl
L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl })

const DEFAULT_CENTER: L.LatLngExpression = [52.1, 19.4]
const DEFAULT_ZOOM = 6
const SELECTED_ZOOM = 14

/** Użytkownik kliknął „Zmień” przy już ustawionej lokalizacji. */
const isChanging = ref(false)
const searchQuery = ref('')
const suggestions = ref<GeocodingResult[]>([])
const searchError = ref<string | null>(null)
const isSearching = ref(false)

const mapElement = ref<HTMLElement | null>(null)
let map: L.Map | null = null
let marker: L.Marker | null = null

const hasSavedLocation = computed(
  () => props.location !== '' && props.latitude !== null && props.longitude !== null,
)

const showSaved = computed(() => hasSavedLocation.value && !isChanging.value)

const showEditor = computed(() => !hasSavedLocation.value || isChanging.value)

const showSearchHint = computed(
  () => suggestions.value.length === 0 && searchQuery.value.length < 3 && !isSearching.value,
)

function emitChange(location: string, latitude: number | null, longitude: number | null): void {
  emit('change', { location, latitude, longitude })
}

function destroyMap(): void {
  marker = null
  map?.remove()
  map = null
}

function syncMarker(latitude: number, longitude: number): void {
  if (map === null) {
    return
  }

  const position: L.LatLngExpression = [latitude, longitude]

  if (marker === null) {
    marker = L.marker(position, { title: '' }).addTo(map)
  } else {
    marker.setLatLng(position)
  }

  map.setView(position, SELECTED_ZOOM)
}

async function ensureMap(): Promise<void> {
  await nextTick()

  if (mapElement.value === null || map !== null) {
    return
  }

  map = L.map(mapElement.value, { scrollWheelZoom: false })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '',
    maxZoom: 19,
  }).addTo(map)

  if (props.latitude !== null && props.longitude !== null) {
    syncMarker(props.latitude, props.longitude)
  } else {
    map.setView(DEFAULT_CENTER, DEFAULT_ZOOM)
  }
}

function resetSearch(): void {
  searchQuery.value = ''
  suggestions.value = []
  searchError.value = null
  isSearching.value = false
  debouncedSearch.cancel()
}

function startChanging(): void {
  destroyMap()
  isChanging.value = true
  searchQuery.value = props.location
  suggestions.value = []
  searchError.value = null
}

function cancelChanging(): void {
  destroyMap()
  isChanging.value = false
  resetSearch()

  if (hasSavedLocation.value) {
    void nextTick().then(() => ensureMap())
  }
}

function clearLocation(): void {
  isChanging.value = false
  resetSearch()
  emitChange('', null, null)
  destroyMap()
}

function selectSuggestion(result: GeocodingResult): void {
  emitChange(result.label, result.latitude, result.longitude)
  isChanging.value = false
  resetSearch()
  void nextTick().then(() => ensureMap())
}

function runSearch(query: string): void {
  void (async () => {
    if (!showEditor.value) {
      return
    }

    isSearching.value = true
    searchError.value = null

    try {
      suggestions.value = await searchLocations(query)
    } catch {
      suggestions.value = []
      searchError.value = 'Nie udało się wyszukać lokalizacji. Spróbuj ponownie za chwilę.'
    } finally {
      isSearching.value = false
    }
  })()
}

const debouncedSearch = useDebouncedCallback(runSearch, 500)

function onSearchInput(value: string | undefined): void {
  const query = value ?? ''
  searchQuery.value = query
  debouncedSearch(query)
}

watch(
  () => [props.latitude, props.longitude, props.location, isChanging.value] as const,
  async ([latitude, longitude, location, changing]) => {
    if (changing || latitude === null || longitude === null || location === '') {
      return
    }

    destroyMap()
    await nextTick()
    await ensureMap()
    syncMarker(latitude, longitude)
  },
)

onMounted(() => {
  if (showSaved.value) {
    void ensureMap()
  }
})

onBeforeUnmount(() => {
  debouncedSearch.cancel()
  destroyMap()
})
</script>

<template>
  <div class="location-picker">
    <div
      v-if="showSaved"
      class="location-picker__saved"
    >
      <p class="location-picker__label">
        <i class="pi pi-map-marker" />
        {{ location }}
      </p>
      <div class="location-picker__actions">
        <Button
          type="button"
          label="Zmień lokalizację"
          severity="secondary"
          size="small"
          @click="startChanging"
        />
        <Button
          type="button"
          label="Usuń"
          severity="secondary"
          size="small"
          text
          @click="clearLocation"
        />
      </div>
    </div>

    <div
      v-if="showEditor"
      class="location-picker__editor"
    >
      <div class="field">
        <label for="location-search">Lokalizacja</label>
        <div class="location-picker__search">
          <InputText
            id="location-search"
            name="ad-location-search"
            :model-value="searchQuery"
            :invalid="invalid"
            placeholder="np. Warszawa, Mokotów"
            autocomplete="off"
            aria-autocomplete="list"
            aria-controls="location-suggestions"
            @update:model-value="onSearchInput"
          />

          <ul
            v-if="suggestions.length > 0"
            id="location-suggestions"
            class="location-picker__suggestions"
          >
            <li
              v-for="(suggestion, index) in suggestions"
              :key="`${suggestion.latitude}-${suggestion.longitude}-${index}`"
            >
              <button
                type="button"
                class="location-picker__suggestion"
                @click="selectSuggestion(suggestion)"
              >
                {{ suggestion.label }}
              </button>
            </li>
          </ul>
        </div>

        <small v-if="showSearchHint">
          Wpisz co najmniej 3 znaki. Wyniki pochodzą z OpenStreetMap.
        </small>
      </div>

      <p
        v-if="isSearching"
        class="location-picker__status"
      >
        Szukam…
      </p>

      <Message
        v-if="searchError"
        severity="error"
        size="small"
        variant="simple"
      >
        {{ searchError }}
      </Message>

      <div
        v-if="isChanging"
        class="location-picker__actions"
      >
        <Button
          type="button"
          label="Anuluj"
          severity="secondary"
          size="small"
          text
          @click="cancelChanging"
        />
      </div>
    </div>

    <div
      v-if="hasSavedLocation"
      ref="mapElement"
      class="location-picker__map"
      aria-hidden="true"
    />
  </div>
</template>

<style scoped>
.location-picker {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.location-picker__saved {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.location-picker__label {
  display: inline-flex;
  align-items: flex-start;
  gap: 0.4rem;
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.45;
}

.location-picker__map {
  width: 100%;
  height: 14rem;
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid var(--surface-border);
  background: var(--surface-muted);
}

/* Podpowiedź Leaflet/OSM pod mapą nie powinna nachodzić na listę wyników. */
.location-picker__map :deep(.leaflet-control-attribution) {
  display: none;
}

.location-picker__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.location-picker__editor {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.field label {
  font-size: 0.8125rem;
  font-weight: 500;
}

.field small {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.location-picker__status {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.location-picker__search {
  position: relative;
}

.location-picker__suggestions {
  position: absolute;
  top: calc(100% + 0.25rem);
  left: 0;
  right: 0;
  z-index: 30;
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

.location-picker__suggestion {
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

.location-picker__suggestion:last-child {
  border-bottom: 0;
}

.location-picker__suggestion:hover {
  background: var(--surface-muted);
}
</style>