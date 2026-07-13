<script setup lang="ts">
withDefaults(
  defineProps<{
    icon?: string
    title: string
    description?: string
  }>(),
  { icon: 'pi pi-inbox', description: '' },
)
</script>

<template>
  <div class="empty">
    <i
      :class="icon"
      class="empty__icon"
      aria-hidden="true"
    />
    <p class="empty__title">
      {{ title }}
    </p>
    <p
      v-if="description"
      class="empty__description"
    >
      {{ description }}
    </p>
    <div class="empty__actions">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 3.5rem var(--card-padding);
  text-align: center;
  background: var(--surface-card);
  border: 1px dashed var(--surface-border);
  border-radius: var(--card-radius);
}

.empty__icon {
  font-size: 2.25rem;
  color: var(--text-muted);
  opacity: 0.5;
}

.empty__title {
  margin: 0;
  font-size: var(--title-card);
  font-weight: 600;
}

.empty__description {
  margin: 0;
  max-width: 32rem;
  color: var(--text-muted);
  font-size: 0.875rem;
}

.empty__actions:empty {
  display: none;
}

.empty__actions {
  margin-top: 0.75rem;
}
</style>
