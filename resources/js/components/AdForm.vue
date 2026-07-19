<script setup lang="ts">
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import { computed, onMounted, toRef } from 'vue'

import AdPreviewPanel from '@/components/form/AdPreviewPanel.vue'
import CategoryPicker from '@/components/form/CategoryPicker.vue'
import ConditionPicker from '@/components/form/ConditionPicker.vue'
import DeliveryPicker from '@/components/form/DeliveryPicker.vue'
import FormSection from '@/components/form/FormSection.vue'
import ImageUploader from '@/components/form/ImageUploader.vue'
import LocationPicker from '@/components/form/LocationPicker.vue'
import MoneyInput from '@/components/form/MoneyInput.vue'
import { useAdCategorySuggestion } from '@/composables/useAdCategorySuggestion'
import { useAuthStore } from '@/stores/auth'
import { useCategoryStore } from '@/stores/categories'
import type { AdFormValues, AdImage } from '@/types/api'

const props = withDefaults(
  defineProps<{
    modelValue: AdFormValues
    existingImages?: AdImage[]
    errors: Record<string, string>
    isSubmitting: boolean
    submitLabel: string
  }>(),
  { existingImages: () => [] },
)

const emit = defineEmits<{
  'update:modelValue': [values: AdFormValues]
  submit: []
}>()

const MAX_IMAGES = 12
const MAX_IMAGE_MB = 10
const MAX_DESCRIPTION = 10000

const auth = useAuthStore()
const categories = useCategoryStore()

const profilePhone = computed(() => auth.user?.phone ?? null)

const remainingCharacters = computed(() => MAX_DESCRIPTION - props.modelValue.description.length)

/** MoneyInput mówi kanonicznym „1234.50”; AdFormValues trzyma cenę jako liczbę. */
const priceText = computed(() =>
  props.modelValue.price === null ? '' : String(props.modelValue.price),
)

function setPrice(value: string): void {
  patch({ price: value === '' ? null : Number(value) })
}

/** Podgląd bierze pierwsze zdjęcie: to ono będzie zdjęciem głównym. */
const previewImage = computed<string | null>(() => {
  const kept = props.existingImages.filter((i) => !props.modelValue.removed_image_ids.includes(i.id))
  if (kept[0]) {
    return kept[0].url
  }

  return props.modelValue.temporary_images[0]?.preview_url ?? null
})

/**
 * `props.modelValue` odświeża się dopiero przy przerysowaniu, więc dwa
 * wywołania w jednym ticku bazowałyby na tym samym, starym obiekcie i drugie
 * cofałoby pierwsze. Każde dziecko musi więc zmieścić swoją zmianę w jednym
 * zdarzeniu — dlatego DeliveryPicker emituje metody i ceny razem.
 */
function patch(partial: Partial<AdFormValues>): void {
  emit('update:modelValue', { ...props.modelValue, ...partial })
}

const { isSuggesting, wasSuggested, isAiAvailable } = useAdCategorySuggestion(
  toRef(props, 'modelValue'),
  patch,
)

onMounted(() => void categories.load())
</script>

