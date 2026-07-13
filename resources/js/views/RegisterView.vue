<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { errorMessage, validationErrors } from '@/api/client'
import AuthCard from '@/components/auth/AuthCard.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

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

    await router.push({ name: 'home' })
  } catch (caught: unknown) {
    const fieldErrors = validationErrors(caught)

    if (Object.keys(fieldErrors).length > 0) {
      errors.value = fieldErrors
    } else {
      generalError.value = errorMessage(caught, 'Rejestracja nie powiodła się.')
    }
  }
}
</script>

<template>
  <AuthCard
    title="Załóż konto"
    subtitle="Konto jest bezpłatne i pozwala publikować ogłoszenia."
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
        <label for="name">Imię i nazwisko</label>
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
        <label for="password_confirmation">Powtórz hasło</label>
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
        label="Załóż konto"
        class="form__submit"
        :loading="auth.isLoading"
        fluid
      />
    </form>

    <p class="consent">
      Zakładając konto, akceptujesz
      <RouterLink :to="{ name: 'terms' }">
        Regulamin
      </RouterLink>
      i potwierdzasz zapoznanie się z
      <RouterLink :to="{ name: 'privacy' }">
        Polityką prywatności
      </RouterLink>.
    </p>

    <template #footer>
      Masz już konto?
      <RouterLink :to="{ name: 'login' }">
        Zaloguj się
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
