<script setup lang="ts">
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import LocationSearchField, {
  type LocationSelection,
} from '@/components/search/LocationSearchField.vue'
import { buildLocationFilters } from '@/composables/useListingGeoFilters'
import { pruneFilters } from '@/composables/useRouteFilters'
import { useCategoryStore } from '@/stores/categories'

const { t } = useI18n()
const router = useRouter()
const categories = useCategoryStore()

const query = ref('')
const selectedCategorySlug = ref<string | null>(null)
const locationField = ref<{
  getSelection: () => LocationSelection
  resolveSelection: () => Promise<LocationSelection>
} | null>(null)
const isSubmitting = ref(false)

const categoryOptions = computed(() => [
  { label: t('landing.hero.allCategories'), value: null as string | null },
  ...categories.roots.map((category) => ({
    label: category.name,
    value: category.slug,
  })),
])

const popularLinks = computed(() => [
  { label: t('landing.popular.cars'), to: { name: 'categories.show', params: { slug: 'samochody' } } },
  { label: t('landing.popular.flats'), to: { name: 'categories.show', params: { slug: 'mieszkania' } } },
  { label: t('landing.popular.phones'), to: { name: 'categories.show', params: { slug: 'telefony' } } },
  { label: t('landing.popular.bikes'), to: { name: 'categories.show', params: { slug: 'rowery' } } },
  { label: t('landing.popular.laptops'), to: { name: 'categories.show', params: { slug: 'laptopy' } } },
  { label: t('landing.popular.jobs'), to: { name: 'categories.show', params: { slug: 'oferty-pracy' } } },
  { label: t('landing.popular.services'), to: { name: 'categories.show', params: { slug: 'budowlane' } } },
])

