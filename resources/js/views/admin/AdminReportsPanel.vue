<script setup lang="ts">
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'

import { errorMessage } from '@/api/client'
import { fetchReports, resolveReport } from '@/api/modules/v1/admin'
import { formatDate } from '@/composables/useFormatting'
import type { AdReport, ReportStatus } from '@/types/api'

const toast = useToast()

const reports = ref<AdReport[]>([])
const isLoading = ref(false)

async function load(): Promise<void> {
  isLoading.value = true
  try {
    reports.value = (await fetchReports()).data
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  } finally {
    isLoading.value = false
  }
}

async function resolve(report: AdReport, status: ReportStatus): Promise<void> {
  try {
    await resolveReport(report.id, status)
    toast.add({ severity: 'success', summary: 'Zgłoszenie rozpatrzone', life: 4000 })
    await load()
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  }
}

onMounted(load)
</script>

<template>
  <DataTable
    :value="reports"
    :loading="isLoading"
    data-key="id"
    responsive-layout="scroll"
  >
    <template #empty>
      Brak oczekujących zgłoszeń.
    </template>

    <Column header="Ogłoszenie">
      <template #body="{ data }">
        {{ data.ad?.title ?? '—' }}
      </template>
    </Column>

    <Column
      field="reason"
      header="Powód"
    />

    <Column header="Wiadomość">
      <template #body="{ data }">
        {{ data.message ?? '—' }}
      </template>
    </Column>

    <Column header="Zgłoszono">
      <template #body="{ data }">
        {{ formatDate(data.created_at) }}
      </template>
    </Column>

    <Column header="Akcje">
      <template #body="{ data }">
        <div class="actions">
          <Button
            label="Rozpatrzone"
            size="small"
            text
            severity="success"
            @click="resolve(data, 'reviewed')"
          />
          <Button
            label="Odrzuć"
            size="small"
            text
            severity="secondary"
            @click="resolve(data, 'dismissed')"
          />
        </div>
      </template>
    </Column>
  </DataTable>
</template>

<style scoped>
.actions {
  display: flex;
  gap: 0.25rem;
}
</style>