<template>
  <div class="layout">
    <form
      class="layout__form"
      @submit.prevent="emit('submit')"
    >
      <FormSection
        :step="1"
        title="Zdjęcia"
      >
        <ImageUploader
          :uploads="modelValue.temporary_images"
          :existing-images="existingImages"
          :removed-ids="modelValue.removed_image_ids"
          :max-images="MAX_IMAGES"
          :max-size-mb="MAX_IMAGE_MB"
          @update:uploads="patch({ temporary_images: $event })"
          @remove-existing="patch({ removed_image_ids: [...modelValue.removed_image_ids, $event] })"
        />
        <Message
          v-if="errors.images || errors.temporary_images || errors['temporary_images.0']"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ errors.images ?? errors.temporary_images ?? errors['temporary_images.0'] }}
        </Message>
      </FormSection>

      <FormSection
        :step="2"
        title="Podstawowe informacje"
      >
        <div class="field">
          <label for="title">Tytuł ogłoszenia <span class="req">*</span></label>
          <InputText
            id="title"
            :model-value="modelValue.title"
            :invalid="Boolean(errors.title)"
            @update:model-value="patch({ title: $event ?? '' })"
          />
          <small>Zwięzły i trafny tytuł przyciąga więcej zainteresowanych.</small>
          <Message
            v-if="errors.title"
            severity="error"
            size="small"
            variant="simple"
          >
            {{ errors.title }}
          </Message>
        </div>

        <div class="field stack">
          <span class="field__label">Kategoria <span class="req">*</span></span>
          <CategoryPicker
            :model-value="modelValue.category_id"
            :invalid="Boolean(errors.category_id)"
            @update:model-value="patch({ category_id: $event })"
          />
          <small v-if="isSuggesting">
            AI dobiera kategorię na podstawie tytułu…
          </small>
          <small v-else-if="wasSuggested && isAiAvailable">
            Kategoria zasugerowana przez AI — możesz ją zmienić.
          </small>
          <small v-else>
            Zacznij pisać tytuł, aby otrzymać podpowiedź kategorii, albo wybierz ręcznie.
          </small>
          <Message
            v-if="errors.category_id"
            severity="error"
            size="small"
            variant="simple"
          >
            {{ errors.category_id }}
          </Message>
        </div>

        <div class="field stack">
          <span class="field__label">Stan</span>
          <ConditionPicker
            :model-value="modelValue.condition"
            :invalid="Boolean(errors.condition)"
            @update:model-value="patch({ condition: $event })"
          />
          <small>Opcjonalne. Kliknij wybraną kartę ponownie, żeby ją odznaczyć.</small>
          <Message
            v-if="errors.condition"
            severity="error"
            size="small"
            variant="simple"
          >
            {{ errors.condition }}
          </Message>
        </div>

        <div class="grid grid--pair stack">
          <div class="field">
            <label for="price">Cena</label>
            <MoneyInput
              id="price"
              :model-value="priceText"
              :invalid="Boolean(errors.price)"
              aria-label="Cena"
              @update:model-value="setPrice"
            />
            <small>Wpisz złote, a strzałką w prawo przejdź do groszy.</small>
            <Message
              v-if="errors.price"
              severity="error"
              size="small"
              variant="simple"
            >
              {{ errors.price }}
            </Message>
          </div>

          <div class="field field--inline">
            <Checkbox
              input-id="is_negotiable"
              :model-value="modelValue.is_negotiable"
              binary
              @update:model-value="patch({ is_negotiable: $event })"
            />
            <label for="is_negotiable">
              <span>Do negocjacji</span>
              <small>Kupujący będą mogli zaproponować swoją cenę.</small>
            </label>
          </div>
        </div>
      </FormSection>

      <FormSection
        :step="3"
        title="Lokalizacja"
      >
        <LocationPicker
          :location="modelValue.location"
          :latitude="modelValue.latitude"
          :longitude="modelValue.longitude"
          :invalid="Boolean(errors.location || errors.latitude || errors.longitude)"
          @change="patch($event)"
        />
        <Message
          v-if="errors.location"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ errors.location }}
        </Message>
        <Message
          v-if="errors.latitude || errors.longitude"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ errors.latitude ?? errors.longitude }}
        </Message>
      </FormSection>

      <FormSection
        :step="4"
        title="Dostawa i odbiór"
      >
        <DeliveryPicker
          :methods="modelValue.delivery_methods"
          :prices="modelValue.delivery_prices"
          @change="patch({ delivery_methods: $event.methods, delivery_prices: $event.prices })"
        />
      </FormSection>

      <FormSection
        :step="5"
        title="Opis"
      >
        <div class="field">
          <Textarea
            id="description"
            :model-value="modelValue.description"
            rows="7"
            :invalid="Boolean(errors.description)"
            aria-label="Opis ogłoszenia"
            @update:model-value="patch({ description: $event ?? '' })"
          />
          <p class="counter">
            Pozostało znaków: {{ remainingCharacters }}
          </p>
          <Message
            v-if="errors.description"
            severity="error"
            size="small"
            variant="simple"
          >
            {{ errors.description }}
          </Message>
        </div>
      </FormSection>

      <FormSection
        :step="6"
        title="Kontakt"
      >
        <p class="contact-hint">
          Kupujący mogą napisać do Ciebie wiadomością w serwisie. Numer telefonu jest opcjonalny
          i nie jest widoczny publicznie — pojawia się dopiero po kliknięciu „Pokaż numer”.
        </p>

        <p
          v-if="profilePhone"
          class="contact-profile"
        >
          <i class="pi pi-phone" />
          Numer z profilu: <strong>{{ profilePhone }}</strong>
        </p>
        <p
          v-else
          class="contact-profile contact-profile--muted"
        >
          Nie masz numeru w profilu — możesz go dodać w ustawieniach konta albo podać tylko przy tym
          ogłoszeniu.
        </p>

        <div class="field field--inline">
          <Checkbox
            input-id="use_custom_phone"
            :model-value="modelValue.use_custom_phone"
            binary
            @update:model-value="patch({ use_custom_phone: $event, contact_phone: $event ? modelValue.contact_phone : '' })"
          />
          <label for="use_custom_phone">Chcę podać inny numer telefonu niż w profilu</label>
        </div>

        <div
          v-if="modelValue.use_custom_phone"
          class="field"
        >
          <label for="contact_phone">Numer przy tym ogłoszeniu</label>
          <InputText
            id="contact_phone"
            :model-value="modelValue.contact_phone"
            :invalid="Boolean(errors.contact_phone)"
            @update:model-value="patch({ contact_phone: $event ?? '' })"
          />
          <Message
            v-if="errors.contact_phone"
            severity="error"
            size="small"
            variant="simple"
          >
            {{ errors.contact_phone }}
          </Message>
        </div>

        <div class="field field--inline terms">
          <Checkbox
            input-id="accept_terms"
            :model-value="modelValue.accept_terms"
            binary
            :invalid="Boolean(errors.accept_terms)"
            @update:model-value="patch({ accept_terms: $event })"
          />
          <label for="accept_terms">Akceptuję regulamin serwisu</label>
        </div>
        <Message
          v-if="errors.accept_terms"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ errors.accept_terms }}
        </Message>
      </FormSection>

      <div class="actions">
        <Button
          type="submit"
          :label="submitLabel"
          :loading="isSubmitting"
          class="actions__submit"
        />
      </div>
    </form>

    <div class="layout__aside">
      <AdPreviewPanel
        :values="modelValue"
        :preview-image="previewImage"
      />
    </div>
  </div>
