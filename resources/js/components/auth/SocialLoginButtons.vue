<script setup lang="ts">
import Button from 'primevue/button'
import { useI18n } from 'vue-i18n'

import { useOAuthProviders } from '@/composables/useOAuthProviders'
import { useSocialLogin } from '@/composables/useSocialLogin'

const { t } = useI18n()
const { start } = useSocialLogin()
const { providers, isLoading } = useOAuthProviders()
</script>

<template>
  <div
    v-if="!isLoading && (providers?.length ?? 0) > 0"
    class="social"
  >
    <p class="social__divider">
      <span>{{ t('auth.social.divider') }}</span>
    </p>

    <div class="social__buttons">
      <Button
        v-if="providers?.includes('google')"
        type="button"
        :label="t('auth.social.google')"
        icon="pi pi-google"
        severity="secondary"
        outlined
        fluid
        @click="start('google')"
      />
      <Button
        v-if="providers?.includes('facebook')"
        type="button"
        :label="t('auth.social.facebook')"
        icon="pi pi-facebook"
        severity="secondary"
        outlined
        fluid
        @click="start('facebook')"
      />
    </div>
  </div>
</template>

<style scoped>
.social {
  margin-top: 1.25rem;
}

.social__divider {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0 1rem;
  color: var(--text-muted);
  font-size: 0.875rem;
}

.social__divider::before,
.social__divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--surface-border);
}

.social__buttons {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}
</style>