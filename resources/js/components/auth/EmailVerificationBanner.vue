<script setup lang="ts">
import Button from 'primevue/button'
import Message from 'primevue/message'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { useResendVerification } from '@/composables/useEmailVerification'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()
const { user, isLoading, isResolved, isAuthenticated, isEmailVerified } = storeToRefs(auth)
const { resend } = useResendVerification()

// Hidden until the boot request has answered, otherwise the banner flashes for
// a split second on every page load for verified users too.
const isVisible = computed(() => isResolved.value && isAuthenticated.value && !isEmailVerified.value)
</script>

<template>
  <Message
    v-if="isVisible"
    severity="warn"
    :closable="false"
    class="verify"
  >
    <div class="verify__body">
      <span>
        <i18n-t
          keypath="auth.verification.banner"
          scope="global"
        >
          <template #email>
            <strong>{{ user?.email }}</strong>
          </template>
        </i18n-t>
      </span>
      <Button
        :label="t('auth.verification.resend')"
        size="small"
        severity="warn"
        outlined
        :loading="isLoading"
        @click="resend"
      />
    </div>
  </Message>
</template>

<style scoped>
.verify {
  margin: 0 0 var(--stack-gap);
}

.verify__body {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  font-size: 0.875rem;
}
</style>
