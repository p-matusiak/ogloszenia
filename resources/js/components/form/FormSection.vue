<script setup lang="ts">
defineProps<{ step: number; title: string }>()
</script>

<template>
  <section class="surface section">
    <header class="section__header">
      <span
        class="section__step"
        aria-hidden="true"
      >{{ step }}</span>
      <h2 class="section__title">
        {{ title }}
      </h2>
    </header>

    <slot />
  </section>
</template>

<style scoped>
.section {
  padding: var(--card-padding);
}

.section__header {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  margin-bottom: 1.125rem;
}

/* Numer kroku jest prawdziwą kolejnością wypełniania, nie ozdobą. */
.section__step {
  display: grid;
  place-items: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  background: var(--p-primary-color);
  color: #fff;
  font-size: 0.8125rem;
  font-weight: 700;
}

.section__title {
  margin: 0;
  font-size: var(--title-card);
  font-weight: 700;
}
</style>
