<script setup lang="ts">
import Message from 'primevue/message'
import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'

import { errorMessage } from '@/api/client'
import { fetchSettings, updateSettings } from '@/api/modules/v1/admin'

const toast = useToast()

const autoApprove = ref(false)
const isLoading = ref(true)

async function onToggle(value: boolean): Promise<void> {
  try {
    const settings = await updateSettings(value)
    autoApprove.value = settings.auto_approve_ads
    toast.add({ severity: 'success', summary: 'Ustawienia zapisane', life: 3000 })
  } catch (caught: unknown) {
    // Snap back: the server rejected the change, so the UI must not pretend.
    autoApprove.value = !value
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  }
}

onMounted(async () => {
  try {
    autoApprove.value = (await fetchSettings()).auto_approve_ads
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="settings">
    <div class="setting">
      <ToggleSwitch
        input-id="auto_approve"
        :model-value="autoApprove"
        :disabled="isLoading"
        @update:model-value="onToggle"
      />
      <label for="auto_approve">Automatyczna akceptacja ogłoszeń</label>
    </div>

    <Message
      severity="info"
      variant="simple"
      size="small"
    >
      Gdy wyłączone, nowe ogłoszenia trafiają do statusu „oczekujące” i wymagają akceptacji.
    </Message>
  </div>
</template>

<style scoped>
.settings {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding-top: 0.5rem;
}

.setting {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
</style>
