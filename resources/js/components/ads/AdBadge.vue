<script setup lang="ts">
export type BadgeTone = 'info' | 'success' | 'neutral'

withDefaults(defineProps<{ label: string; tone?: BadgeTone }>(), { tone: 'neutral' })
</script>

<template>
  <span
    class="badge"
    :class="`badge--${tone}`"
  >{{ label }}</span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.55rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.4;
  white-space: nowrap;
}

/* „Do negocjacji” — delikatny pomarańcz marki, bez obramowania. */
.badge--info {
  background: color-mix(in srgb, var(--brand-orange) 11%, var(--surface-muted));
  color: color-mix(in srgb, var(--brand-orange) 82%, var(--text-strong));
  font-weight: 600;
}

/* „Paczkomat, Kurier…” — obrysowana, zielona, jak w makiecie. */
.badge--success {
  background: color-mix(in srgb, var(--tone-success) 8%, transparent);
  border: 1px solid color-mix(in srgb, var(--tone-success) 35%, transparent);
  color: var(--tone-success-text);
}

.badge--neutral {
  background: var(--surface-muted);
  color: var(--text-muted);
}
</style>
