<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { errorMessage, validationErrors } from '@/api/client'
import AuthCard from '@/components/auth/AuthCard.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

/**
 * Poświadczenia z seedera są pomocą deweloperską i nie mogą trafić na produkcję.
 * Gate na `import.meta.env.DEV` nie wystarczy: aplikacja jest serwowana ze
 * zbudowanych assetów także lokalnie, więc `DEV` jest tam fałszem.
 */
const showsSeededCredentials = import.meta.env.VITE_SHOW_SEED_CREDENTIALS === 'true'

async function onSubmit(): Promise<void> {
  errors.value = {}
  generalError.value = null

  try {
    await auth.login({ email: email.value, password: password.value })

    const redirect = route.query.redirect
    await router.push(typeof redirect === 'string' ? redirect : { name: 'home' })
  } catch (caught: unknown) {
    const fieldErrors = validationErrors(caught)

    if (Object.keys(fieldErrors).length > 0) {
      errors.value = fieldErrors
    } else {
      generalError.value = errorMessage(caught, 'Logowanie nie powiodło się.')
    }
  }
}
</script>

<template>
  <AuthCard
    title="Zaloguj się"
    subtitle="Zaloguj się, aby publikować i zarządzać ogłoszeniami."
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
        <label for="email">E-mail</label>
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
        <label for="password">Hasło</label>
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
      </div>

      <Button
        type="submit"
        label="Zaloguj"
        class="form__submit"
        :loading="auth.isLoading"
        fluid
      />
    </form>

    <Message
      v-if="showsSeededCredentials"
      severity="secondary"
      size="small"
      class="alert alert--hint"
    >
      Konto administratora po seedingu: <strong>admin@ogloszenia.local</strong> / <strong>password</strong>
    </Message>

    <template #footer>
      Nie masz konta?
      <RouterLink :to="{ name: 'register' }">
        Załóż konto
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
</style>
