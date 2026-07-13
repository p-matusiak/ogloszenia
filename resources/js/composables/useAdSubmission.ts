import { ref, type Ref } from 'vue'

import { errorMessage, validationErrors } from '@/api/client'
import type { Ad, AdFormValues } from '@/types/api'

export function emptyAdForm(): AdFormValues {
  return {
    title: '',
    description: '',
    category_id: null,
    price: null,
    is_negotiable: false,
    condition: null,
    delivery_methods: [],
    delivery_prices: {},
    location: '',
    district: '',
    use_custom_phone: false,
    contact_phone: '',
    accept_terms: false,
    images: [],
    removed_image_ids: [],
  }
}

export function adToForm(ad: Ad): AdFormValues {
  return {
    title: ad.title,
    description: ad.description,
    category_id: ad.category?.id ?? null,
    price: ad.price,
    is_negotiable: ad.is_negotiable,
    condition: ad.condition,
    delivery_methods: ad.delivery_methods,
    delivery_prices: { ...ad.delivery_prices },
    location: ad.location ?? '',
    district: ad.district ?? '',
    use_custom_phone: Boolean(ad.contact_phone),
    // Pełny numer nadpisania przychodzi tylko autorowi; null znaczy „z profilu”.
    contact_phone: ad.contact_phone ?? '',
    // Already accepted when the ad was first published.
    accept_terms: true,
    images: [],
    removed_image_ids: [],
  }
}

interface AdSubmission {
  errors: Ref<Record<string, string>>
  generalError: Ref<string | null>
  isSubmitting: Ref<boolean>
  submit: (action: () => Promise<Ad>) => Promise<Ad | null>
}

/**
 * Splits Laravel's two error shapes: 422 field errors bind to inputs, while a
 * domain error (429 daily limit, 409 category in use) is a single message.
 */
export function useAdSubmission(): AdSubmission {
  const errors = ref<Record<string, string>>({})
  const generalError = ref<string | null>(null)
  const isSubmitting = ref(false)

  async function submit(action: () => Promise<Ad>): Promise<Ad | null> {
    errors.value = {}
    generalError.value = null
    isSubmitting.value = true

    try {
      return await action()
    } catch (caught: unknown) {
      const fieldErrors = validationErrors(caught)

      if (Object.keys(fieldErrors).length > 0) {
        errors.value = fieldErrors
      } else {
        generalError.value = errorMessage(caught, 'Nie udało się zapisać ogłoszenia.')
      }

      return null
    } finally {
      isSubmitting.value = false
    }
  }

  return { errors, generalError, isSubmitting, submit }
}