async function submitSearch(): Promise<void> {
  if (isSubmitting.value) {
    return
  }

  isSubmitting.value = true

  try {
    const selection = locationField.value
      ? await locationField.value.resolveSelection()
      : { label: '', latitude: undefined, longitude: undefined }

    const locationPatch = await buildLocationFilters(
      selection.label || undefined,
      selection.latitude,
      selection.longitude,
    )

    const filters = pruneFilters({
      q: query.value.trim() || undefined,
      ...locationPatch,
      page: 1,
    })

    const category = selectedCategorySlug.value ?? undefined

    if (category !== undefined && category !== '') {
      await router
        .push({ name: 'categories.show', params: { slug: category }, query: filters })
        .catch(() => undefined)

      return
    }

    await router.push({ name: 'listings', query: filters }).catch(() => undefined)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="hero">
    <div
      class="hero__bg"
      aria-hidden="true"
    />

    <div class="shell hero__inner">
      <h1 class="hero__title">
        {{ t('landing.hero.title') }}
      </h1>
      <p class="hero__subtitle">
        {{ t('landing.hero.subtitle') }}
      </p>

      <form
        class="hero__search"
        role="search"
        @submit.prevent="submitSearch"
      >
        <IconField class="hero__field hero__field--query">
          <InputIcon class="pi pi-search" />
          <InputText
            v-model="query"
            :placeholder="t('landing.hero.queryPlaceholder')"
            :aria-label="t('landing.hero.queryPlaceholder')"
          />
        </IconField>

        <Select
          v-model="selectedCategorySlug"
          :options="categoryOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('landing.hero.categoryPlaceholder')"
          :aria-label="t('landing.hero.categoryPlaceholder')"
          class="hero__field hero__field--category"
        />

        <div class="hero__field hero__field--location">
          <LocationSearchField
            ref="locationField"
            compact
            fluid
            show-feedback
            :label="t('landing.hero.locationPlaceholder')"
            :placeholder="t('landing.hero.locationPlaceholder')"
            :aria-label="t('filters.location')"
            @submit="submitSearch"
          />
        </div>

        <Button
          type="submit"
          :label="t('landing.hero.submit')"
          icon="pi pi-search"
          class="hero__submit"
          :loading="isSubmitting"
        />
      </form>

      <div class="hero__popular">
        <span class="hero__popular-label">{{ t('landing.hero.popularLabel') }}</span>
        <div class="hero__popular-links">
          <RouterLink
            v-for="link in popularLinks"
            :key="link.label"
            :to="link.to"
            class="hero__popular-link"
          >
            {{ link.label }}
          </RouterLink>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero {
  position: relative;
  color: #fff;
  overflow: visible;
}

.hero__bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    linear-gradient(180deg, rgb(30 58 95 / 72%) 0%, rgb(30 58 95 / 55%) 100%),
    url('/images/landing-hero.jpg') center / cover no-repeat;
}

.hero__inner {
  position: relative;
  z-index: 1;
  padding-block: 3.5rem 3rem;
  text-align: center;
}

.hero__title {
  margin: 0 0 0.75rem;
  font-size: clamp(1.75rem, 4vw, 2.5rem);
  font-weight: 700;
  line-height: 1.15;
  color: #fff;
}

.hero__subtitle {
  margin: 0 auto 2rem;
  max-width: 40rem;
  font-size: 1.0625rem;
  line-height: 1.5;
  color: rgb(255 255 255 / 92%);
}

.hero__search {
  position: relative;
  z-index: 5;
  display: grid;
  gap: 0.75rem;
  max-width: 56rem;
  margin: 0 auto;
  padding: 1rem;
  border-radius: 0.5rem;
  background: rgb(255 255 255 / 96%);
  box-shadow: 0 12px 40px rgb(0 0 0 / 18%);
  overflow: visible;
  color: var(--text-strong);
}

.hero__field :deep(.p-inputtext),
.hero__field :deep(.p-select) {
  width: 100%;
}

.hero__field--location {
  position: relative;
  min-width: 0;
}

.hero__field--location :deep(.location-search--compact) {
  min-height: 2.5rem;
  padding-inline: 0.65rem 0.5rem;
  border: 1px solid var(--input-border);
  border-radius: var(--input-radius);
  background: var(--surface-card);
}

.hero__field--location :deep(.location-search--compact input) {
  width: 100%;
  min-height: 2.35rem;
  border: 0;
  background: transparent;
  box-shadow: none;
  color: var(--text-strong);
  caret-color: var(--text-strong);
}

.hero__field--location :deep(.location-search--compact input::placeholder) {
  color: var(--text-muted);
  opacity: 1;
}

.hero__field--location :deep(.location-search__hint),
.hero__field--location :deep(.location-search__status) {
  position: absolute;
  top: calc(100% + 0.2rem);
  left: 0;
  right: 0;
  margin: 0;
  text-align: left;
}

.hero__submit :deep(.p-button) {
  width: 100%;
  justify-content: center;
  background: var(--brand-orange);
  border-color: var(--brand-orange);
}

.hero__submit :deep(.p-button:enabled:hover) {
  background: var(--brand-orange-hover);
  border-color: var(--brand-orange-hover);
}

@media (width >= 48rem) {
  .hero__search {
    grid-template-columns: 1.35fr 0.95fr 1.05fr auto;
    align-items: stretch;
  }

  .hero__submit :deep(.p-button) {
    min-width: 8.5rem;
    width: auto;
    height: 100%;
  }
}

.hero__popular {
  margin-top: 1.5rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 0.5rem 0.75rem;
}

.hero__popular-label {
  font-size: 0.875rem;
  color: rgb(255 255 255 / 88%);
}

.hero__popular-links {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.5rem;
}

.hero__popular-link {
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
  background: rgb(255 255 255 / 16%);
  color: #fff;
  text-decoration: none;
  font-size: 0.8125rem;
  border: 1px solid rgb(255 255 255 / 24%);
}

.hero__popular-link:hover {
  background: rgb(255 255 255 / 26%);
}
</style>