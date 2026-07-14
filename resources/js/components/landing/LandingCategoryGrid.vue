<script setup lang="ts">
import Button from 'primevue/button'
import { useI18n } from 'vue-i18n'

import type { LandingCategoryTile } from '@/composables/useLandingPage'

defineProps<{
  tiles: LandingCategoryTile[]
  isLoading: boolean
}>()

const { t } = useI18n()
</script>

<template>
  <section
    id="categories"
    class="categories"
  >
    <h2 class="categories__title">
      {{ t('landing.categories.title') }}
    </h2>

    <div class="categories__grid">
      <RouterLink
        v-for="tile in tiles"
        :key="tile.category.id"
        :to="{ name: 'categories.show', params: { slug: tile.category.slug } }"
        class="categories__card"
      >
        <span
          class="categories__icon"
          aria-hidden="true"
        >
          <i :class="tile.icon" />
        </span>

        <span class="categories__body">
          <span class="categories__name">{{ tile.category.name }}</span>
          <span class="categories__count">
            {{
              tile.count === null
                ? t('common.loading')
                : t('landing.categories.count', { count: tile.count })
            }}
          </span>
        </span>
      </RouterLink>

      <template v-if="isLoading && tiles.length === 0">
        <div
          v-for="n in 9"
          :key="n"
          class="categories__card categories__card--skeleton"
          aria-hidden="true"
        />
      </template>
    </div>

    <div class="categories__footer">
      <RouterLink :to="{ name: 'listings' }">
        <Button
          :label="t('landing.categories.viewAll')"
          outlined
        />
      </RouterLink>
    </div>
  </section>
</template>

<style scoped>
.categories {
  padding-block: 2.5rem 2rem;
}

.categories__title {
  margin: 0 0 1.5rem;
  font-size: 1.5rem;
  font-weight: 700;
}

.categories__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.875rem;
}

@media (width >= 36rem) {
  .categories__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (width >= 62rem) {
  .categories__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
  }
}

.categories__card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.15rem;
  border-radius: var(--card-radius);
  background: var(--surface-card);
  border: 1px solid var(--surface-border);
  text-decoration: none;
  color: inherit;
  transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.categories__card:hover {
  border-color: color-mix(in srgb, var(--brand-orange) 35%, var(--surface-border));
  box-shadow: var(--shadow-card-hover);
}

.categories__icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--brand-blue) 10%, var(--surface-muted));
  color: var(--brand-blue);
  font-size: 1.125rem;
}

.categories__body {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.categories__name {
  font-weight: 600;
  font-size: 0.9375rem;
  line-height: 1.25;
}

.categories__count {
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.categories__card--skeleton {
  min-height: 4.75rem;
  animation: pulse 1.4s ease-in-out infinite;
  background: var(--surface-muted);
}

.categories__footer {
  display: flex;
  justify-content: center;
  margin-top: 1.75rem;
}

.categories__footer :deep(.p-button) {
  min-width: 14rem;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.55;
  }
}
</style>