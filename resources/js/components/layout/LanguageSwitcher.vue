<script setup lang="ts">
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useAppLocale } from '@/composables/useAppLocale'

const { t } = useI18n()
const { locale, options, setLocale } = useAppLocale()
const props = withDefaults(
  defineProps<{
    compact?: boolean
  }>(),
  { compact: true },
)

const menu = ref<InstanceType<typeof Menu> | null>(null)

const currentLocaleLabel = computed(
  () => options.find((option) => option.value === locale.value)?.label ?? t('nav.language'),
)

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
    :icon="props.compact ? 'pi pi-globe' : undefined"
    :label="props.compact ? undefined : currentLocaleLabel"
    :aria-label="t('nav.language')"
    severity="secondary"
    text
    :rounded="props.compact"
    :class="props.compact ? 'language-switcher' : 'language-switcher language-switcher--expanded'"
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

.language-switcher--expanded :deep(.p-button) {
  width: auto;
  height: auto;
  padding: 0.625rem 0.875rem;
  border-radius: 0.75rem;
}
</style>
