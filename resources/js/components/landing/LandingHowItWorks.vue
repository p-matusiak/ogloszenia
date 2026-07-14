<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const steps = computed(() => [
  {
    number: 1,
    title: t('landing.howItWorks.step1Title'),
    text: t('landing.howItWorks.step1Text'),
  },
  {
    number: 2,
    title: t('landing.howItWorks.step2Title'),
    text: t('landing.howItWorks.step2Text'),
  },
  {
    number: 3,
    title: t('landing.howItWorks.step3Title'),
    text: t('landing.howItWorks.step3Text'),
  },
])

const testimonials = computed(() => [
  {
    name: t('landing.testimonials.anna.name'),
    quote: t('landing.testimonials.anna.quote'),
  },
  {
    name: t('landing.testimonials.marek.name'),
    quote: t('landing.testimonials.marek.quote'),
  },
  {
    name: t('landing.testimonials.kasia.name'),
    quote: t('landing.testimonials.kasia.quote'),
  },
])
</script>

<template>
  <section class="how">
    <h2 class="how__title">
      {{ t('landing.howItWorks.title') }}
    </h2>

    <div class="how__steps">
      <article
        v-for="step in steps"
        :key="step.number"
        class="how__step"
      >
        <span class="how__step-number">{{ step.number }}</span>
        <h3 class="how__step-title">
          {{ step.title }}
        </h3>
        <p class="how__step-text">
          {{ step.text }}
        </p>
      </article>
    </div>

    <h2 class="how__title how__title--secondary">
      {{ t('landing.testimonials.title') }}
    </h2>

    <div class="how__testimonials">
      <article
        v-for="item in testimonials"
        :key="item.name"
        class="how__quote"
      >
        <div
          class="how__stars"
          aria-hidden="true"
        >
          <i
            v-for="n in 5"
            :key="n"
            class="pi pi-star-fill"
          />
        </div>
        <p class="how__quote-text">
          „{{ item.quote }}"
        </p>
        <p class="how__quote-author">
          {{ item.name }}
        </p>
      </article>
    </div>
  </section>
</template>

<style scoped>
.how {
  padding-block: 2.5rem 3rem;
}

.how__title {
  margin: 0 0 1.5rem;
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
}

.how__title--secondary {
  margin-top: 3rem;
}

.how__steps {
  display: grid;
  gap: 1.25rem;
}

@media (width >= 48rem) {
  .how__steps {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.how__step {
  padding: 1.5rem;
  border-radius: var(--card-radius);
  background: var(--surface-card);
  border: 1px solid var(--surface-border);
  text-align: center;
}

.how__step-number {
  display: inline-grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  margin-bottom: 0.75rem;
  border-radius: 999px;
  background: var(--brand-orange);
  color: #fff;
  font-weight: 700;
}

.how__step-title {
  margin: 0 0 0.5rem;
  font-size: 1.0625rem;
}

.how__step-text {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-muted);
  line-height: 1.5;
}

.how__testimonials {
  display: grid;
  gap: 1rem;
}

@media (width >= 48rem) {
  .how__testimonials {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.how__quote {
  padding: 1.25rem;
  border-radius: var(--card-radius);
  background: var(--surface-card);
  border: 1px solid var(--surface-border);
}

.how__stars {
  display: flex;
  gap: 0.15rem;
  margin-bottom: 0.75rem;
  color: var(--brand-orange);
  font-size: 0.875rem;
}

.how__quote-text {
  margin: 0 0 0.75rem;
  font-size: 0.9375rem;
  line-height: 1.55;
}

.how__quote-author {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--text-muted);
}
</style>