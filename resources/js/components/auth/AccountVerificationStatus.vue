<script setup lang="ts">
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { storeToRefs } from 'pinia'

import { useResendVerification } from '@/composables/useEmailVerification'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { user, isLoading, isEmailVerified } = storeToRefs(auth)
const { resend } = useResendVerification()
</script>

<template>
  <div class="account-status">
    <div class="account-status__row">
      <span class="account-status__label">Status konta</span>
      <Tag
        :severity="isEmailVerified ? 'success' : 'warn'"
        :icon="isEmailVerified ? 'pi pi-verified' : 'pi pi-exclamation-triangle'"
        :value="isEmailVerified ? 'Potwierdzone' : 'Niepotwierdzone'"
      />
    </div>

    <p class="account-status__hint">
      <template v-if="isEmailVerified">
        Adres <strong>{{ user?.email }}</strong> jest potwierdzony. Możesz publikować ogłoszenia.
      </template>
      <template v-else>
        Wysłaliśmy link aktywacyjny na <strong>{{ user?.email }}</strong>. Do czasu potwierdzenia
        nie opublikujesz ani nie zedytujesz ogłoszenia.
      </template>
    </p>

    <Button
      v-if="!isEmailVerified"
      label="Wyślij link ponownie"
      icon="pi pi-send"
      severity="secondary"
      outlined
      size="small"
      :loading="isLoading"
      @click="resend"
    />
  </div>
</template>

<style scoped>
.account-status {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
  align-items: flex-start;
}

.account-status__row {
  display: flex;
  align-items: center;
  gap: 0.625rem;
}

.account-status__label {
  font-weight: 600;
}

.account-status__hint {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.5;
  color: var(--text-muted);
}
</style>
