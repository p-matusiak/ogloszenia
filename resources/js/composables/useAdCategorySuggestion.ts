import { ref, watch, type Ref } from 'vue'

import { suggestAdCategory } from '@/api/modules/v1/ads'
import type { AdFormValues } from '@/types/api'

const MIN_TITLE_LENGTH = 5
const DEBOUNCE_MS = 600

interface AdCategorySuggestionState {
  isSuggesting: Ref<boolean>
  isAiAvailable: Ref<boolean>
  wasSuggested: Ref<boolean>
}

export function useAdCategorySuggestion(
  values: Ref<AdFormValues>,
  patch: (partial: Partial<AdFormValues>) => void,
): AdCategorySuggestionState {
  const isSuggesting = ref(false)
  const isAiAvailable = ref(false)
  const wasSuggested = ref(false)
  const categoryChosenManually = ref(values.value.category_id !== null)
  let debounceTimer: ReturnType<typeof setTimeout> | null = null
  let latestRequestId = 0

  watch(
    () => values.value.category_id,
    (next, previous) => {
      if (next !== previous && next !== null) {
        categoryChosenManually.value = true
        wasSuggested.value = false
      }
    },
  )

  watch(
    () => values.value.title,
    (title) => {
      if (debounceTimer !== null) {
        clearTimeout(debounceTimer)
      }

      const trimmed = title.trim()

      if (trimmed.length < MIN_TITLE_LENGTH || categoryChosenManually.value) {
        return
      }

      debounceTimer = setTimeout(() => {
        void requestSuggestion(trimmed)
      }, DEBOUNCE_MS)
    },
  )

  async function requestSuggestion(title: string): Promise<void> {
    const requestId = ++latestRequestId

    isSuggesting.value = true

    try {
      const suggestion = await suggestAdCategory(title)

      if (requestId !== latestRequestId || categoryChosenManually.value) {
        return
      }

      isAiAvailable.value = suggestion.available

      if (suggestion.category_id !== null && values.value.category_id === null) {
        patch({ category_id: suggestion.category_id })
        wasSuggested.value = true
      }
    } catch {
      if (requestId === latestRequestId) {
        isAiAvailable.value = false
      }
    } finally {
      if (requestId === latestRequestId) {
        isSuggesting.value = false
      }
    }
  }

  return { isSuggesting, isAiAvailable, wasSuggested }
}