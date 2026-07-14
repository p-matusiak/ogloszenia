<script setup lang="ts">
import Button from 'primevue/button'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <section class="not-found">
    <h1>{{ t('errors.notFoundTitle') }}</h1>
    <p>{{ t('errors.notFoundDescription') }}</p>
    <RouterLink :to="{ name: 'landing' }">
      <Button
        :label="t('errors.notFoundBack')"
        icon="pi pi-home"
      />
    </RouterLink>
  </section>
</template>

<style scoped>
.not-found {
  text-align: center;
  padding: 3rem 1rem;
}
</style>
