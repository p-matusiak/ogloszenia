<script setup lang="ts">
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { ref } from 'vue'

import { errorMessage } from '@/api/client'
import { revealPhone } from '@/api/modules/v1/ads'
import type { Seller } from '@/types/api'

const props = defineProps<{
  slug: string
  seller?: Seller
  contactEmail: string | null
  hasPhone: boolean
  maskedPhone: string | null
}>()

const toast = useToast()
const revealedPhone = ref<string | null>(null)
const isRevealing = ref(false)

/** Inicjały zamiast zdjęcia: konta nie mają avatarów. */
function initials(name: string): string {
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')
}

/**
 * Numer przychodzi dopiero teraz, osobnym limitowanym żądaniem — dzięki temu
 * pobranie samego ogłoszenia nie oddaje numeru robotom.
 */
async function showPhone(): Promise<void> {
  isRevealing.value = true
  try {
    revealedPhone.value = await revealPhone(props.slug)
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught, 'Nie udało się pobrać numeru.'), life: 5000 })
  } finally {
    isRevealing.value = false
  }
}
</script>

<template>
  <div class="surface seller">
    <h2 class="seller__heading">
      Sprzedający
    </h2>

    <div
      v-if="props.seller"
      class="seller__identity"
    >
      <div
        class="seller__avatar"
        aria-hidden="true"
      >
        {{ initials(props.seller.name) }}
      </div>
      <div>
        <p class="seller__name">
          {{ props.seller.name }}
        </p>
        <p
          v-if="props.seller.member_since"
          class="seller__since"
        >
          W serwisie od {{ props.seller.member_since }}
        </p>
      </div>
    </div>

    <template v-if="hasPhone">
      <a
        v-if="revealedPhone"
        :href="`tel:${revealedPhone}`"
        class="seller__link"
      >
        <Button
          icon="pi pi-phone"
          :label="revealedPhone"
          fluid
        />
      </a>

      <Button
        v-else
        icon="pi pi-phone"
        :label="`${maskedPhone} — pokaż numer`"
        :loading="isRevealing"
        fluid
        @click="showPhone"
      />
    </template>

    <a
      v-if="contactEmail"
      :href="`mailto:${contactEmail}`"
      class="seller__link"
    >
      <Button
        icon="pi pi-envelope"
        label="Napisz e-mail"
        severity="secondary"
        outlined
        fluid
      />
    </a>
  </div>
</template>

<style scoped>
.seller {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
  padding: var(--card-padding);
}

.seller__heading {
  margin: 0;
  font-size: var(--title-card);
  font-weight: 700;
}

.seller__identity {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.seller__avatar {
  display: grid;
  place-items: center;
  width: 3rem;
  height: 3rem;
  flex-shrink: 0;
  border-radius: 50%;
  background: color-mix(in srgb, var(--p-primary-color) 15%, transparent);
  color: var(--p-primary-color);
  font-weight: 700;
}

.seller__name {
  margin: 0;
  font-weight: 600;
}

.seller__since {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.seller__link {
  text-decoration: none;
}
</style>
