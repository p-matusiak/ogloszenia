<script setup lang="ts">
import Paginator from 'primevue/paginator'
import { storeToRefs } from 'pinia'
import { onMounted } from 'vue'

import AdCard from '@/components/ads/AdCard.vue'
import FavoriteButton from '@/components/ads/FavoriteButton.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useFavoritesStore } from '@/stores/favorites'

const store = useFavoritesStore()
const { favorites, meta, isLoading, error } = storeToRefs(store)

onMounted(() => void store.loadFavorites())

function changePage(event: { page: number }): void {
  void store.loadFavorites(event.page + 1)
}
</script>

<template>
  <section class="shell favorites">
    <h1 class="favorites__title">
      Ulubione ogłoszenia
    </h1>

    <p
      v-if="isLoading"
      class="favorites__status"
    >
      Wczytywanie…
    </p>
    <p
      v-else-if="error"
      class="favorites__status favorites__status--error"
    >
      {{ error }}
    </p>
    <EmptyState
      v-else-if="favorites.length === 0"
      icon="pi pi-heart"
      title="Nie obserwujesz jeszcze żadnego ogłoszenia"
      description="Dodaj ogłoszenie do ulubionych, aby dostać e-mail, gdy się zmieni."
    />

    <div
      v-else
      class="favorites__grid"
    >
      <div
        v-for="ad in favorites"
        :key="ad.id"
        class="favorites__item"
      >
        <AdCard :ad="ad" />
        <div class="favorites__action">
          <FavoriteButton
            :ad-id="ad.id"
            :ad-slug="ad.slug"
          />
        </div>
      </div>
    </div>

    <Paginator
      v-if="meta && meta.last_page > 1"
      :rows="meta.per_page"
      :total-records="meta.total"
      :first="(meta.current_page - 1) * meta.per_page"
      class="favorites__pager"
      @page="changePage"
    />
  </section>
</template>

<style scoped>
.favorites {
  padding-block: 1.5rem;
}

.favorites__title {
  margin: 0 0 1.25rem;
  font-size: 1.5rem;
  font-weight: 700;
}

.favorites__status {
  color: var(--text-muted);
}

.favorites__status--error {
  color: var(--p-red-500);
}

.favorites__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr));
  gap: 1rem;
}

.favorites__item {
  position: relative;
}

.favorites__action {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
}

.favorites__pager {
  margin-top: 1.5rem;
}
</style>
