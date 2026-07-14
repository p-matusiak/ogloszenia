<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

import { errorMessage, validationErrors } from '@/api/client'
import AuthCard from '@/components/auth/AuthCard.vue'
import SocialLoginButtons from '@/components/auth/SocialLoginButtons.vue'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

onMounted(() => {
  const oauthError = route.query.oauth_error

  if (oauthError === 'unconfigured') {
    generalError.value = t('auth.oauth.unconfigured')
  } else if (oauthError === 'email_required') {
    generalError.value = t('auth.oauth.emailRequired')
  } else if (oauthError === 'failed' || oauthError === '1') {
    generalError.value = t('auth.oauth.error')
  }
})

async function onSubmit(): Promise<void> {
  errors.value = {}
  generalError.value = null

  try {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    await router.push({ name: 'landing' })
  } catch (caught: unknown) {
    const fieldErrors = validationErrors(caught)

    if (Object.keys(fieldErrors).length > 0) {
      errors.value = fieldErrors
    } else {
      generalError.value = errorMessage(caught, t('auth.register.error'))
    }
  }
}
</script>

<template>
  <AuthCard
    :title="t('auth.register.title')"
    :subtitle="t('auth.register.subtitle')"
  >
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
        <label for="name">{{ t('auth.register.name') }}</label>
        <InputText
          id="name"
          v-model="name"
          autocomplete="name"
          :invalid="Boolean(errors.name)"
          fluid
        />
        <Message
          v-if="errors.name"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ errors.name }}
        </Message>
      </div>

      <div class="field">
        <label for="email">{{ t('auth.register.email') }}</label>
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
        <label for="password">{{ t('auth.register.password') }}</label>
        <Password
          v-model="password"
          input-id="password"
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

      <!-- Reguła `confirmed` Laravela zgłasza niezgodność pod kluczem `password`,
           więc komunikat pojawia się przy polu wyżej, a nie tutaj. -->
      <div class="field">
        <label for="password_confirmation">{{ t('auth.register.passwordConfirmation') }}</label>
        <Password
          v-model="passwordConfirmation"
          input-id="password_confirmation"
          :input-props="{ autocomplete: 'new-password' }"
          :feedback="false"
          :invalid="Boolean(errors.password)"
          toggle-mask
          fluid
        />
      </div>

      <Button
        type="submit"
        :label="t('auth.register.submit')"
        class="form__submit"
        :loading="auth.isLoading"
        fluid
      />
    </form>

    <SocialLoginButtons />

    <p class="consent">
      {{ t('auth.register.consentPrefix') }}
      <RouterLink :to="{ name: 'terms' }">
        {{ t('auth.register.termsLink') }}
      </RouterLink>
      {{ t('auth.register.consentMiddle') }}
      <RouterLink :to="{ name: 'privacy' }">
        {{ t('auth.register.privacyLink') }}
      </RouterLink>.
    </p>

    <template #footer>
      {{ t('auth.register.hasAccount') }}
      <RouterLink :to="{ name: 'login' }">
        {{ t('auth.register.loginLink') }}
      </RouterLink>
    </template>
  </AuthCard>
</template>

<style scoped>
.alert {
  margin-bottom: 1rem;
}

.consent {
  margin: 1.25rem 0 0;
  font-size: 0.8125rem;
  line-height: 1.5;
  color: var(--text-muted);
}
</style>
