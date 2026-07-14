<script setup lang="ts">
import Button from 'primevue/button'
import { storeToRefs } from 'pinia'
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import AuthCard from '@/components/auth/AuthCard.vue'
import {
  isVerified,
  parseVerificationStatus,
  useResendVerification,
  verificationCopy,
} from '@/composables/useEmailVerification'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const { isAuthenticated, isLoading } = storeToRefs(auth)
const { resend } = useResendVerification()

const screen = computed(() => parseVerificationStatus(route.query.status))
const copy = computed(() => verificationCopy(screen.value))

onMounted(async () => {
  // The backend consumed the link, not the SPA, so the cached user is a step
  // behind. Without this the activation banner keeps nagging a verified visitor.
  if (isVerified(screen.value)) {
    await auth.refresh()
  }
})
</script>

<template>
  <AuthCard
    :title="copy.title"
    :subtitle="copy.subtitle"
  >
    <i
      class="status__icon"
      :class="[copy.icon, `status__icon--${copy.tone}`]"
      aria-hidden="true"
    />

    <p class="status__text">
      {{ copy.body }}
    </p>

    <Button
      v-if="copy.tone === 'success'"
      label="Przejdź do ogłoszeń"
      fluid
      @click="router.push({ name: 'landing' })"
    />

    <Button
      v-else-if="isAuthenticated"
      label="Wyślij nowy link"
      icon="pi pi-send"
      fluid
      :loading="isLoading"
      @click="resend"
    />

    <Button
      v-else
      label="Zaloguj się"
      fluid
      @click="router.push({ name: 'login' })"
    />
  </AuthCard>
</template>

<style scoped>
.status__icon {
  display: block;
  font-size: 2.5rem;
  text-align: center;
  margin-bottom: 1rem;
}

.status__icon--success {
  color: var(--tone-success-text);
}

.status__icon--pending {
  color: var(--p-primary-color);
}

.status__icon--failure {
  color: #b91c1c;
}

.status__text {
  margin: 0 0 1.5rem;
  text-align: center;
  line-height: 1.6;
}
</style>
