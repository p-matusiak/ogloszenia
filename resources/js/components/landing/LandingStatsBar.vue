<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  totalAds: number | null
}>()

const { t } = useI18n()

const stats = computed(() => [
  {
    icon: 'pi pi-megaphone',
    value: props.totalAds === null ? '…' : `${props.totalAds.toLocaleString('pl-PL')}+`,
    label: t('landing.stats.activeAds'),
  },
  {
    icon: 'pi pi-users',
    value: '50+',
    label: t('landing.stats.sellers'),
  },
  {
    icon: 'pi pi-th-large',
    value: '55+',
    label: t('landing.stats.categories'),
  },
  {
    icon: 'pi pi-shield',
    value: '100%',
    label: t('landing.stats.safe'),
  },
])
</script>

<template>
  <section
    class="stats"
    aria-label="Statystyki serwisu"
  >
    <div class="shell stats__inner">
      <div
        v-for="item in stats"
        :key="item.label"
        class="stats__item"
      >
        <i
          :class="item.icon"
          class="stats__icon"
          aria-hidden="true"
        />
        <span class="stats__value">{{ item.value }}</span>
        <span class="stats__label">{{ item.label }}</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.stats {
  background: color-mix(in srgb, var(--brand-blue) 8%, var(--surface-card));
  border-block: 1px solid var(--surface-border);
}

.stats__inner {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.25rem;
  padding-block: 2rem;
}

@media (width >= 48rem) {
  .stats__inner {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.stats__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.35rem;
}

.stats__icon {
  font-size: 1.5rem;
  color: var(--brand-blue);
}

.stats__value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-strong);
}

.stats__label {
  font-size: 0.875rem;
  color: var(--text-muted);
}
</style>