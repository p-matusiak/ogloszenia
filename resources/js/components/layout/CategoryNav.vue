<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'

import { useCategoryStore } from '@/stores/categories'

const { t } = useI18n()
const categories = useCategoryStore()
const { roots } = storeToRefs(categories)
</script>

<template>
  <nav
    class="catnav"
    :aria-label="t('nav.categories')"
  >
    <div class="shell catnav__inner">
      <RouterLink
        :to="{ name: 'listings' }"
        class="catnav__all"
      >
        <i class="pi pi-bars" />
        <span>{{ t('nav.allCategories') }}</span>
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
  overflow-x: clip;
  max-width: 100%;
}

.catnav__inner {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding-block: 0.75rem;
  min-width: 0;
  max-width: 100%;
  /* Przewijanie zostaje w pasku kategorii — nie rozszerza całej strony. */
  overflow-x: auto;
  overscroll-behavior-x: contain;
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
  color: var(--brand-blue);
  font-weight: 600;
}

.catnav__all:hover,
.catnav__link:hover {
  color: var(--brand-orange);
}

.catnav__link--active {
  color: var(--brand-blue);
  font-weight: 600;
  box-shadow: inset 0 -2px 0 var(--brand-orange);
}
</style>
