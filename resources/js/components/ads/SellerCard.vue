<script setup lang="ts">
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { computed, ref } from 'vue'

import { errorMessage } from '@/api/client'
import { revealPhone } from '@/api/modules/v1/ads'
import type { Seller } from '@/types/api'

const props = defineProps<{
  slug: string
  seller?: Seller
  hasPhone: boolean
  maskedPhone: string | null
  showMessage?: boolean
}>()

const emit = defineEmits<{ message: [] }>()

const toast = useToast()
const revealedPhone = ref<string | null>(null)
const isRevealing = ref(false)

const sellerInitials = computed(() => {
  const name = props.seller?.name ?? ''
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')
})

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
      <Avatar
        v-if="props.seller.avatar_url"
        :image="props.seller.avatar_url"
        :alt="`Avatar ${props.seller.name}`"
        shape="circle"
        size="large"
        class="seller__avatar"
      />
      <Avatar
        v-else
        :label="sellerInitials"
        shape="circle"
        size="large"
        class="seller__avatar seller__avatar--fallback"
      />
      <div>
        <RouterLink
          v-if="props.seller.slug"
          :to="{ name: 'sellers.show', params: { sellerSlug: props.seller.slug } }"
          class="seller__name seller__name--link"
        >
          {{ props.seller.name }}
        </RouterLink>
        <p
          v-else
          class="seller__name"
        >
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

    <Button
      v-if="showMessage"
      icon="pi pi-comments"
      label="Wyślij wiadomość"
      fluid
      @click="emit('message')"
    />
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
  flex-shrink: 0;
}

.seller__avatar--fallback {
  background: color-mix(in srgb, var(--p-primary-color) 15%, transparent);
  color: var(--p-primary-color);
  font-weight: 700;
}

.seller__name {
  margin: 0;
  font-weight: 600;
}

.seller__name--link {
  color: inherit;
  text-decoration: none;
}

.seller__name--link:hover {
  color: var(--p-primary-color);
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
