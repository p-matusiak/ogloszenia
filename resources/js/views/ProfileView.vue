<script setup lang="ts">
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import { storeToRefs } from 'pinia'
import { computed, ref, watch } from 'vue'

import { errorMessage, validationErrors } from '@/api/client'
import AccountVerificationStatus from '@/components/auth/AccountVerificationStatus.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { user } = storeToRefs(auth)

const name = ref('')
const email = ref('')
const bio = ref('')
const avatarFile = ref<File | null>(null)
const removeAvatar = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)
const previewUrl = ref<string | null>(null)

watch(
  user,
  (next) => {
    name.value = next?.name ?? ''
    email.value = next?.email ?? ''
    bio.value = next?.bio ?? ''
    if (avatarFile.value === null) {
      previewUrl.value = next?.avatar_url ?? null
    }
  },
  { immediate: true },
)

/** Warns before the save, not after: the change costs the user their verification. */
const willRequireReverification = computed(
  () => user.value?.is_email_verified === true && email.value !== user.value.email,
)

const initials = computed(() =>
  (user.value?.name ?? 'U')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join(''),
)

function onAvatarSelect(event: { files: File[] }): void {
  const [file] = event.files

  avatarFile.value = file ?? null
  removeAvatar.value = false
  previewUrl.value = file ? URL.createObjectURL(file) : user.value?.avatar_url ?? null
}

function clearAvatar(): void {
  avatarFile.value = null
  removeAvatar.value = true
  previewUrl.value = null
}

async function onSubmit(): Promise<void> {
  errors.value = {}
  generalError.value = null

  try {
    await auth.updateProfile({
      name: name.value,
      email: email.value,
      bio: bio.value,
      avatar: avatarFile.value,
      remove_avatar: removeAvatar.value,
    })

    avatarFile.value = null
    removeAvatar.value = false
    previewUrl.value = user.value?.avatar_url ?? null
  } catch (caught: unknown) {
    const fieldErrors = validationErrors(caught)

    if (Object.keys(fieldErrors).length > 0) {
      errors.value = fieldErrors
    } else {
      generalError.value = errorMessage(caught, 'Nie udało się zapisać profilu.')
    }
  }
}
</script>

