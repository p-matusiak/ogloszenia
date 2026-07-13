<script setup lang="ts">
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'

import { errorMessage } from '@/api/client'
import { approveAd, deleteAdAsAdmin, fetchAdminAds, rejectAd } from '@/api/modules/v1/admin'
import type { AdStatus, AdSummary } from '@/types/api'

const toast = useToast()

const ads = ref<AdSummary[]>([])
const isLoading = ref(false)
const status = ref<AdStatus | null>('pending')
const rejectionReason = ref('')
const rejectingSlug = ref<string | null>(null)

const statuses: { label: string; value: AdStatus }[] = [
  { label: 'Oczekujące', value: 'pending' },
  { label: 'Aktywne', value: 'active' },
  { label: 'Odrzucone', value: 'rejected' },
  { label: 'Wygasłe', value: 'expired' },
  { label: 'Usunięte', value: 'deleted' },
]

async function load(): Promise<void> {
  isLoading.value = true
  try {
    ads.value = (await fetchAdminAds(status.value ?? undefined)).data
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  } finally {
    isLoading.value = false
  }
}

async function onApprove(ad: AdSummary): Promise<void> {
  try {
    await approveAd(ad.slug)
    toast.add({ severity: 'success', summary: 'Ogłoszenie zaakceptowane', life: 4000 })
    await load()
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  }
}

async function onReject(ad: AdSummary): Promise<void> {
  if (rejectionReason.value.trim().length < 5) {
    toast.add({ severity: 'warn', summary: 'Podaj powód odrzucenia (min. 5 znaków).', life: 4000 })
    return
  }

  try {
    await rejectAd(ad.slug, rejectionReason.value.trim())
    toast.add({ severity: 'success', summary: 'Ogłoszenie odrzucone', life: 4000 })
    rejectingSlug.value = null
    rejectionReason.value = ''
    await load()
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  }
}

async function onDelete(ad: AdSummary): Promise<void> {
  try {
    await deleteAdAsAdmin(ad.slug)
    toast.add({ severity: 'success', summary: 'Ogłoszenie usunięte', life: 4000 })
    await load()
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="filter">
      <Select
        v-model="status"
        :options="statuses"
        option-label="label"
        option-value="value"
        placeholder="Status"
        show-clear
      />
      <Button
        label="Filtruj"
        icon="pi pi-filter"
        size="small"
        @click="load"
      />
    </div>

    <DataTable
      :value="ads"
      :loading="isLoading"
      data-key="id"
      responsive-layout="scroll"
    >
      <template #empty>
        Brak ogłoszeń o wybranym statusie.
      </template>

      <Column
        field="title"
        header="Tytuł"
      />

      <Column header="Status">
        <template #body="{ data }">
          <Tag :value="data.status" />
        </template>
      </Column>

      <Column header="Akcje">
        <template #body="{ data }">
          <div class="actions">
            <Button
              v-if="data.status === 'pending'"
              icon="pi pi-check"
              severity="success"
              text
              size="small"
              aria-label="Akceptuj"
              @click="onApprove(data)"
            />
            <Button
              v-if="data.status === 'pending'"
              icon="pi pi-times"
              severity="warn"
              text
              size="small"
              aria-label="Odrzuć"
              @click="rejectingSlug = data.slug"
            />
            <Button
              icon="pi pi-trash"
              severity="danger"
              text
              size="small"
              aria-label="Usuń"
              @click="onDelete(data)"
            />
          </div>

          <div
            v-if="rejectingSlug === data.slug"
            class="reject"
          >
            <InputText
              v-model="rejectionReason"
              placeholder="Powód odrzucenia"
            />
            <Button
              label="Potwierdź"
              size="small"
              severity="warn"
              @click="onReject(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<style scoped>
.filter {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.actions {
  display: flex;
  gap: 0.25rem;
}

.reject {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
</style>
