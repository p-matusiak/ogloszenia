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

const email = ref('')
const password = ref('')
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

/**
 * Poświadczenia z seedera są pomocą deweloperską i nie mogą trafić na produkcję.
 * Gate na `import.meta.env.DEV` nie wystarczy: aplikacja jest serwowana ze
 * zbudowanych assetów także lokalnie, więc `DEV` jest tam fałszem.
 */
const showsSeededCredentials = import.meta.env.VITE_SHOW_SEED_CREDENTIALS === 'true'

onMounted(() => {
  if (typeof route.query.email === 'string') {
    email.value = route.query.email
  }

  if (route.query.reset === '1') {
    successMessage.value = t('auth.resetPassword.success')
  }

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
    await auth.login({ email: email.value, password: password.value })

    const redirect = route.query.redirect
    await router.push(typeof redirect === 'string' ? redirect : { name: 'landing' })
  } catch (caught: unknown) {
    const fieldErrors = validationErrors(caught)

    if (Object.keys(fieldErrors).length > 0) {
      errors.value = fieldErrors
    } else {
      generalError.value = errorMessage(caught, t('auth.login.error'))
    }
  }
}
</script>

<template>
  <AuthCard
    :title="t('auth.login.title')"
    :subtitle="t('auth.login.subtitle')"
  >
    <Message
      v-if="successMessage"
      severity="success"
      class="alert"
    >
      {{ successMessage }}
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
        <label for="email">{{ t('auth.login.email') }}</label>
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
        <label for="password">{{ t('auth.login.password') }}</label>
        <Password
          v-model="password"
          input-id="password"
          :input-props="{ autocomplete: 'current-password' }"
          :feedback="false"
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

        <RouterLink
          class="login__forgot-link"
          :to="{ name: 'password.forgot' }"
        >
          {{ t('auth.login.forgotPassword') }}
        </RouterLink>
      </div>

      <Button
        type="submit"
        :label="t('auth.login.submit')"
        class="form__submit"
        :loading="auth.isLoading"
        fluid
      />
    </form>

    <SocialLoginButtons />

    <Message
      v-if="showsSeededCredentials"
      severity="secondary"
      size="small"
      class="alert alert--hint"
    >
      Konto administratora po seedingu: <strong>admin@zunto.local</strong> / <strong>password</strong>
    </Message>

    <template #footer>
      {{ t('auth.login.noAccount') }}
      <RouterLink :to="{ name: 'register' }">
        {{ t('auth.login.registerLink') }}
      </RouterLink>
    </template>
  </AuthCard>
</template>

<style scoped>
.alert {
  margin-bottom: 1rem;
}

.alert--hint {
  margin: 1.25rem 0 0;
}

.login__forgot-link {
  align-self: flex-end;
  font-size: 0.8125rem;
  color: var(--brand-blue);
  text-decoration: none;
}

.login__forgot-link:hover {
  color: var(--brand-orange);
}
</style>
