<script setup lang="ts">
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { errorMessage } from '@/api/client'
import { deleteAd, fetchMyAds, refreshAd } from '@/api/modules/v1/ads'
import { formatDate, formatPrice } from '@/composables/useFormatting'
import type { AdStatus, AdSummary } from '@/types/api'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const ads = ref<AdSummary[]>([])
const isLoading = ref(true)
const loadError = ref<string | null>(null)

const statusLabels: Record<AdStatus, string> = {
  active: 'Aktywne',
  pending: 'Oczekujące',
  rejected: 'Odrzucone',
  expired: 'Wygasłe',
  deleted: 'Usunięte',
}

const statusSeverity: Record<AdStatus, string> = {
  active: 'success',
  pending: 'warn',
  rejected: 'danger',
  expired: 'secondary',
  deleted: 'contrast',
}

/** Only lapsed ads can be refreshed; the server is the final authority. */
function canRefresh(ad: AdSummary): boolean {
  if (ad.status !== 'active' && ad.status !== 'expired') {
    return false
  }

  return ad.expires_at !== null && new Date(ad.expires_at).getTime() <= Date.now()
}

async function load(): Promise<void> {
  isLoading.value = true
  try {
    ads.value = (await fetchMyAds()).data
  } catch (caught: unknown) {
    loadError.value = errorMessage(caught, 'Nie udało się pobrać Twoich ogłoszeń.')
  } finally {
    isLoading.value = false
  }
}

async function onRefresh(ad: AdSummary): Promise<void> {
  try {
    await refreshAd(ad.slug)
    toast.add({ severity: 'success', summary: 'Ogłoszenie odświeżone', life: 4000 })
    await load()
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  }
}

async function confirmedDelete(ad: AdSummary): Promise<void> {
  try {
    await deleteAd(ad.slug)
    toast.add({ severity: 'success', summary: 'Ogłoszenie usunięte', life: 4000 })
    await load()
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  }
}

function onDelete(ad: AdSummary): void {
  confirm.require({
    message: `Usunąć ogłoszenie „${ad.title}”?`,
    header: 'Potwierdzenie',
    acceptLabel: 'Usuń',
    rejectLabel: 'Anuluj',
    // `accept` expects a void return; confirmedDelete handles its own errors,
    // so discarding the promise here cannot swallow a rejection.
    accept: () => void confirmedDelete(ad),
  })
}

onMounted(load)
</script>

<template>
  <section>
    <h1>Moje ogłoszenia</h1>

    <Message
      v-if="loadError"
      severity="error"
    >
      {{ loadError }}
    </Message>

    <DataTable
      v-else
      :value="ads"
      :loading="isLoading"
      data-key="id"
      responsive-layout="scroll"
    >
      <template #empty>
        Nie masz jeszcze żadnych ogłoszeń.
      </template>

      <Column
        field="title"
        header="Tytuł"
      />

      <Column header="Status">
        <template #body="{ data }">
          <Tag
            :value="statusLabels[data.status as AdStatus]"
            :severity="statusSeverity[data.status as AdStatus]"
          />
        </template>
      </Column>

      <Column header="Cena">
        <template #body="{ data }">
          {{ formatPrice(data.price) }}
        </template>
      </Column>

      <Column header="Dodano">
        <template #body="{ data }">
          {{ formatDate(data.published_at) }}
        </template>
      </Column>

      <Column header="Wygasa">
        <template #body="{ data }">
          {{ formatDate(data.expires_at) }}
        </template>
      </Column>

      <Column header="Akcje">
        <template #body="{ data }">
          <div class="actions">
            <Button
              icon="pi pi-pencil"
              text
              size="small"
              aria-label="Edytuj"
              @click="router.push({ name: 'ads.edit', params: { slug: data.slug } })"
            />
            <Button
              icon="pi pi-refresh"
              text
              size="small"
              aria-label="Odśwież"
              :disabled="!canRefresh(data)"
              @click="onRefresh(data)"
            />
            <Button
              icon="pi pi-trash"
              text
              size="small"
              severity="danger"
              aria-label="Usuń"
              @click="onDelete(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>
  </section>
</template>

<style scoped>
.actions {
  display: flex;
  gap: 0.25rem;
}
</style>
