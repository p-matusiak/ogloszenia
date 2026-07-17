<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

import { errorMessage, validationErrors } from '@/api/client'
import { resetPassword } from '@/api/modules/v1/auth'
import AuthCard from '@/components/auth/AuthCard.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const email = ref(typeof route.query.email === 'string' ? route.query.email : '')
const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''))
const password = ref('')
const passwordConfirmation = ref('')
const isLoading = ref(false)
const isSuccess = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

const hasValidLink = computed(() => token.value.length > 0 && email.value.length > 0)

async function onSubmit(): Promise<void> {
  if (!hasValidLink.value) {
    generalError.value = t('auth.resetPassword.invalidLink')
    return
  }

  errors.value = {}
  generalError.value = null
  isLoading.value = true

  try {
    await resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    isSuccess.value = true
    password.value = ''
    passwordConfirmation.value = ''
    window.setTimeout(() => {
      void router.push({ name: 'login', query: { email: email.value, reset: '1' } })
    }, 800)
  } catch (caught: unknown) {
    const fieldErrors = validationErrors(caught)

    if (Object.keys(fieldErrors).length > 0) {
      errors.value = fieldErrors
    } else {
      generalError.value = errorMessage(caught, t('auth.resetPassword.error'))
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AuthCard
    :title="t('auth.resetPassword.title')"
    :subtitle="t('auth.resetPassword.subtitle')"
  >
    <Message
      v-if="isSuccess"
      severity="success"
      class="alert"
    >
      {{ t('auth.resetPassword.success') }}
    </Message>

    <Message
      v-else-if="!hasValidLink"
      severity="warn"
      class="alert"
    >
      {{ t('auth.resetPassword.invalidLink') }}
    </Message>

    <Message
      v-else-if="generalError"
      severity="error"
      class="alert"
    >
      {{ generalError }}
    </Message>

    <form
      v-if="hasValidLink && !isSuccess"
      class="form"
      @submit.prevent="onSubmit"
    >
      <div class="field">
        <label for="email">{{ t('auth.resetPassword.email') }}</label>
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

      <div class="field">
        <label for="password">{{ t('auth.resetPassword.password') }}</label>
        <Password
          v-model="password"
          input-id="password"
          :feedback="false"
          :input-props="{ autocomplete: 'new-password' }"
          :invalid="Boolean(errors.password)"
          toggle-mask
          fluid
        />
        <Message
          v-if="errors.password"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ errors.password }}
        </Message>
      </div>

      <div class="field">
        <label for="password_confirmation">{{ t('auth.resetPassword.passwordConfirmation') }}</label>
        <Password
          v-model="passwordConfirmation"
          input-id="password_confirmation"
          :feedback="false"
          :input-props="{ autocomplete: 'new-password' }"
          :invalid="Boolean(errors.password)"
          toggle-mask
          fluid
        />
      </div>

      <Button
        type="submit"
        :label="t('auth.resetPassword.submit')"
        :loading="isLoading"
        class="form__submit"
        fluid
      />
    </form>

    <template #footer>
      {{ t('auth.resetPassword.backToLoginPrefix') }}
      <RouterLink :to="{ name: 'login' }">
        {{ t('auth.resetPassword.backToLoginLink') }}
      </RouterLink>
    </template>
  </AuthCard>
</template>

<style scoped>
.alert {
  margin-bottom: 1rem;
}
</style>
