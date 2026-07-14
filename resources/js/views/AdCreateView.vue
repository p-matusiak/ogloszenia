<script setup lang="ts">
import Message from 'primevue/message'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { createAd } from '@/api/modules/v1/ads'
import AdForm from '@/components/AdForm.vue'
import { emptyAdForm, prefillLocationFromUser, useAdSubmission } from '@/composables/useAdSubmission'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const values = ref(prefillLocationFromUser(emptyAdForm(), auth.user))
const { errors, generalError, isSubmitting, submit } = useAdSubmission()

async function onSubmit(): Promise<void> {
  const ad = await submit(() => createAd(values.value))

  if (ad !== null) {
    // A pending ad has no public page yet, so send its author to their panel.
    await router.push(
      ad.status === 'active'
        ? { name: 'ads.show', params: { slug: ad.slug } }
        : { name: 'ads.mine' },
    )
  }
}
</script>

<template>
  <section>
    <header class="page-head">
      <h1 class="page-head__title">
        Dodaj ogłoszenie
      </h1>
      <p class="page-head__hint">
        Pola oznaczone <span class="req">*</span> są wymagane.
      </p>
    </header>

    <Message
      v-if="generalError"
      severity="error"
      class="general-error"
    >
      {{ generalError }}
    </Message>

    <AdForm
      v-model="values"
      :errors="errors"
      :is-submitting="isSubmitting"
      submit-label="Opublikuj ogłoszenie"
      @submit="onSubmit"
    />
  </section>
</template>

<style scoped>
.page-head {
  margin-bottom: var(--stack-gap);
}

.page-head__title {
  margin: 0;
  font-size: 1.65rem;
  font-weight: 700;
}

.page-head__hint {
  margin: 0.25rem 0 0;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.req {
  color: var(--p-red-500, #ef4444);
}

.general-error {
  margin-bottom: 1rem;
}
</style>
