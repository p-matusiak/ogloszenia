<script setup lang="ts">
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useAppLocale } from '@/composables/useAppLocale'

const { t } = useI18n()
const { locale, options, setLocale } = useAppLocale()

const menu = ref<InstanceType<typeof Menu> | null>(null)

const menuItems = computed(() =>
  options.map((option) => ({
    label: option.label,
    icon: locale.value === option.value ? 'pi pi-check' : undefined,
    command: () => setLocale(option.value),
  })),
)

function toggleMenu(event: MouseEvent): void {
  menu.value?.toggle(event)
}
</script>

<template>
  <Button
    icon="pi pi-globe"
    :aria-label="t('nav.language')"
    severity="secondary"
    text
    rounded
    class="language-switcher"
    @click="toggleMenu"
  />
  <Menu
    ref="menu"
    :model="menuItems"
    :popup="true"
    class="language-switcher__menu"
  />
</template>

<style scoped>
.language-switcher :deep(.p-button) {
  width: 2.25rem;
  height: 2.25rem;
  padding: 0;
}

.language-switcher :deep(.p-button-icon) {
  font-size: 1rem;
}
</style>