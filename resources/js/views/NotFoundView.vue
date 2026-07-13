<script setup lang="ts">
import Button from 'primevue/button'
</script>

<template>
  <section class="not-found">
    <h1>404 — nie znaleziono strony</h1>
    <p>Strona, której szukasz, nie istnieje lub została usunięta.</p>
    <RouterLink :to="{ name: 'home' }">
      <Button
        label="Wróć do ogłoszeń"
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
