<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { errorMessage, validationErrors } from '@/api/client'
import { requestPasswordReset } from '@/api/modules/v1/auth'
import AuthCard from '@/components/auth/AuthCard.vue'

const { t } = useI18n()

const email = ref('')
const isLoading = ref(false)
const isSent = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

async function onSubmit(): Promise<void> {
  errors.value = {}
  generalError.value = null
  isLoading.value = true

  try {
    await requestPasswordReset(email.value)
    isSent.value = true
  } catch (caught: unknown) {
    const fieldErrors = validationErrors(caught)

    if (Object.keys(fieldErrors).length > 0) {
      errors.value = fieldErrors
    } else {
      generalError.value = errorMessage(caught, t('auth.forgotPassword.error'))
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AuthCard
    :title="t('auth.forgotPassword.title')"
    :subtitle="t('auth.forgotPassword.subtitle')"
  >
    <Message
      v-if="isSent"
      severity="success"
      class="alert"
    >
      {{ t('auth.forgotPassword.success', { email }) }}
    </Message>

    <Message
      v-if="generalError"
      severity="error"
      class="alert"
    >
      {{ generalError }}
    </Message>

    <form
      class="form"
      @submit.prevent="onSubmit"
    >
      <div class="field">
        <label for="email">{{ t('auth.forgotPassword.email') }}</label>
        <InputText
          id="email"
          v-model="email"
          type="email"
          autocomplete="email"
          :invalid="Boolean(errors.email)"
          fluid
        />
        <Message
          v-if="errors.email"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ errors.email }}
        </Message>
      </div>

      <Button
        type="submit"
        :label="t('auth.forgotPassword.submit')"
        :loading="isLoading"
        class="form__submit"
        fluid
      />
    </form>

    <template #footer>
      {{ t('auth.forgotPassword.backToLoginPrefix') }}
      <RouterLink :to="{ name: 'login' }">
        {{ t('auth.forgotPassword.backToLoginLink') }}
      </RouterLink>
    </template>
  </AuthCard>
</template>

<style scoped>
.alert {
  margin-bottom: 1rem;
}
</style>