</template>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--stack-gap);
  align-items: start;
}

@media (width >= 68rem) {
  .layout {
    grid-template-columns: minmax(0, 1fr) 22rem;
  }

  .layout__aside {
    position: sticky;
    top: 6rem;
  }
}

.layout__form {
  display: flex;
  flex-direction: column;
  gap: var(--stack-gap);
  min-width: 0;
}

.grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: 1fr;
}

@media (width >= 55rem) {
  .grid--pair {
    grid-template-columns: 1fr 1fr;
    align-items: start;
  }
}

/* Pola w sekcji leżą jedno pod drugim; pierwsze nie potrzebuje odstępu. */
.stack {
  margin-top: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 0;
}

.field label,
.field__label {
  font-size: 0.8125rem;
  font-weight: 500;
}

.field small {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.field--inline {
  flex-direction: row;
  align-items: flex-start;
  gap: 0.5rem;
}

.field--inline label {
  display: flex;
  flex-direction: column;
  cursor: pointer;
}

/* Dopiero w dwóch kolumnach checkbox stoi obok pola z etykietą — wtedy musi
   zjechać na wysokość inputa, a nie jego etykiety. W jednej kolumnie ten
   odstęp byłby pustą dziurą nad polem. */
@media (width >= 55rem) {
  .grid--pair .field--inline {
    padding-top: 1.6rem;
  }
}

.contact-hint {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  color: var(--text-muted);
  line-height: 1.5;
}

.contact-profile {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0 0 1rem;
  font-size: 0.875rem;
}

.contact-profile--muted {
  color: var(--text-muted);
}

.terms {
  padding-top: 1rem;
}

.req {
  color: var(--p-red-500, #ef4444);
}

.counter {
  margin: 0;
  text-align: right;
  font-size: 0.7rem;
  color: var(--text-muted);
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.actions__submit {
  min-width: 14rem;
}
</style>
