<script setup lang="ts">
import Button from 'primevue/button'
import Select from 'primevue/select'
import { useToast } from 'primevue/usetoast'
import { ref } from 'vue'

import { errorMessage } from '@/api/client'
import { reportAd } from '@/api/modules/v1/ads'
import type { ReportReason } from '@/types/api'

const props = defineProps<{ slug: string }>()
const emit = defineEmits<{ sent: [] }>()

const toast = useToast()
const reason = ref<ReportReason | null>(null)
const isSending = ref(false)

const reasons: { label: string; value: ReportReason }[] = [
  { label: 'Spam', value: 'spam' },
  { label: 'Oszustwo', value: 'scam' },
  { label: 'Treści obraźliwe', value: 'offensive' },
  { label: 'Zła kategoria', value: 'wrong_category' },
  { label: 'Inne', value: 'other' },
]

async function send(): Promise<void> {
  if (reason.value === null) {
    return
  }

  isSending.value = true
  try {
    await reportAd(props.slug, reason.value)
    toast.add({ severity: 'success', summary: 'Zgłoszenie wysłane', life: 4000 })
    reason.value = null
    emit('sent')
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  } finally {
    isSending.value = false
  }
}
</script>

<template>
  <div class="report">
    <p class="report__hint">
      Powiedz nam, co jest nie tak z tym ogłoszeniem. Zgłoszenie trafi do moderatora.
    </p>

    <Select
      v-model="reason"
      :options="reasons"
      option-label="label"
      option-value="value"
      placeholder="Powód zgłoszenia"
      aria-label="Powód zgłoszenia"
      fluid
    />

    <Button
      label="Wyślij zgłoszenie"
      severity="danger"
      fluid
      :disabled="reason === null"
      :loading="isSending"
      @click="send"
    />
  </div>
</template>

<style scoped>
.report {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.report__hint {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-muted);
}
</style>