<template>
  <section class="profile">
    <header class="profile__hero">
      <div class="profile__identity">
        <Avatar
          v-if="previewUrl"
          :image="previewUrl"
          shape="circle"
          size="xlarge"
          class="profile__avatar"
        />
        <Avatar
          v-else
          :label="initials"
          shape="circle"
          size="xlarge"
          class="profile__avatar profile__avatar--fallback"
        />
        <div>
          <h1>Profil użytkownika</h1>
          <p>Zarządzaj zdjęciem profilowym i podstawowymi danymi konta.</p>
        </div>
      </div>

      <nav class="profile__nav">
        <RouterLink :to="{ name: 'profile' }">
          Dane profilu
        </RouterLink>
        <RouterLink :to="{ name: 'ads.mine' }">
          Moje ogłoszenia
        </RouterLink>
        <RouterLink :to="{ name: 'ads.create' }">
          Dodaj ogłoszenie
        </RouterLink>
      </nav>
    </header>

    <div class="profile__layout">
      <div class="profile__aside">
        <section class="profile__card profile__card--aside">
          <h2>Status konta</h2>
          <AccountVerificationStatus />
        </section>

        <aside class="profile__card profile__card--aside">
          <h2>Avatar</h2>
          <p>Kwadratowe lub okrągłe zdjęcie, do 2 MB.</p>

          <div class="profile__avatar-preview">
            <Avatar
              v-if="previewUrl"
              :image="previewUrl"
              shape="circle"
              size="xlarge"
              class="profile__avatar profile__avatar--large"
            />
            <Avatar
              v-else
              :label="initials"
              shape="circle"
              size="xlarge"
              class="profile__avatar profile__avatar--fallback profile__avatar--large"
            />
          </div>

          <FileUpload
            mode="basic"
            choose-label="Wybierz avatar"
            accept="image/*"
            :max-file-size="2 * 1024 * 1024"
            custom-upload
            auto
            @select="onAvatarSelect"
          />

          <Button
            v-if="previewUrl || user?.avatar_url"
            label="Usuń avatar"
            severity="secondary"
            text
            @click="clearAvatar"
          />
        </aside>
      </div>

      <form
        class="profile__card profile__form"
        @submit.prevent="onSubmit"
      >
        <div class="profile__section-heading">
          <div>
            <h2>Podstawowe dane</h2>
            <p>Te informacje opisują konto użytkownika w serwisie.</p>
          </div>
          <Button
            type="submit"
            label="Zapisz profil"
            icon="pi pi-check"
            :loading="auth.isLoading"
          />
        </div>

        <Message
          v-if="generalError"
          severity="error"
        >
          {{ generalError }}
        </Message>

        <label class="field">
          <span class="field__label">Nazwa użytkownika</span>
          <InputText
            v-model="name"
            :invalid="Boolean(errors.name)"
          />
          <small
            v-if="errors.name"
            class="field__error"
          >{{ errors.name }}</small>
        </label>

        <label class="field">
          <span class="field__label">E-mail</span>
          <InputText
            v-model="email"
            type="email"
            :invalid="Boolean(errors.email)"
          />
          <small
            v-if="errors.email"
            class="field__error"
          >{{ errors.email }}</small>
          <Message
            v-if="willRequireReverification"
            severity="warn"
            size="small"
            variant="simple"
          >
            Zmiana adresu cofnie potwierdzenie konta. Na nowy adres wyślemy link aktywacyjny.
          </Message>
        </label>

        <label class="field">
          <span class="field__label">Opis profilu</span>
          <Textarea
            v-model="bio"
            rows="6"
            auto-resize
            :invalid="Boolean(errors.bio)"
          />
          <small
            v-if="errors.bio"
            class="field__error"
          >{{ errors.bio }}</small>
        </label>
      </form>
    </div>
  </section>
</template>

<style scoped>
.profile {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.profile__hero,
.profile__card {
  border: 1px solid var(--surface-border);
  border-radius: 1rem;
  background: var(--surface-card);
  box-shadow: 0 20px 40px rgb(15 23 42 / 0.06);
}

.profile__hero {
  padding: 1.25rem;
}

.profile__identity {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.profile__identity h1,
.profile__section-heading h2,
.profile__card h2 {
  margin: 0;
}

.profile__identity p,
.profile__section-heading p,
.profile__card p {
  margin: 0.35rem 0 0;
  color: var(--text-muted);
}

.profile__avatar--fallback {
  background: color-mix(in srgb, var(--p-primary-color) 20%, white 80%);
  color: var(--p-primary-color);
}

.profile__avatar--large {
  width: 5.5rem;
  height: 5.5rem;
  font-size: 1.4rem;
}

.profile__nav {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 1rem;
}

.profile__nav a {
  padding: 0.55rem 0.85rem;
  border-radius: 999px;
  text-decoration: none;
  color: var(--text-strong);
  background: var(--surface-ground);
}

.profile__nav a.router-link-active {
  color: var(--p-primary-color);
  background: color-mix(in srgb, var(--p-primary-color) 12%, white 88%);
}

.profile__layout {
  display: grid;
  gap: 1.25rem;
}

/* Status i avatar dzielą jedną kolumnę, żeby siatka pozostała dwukolumnowa. */
.profile__aside {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.profile__card {
  padding: 1.25rem;
}

.profile__card--aside {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.profile__avatar-preview {
  display: flex;
  justify-content: center;
  padding: 0.5rem 0;
}

.profile__form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.profile__section-heading {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.field__label {
  font-weight: 600;
}

.field__error {
  color: #b91c1c;
}

@media (width >= 72rem) {
  .profile__layout {
    grid-template-columns: minmax(18rem, 22rem) minmax(0, 1fr);
    align-items: start;
  }
}
</style>
