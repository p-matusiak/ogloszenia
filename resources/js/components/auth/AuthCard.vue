<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <section class="auth">
    <div class="surface auth__card">
      <header class="auth__header">
        <h1 class="auth__title">
          {{ title }}
        </h1>
        <p
          v-if="subtitle"
          class="auth__subtitle"
        >
          {{ subtitle }}
        </p>
      </header>

      <slot />
    </div>

    <div
      v-if="$slots.footer"
      class="auth__footer"
    >
      <slot name="footer" />
    </div>
  </section>
</template>

<style scoped>
.auth {
  max-width: 26rem;
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  gap: var(--stack-gap);
}

.auth__card {
  padding: var(--card-padding);
}

.auth__header {
  margin-bottom: 1.25rem;
}

/* Nie globalne `h1`: tytuł stoi wewnątrz karty, więc trzyma się skali karty,
   a nie skali tytułu strony. */
.auth__title {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 700;
  line-height: 1.25;
}

.auth__subtitle {
  margin: 0.375rem 0 0;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.auth__footer {
  text-align: center;
  font-size: 0.875rem;
  color: var(--text-muted);
}

/* Pola formularza przychodzą slotem, więc ich metryki muszą stać tutaj.
   Te same wartości co w AdForm — inaczej etykiety w dwóch formularzach
   tej samej aplikacji mają różną wielkość. */
.auth__card :deep(.form) {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.auth__card :deep(.field) {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 0;
}

.auth__card :deep(.field label) {
  font-size: 0.8125rem;
  font-weight: 500;
}

.auth__card :deep(.field small) {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.auth__card :deep(.form__submit) {
  margin-top: 0.25rem;
}
</style>
