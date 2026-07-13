<script setup lang="ts">
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { fetchAd, updateAd } from '@/api/modules/v1/ads'
import AdForm from '@/components/AdForm.vue'
import { adToForm, emptyAdForm, useAdSubmission } from '@/composables/useAdSubmission'
import type { AdImage } from '@/types/api'

const props = defineProps<{ slug: string }>()

const router = useRouter()
const values = ref(emptyAdForm())
const existingImages = ref<AdImage[]>([])
const isLoading = ref(true)
const loadError = ref<string | null>(null)

const { errors, generalError, isSubmitting, submit } = useAdSubmission()

async function onSubmit(): Promise<void> {
  const ad = await submit(() => updateAd(props.slug, values.value))

  if (ad !== null) {
    await router.push({ name: 'ads.mine' })
  }
}

onMounted(async () => {
  try {
    const ad = await fetchAd(props.slug)
    values.value = adToForm(ad)
    existingImages.value = ad.images ?? []
  } catch {
    loadError.value = 'Nie udało się wczytać ogłoszenia.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section>
    <h1>Edytuj ogłoszenie</h1>

    <ProgressSpinner
      v-if="isLoading"
      aria-label="Ładowanie"
    />
    <Message
      v-else-if="loadError"
      severity="error"
    >
      {{ loadError }}
    </Message>

    <template v-else>
      <Message
        v-if="generalError"
        severity="error"
        class="general-error"
      >
        {{ generalError }}
      </Message>

      <AdForm
        v-model="values"
        :existing-images="existingImages"
        :errors="errors"
        :is-submitting="isSubmitting"
        submit-label="Zapisz zmiany"
        @submit="onSubmit"
      />
    </template>
  </section>
</template>

<style scoped>
.general-error {
  margin-bottom: 1rem;
}
</style>
