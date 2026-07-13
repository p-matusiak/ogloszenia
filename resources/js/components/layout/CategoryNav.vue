<script setup lang="ts">
import { storeToRefs } from 'pinia'

import { useCategoryStore } from '@/stores/categories'

const categories = useCategoryStore()
const { roots } = storeToRefs(categories)
</script>

<template>
  <nav
    class="catnav"
    aria-label="Kategorie"
  >
    <div class="shell catnav__inner">
      <RouterLink
        :to="{ name: 'home' }"
        class="catnav__all"
      >
        <i class="pi pi-bars" />
        <span>Wszystkie kategorie</span>
      </RouterLink>

      <RouterLink
        v-for="root in roots"
        :key="root.id"
        :to="{ name: 'categories.show', params: { slug: root.slug } }"
        class="catnav__link"
        active-class="catnav__link--active"
      >
        {{ root.name }}
      </RouterLink>
    </div>
  </nav>
</template>

<style scoped>
.catnav {
  background: var(--surface-card);
  border-bottom: 1px solid var(--surface-border);
}

.catnav__inner {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding-block: 0.75rem;
  /* Na telefonie kategorie przewijają się w poziomie zamiast zawijać. */
  overflow-x: auto;
  scrollbar-width: none;
}

.catnav__inner::-webkit-scrollbar {
  display: none;
}

.catnav__all,
.catnav__link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
  text-decoration: none;
  font-size: 0.875rem;
  color: var(--text-strong);
}

.catnav__all {
  color: var(--p-primary-color);
  font-weight: 600;
}

.catnav__link:hover {
  color: var(--p-primary-color);
}
</style>
