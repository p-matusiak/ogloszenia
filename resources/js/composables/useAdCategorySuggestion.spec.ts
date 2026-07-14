import { ref } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { suggestAdCategory } from '@/api/modules/v1/ads'
import { useAdCategorySuggestion } from '@/composables/useAdCategorySuggestion'
import { emptyAdForm } from '@/composables/useAdSubmission'
import type { AdFormValues } from '@/types/api'

vi.mock('@/api/modules/v1/ads', () => ({
  suggestAdCategory: vi.fn(),
}))

describe('useAdCategorySuggestion', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.mocked(suggestAdCategory).mockResolvedValue({
      category_id: 42,
      available: true,
    })
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.clearAllMocks()
  })

  it('suggests a category after debounce when the title is long enough', async () => {
    const values = ref<AdFormValues>(emptyAdForm())
    const patches: Array<Partial<AdFormValues>> = []

    useAdCategorySuggestion(values, (partial) => {
      values.value = { ...values.value, ...partial }
      patches.push(partial)
    })

    values.value = { ...values.value, title: 'Sprzedam iPhone 13' }

    vi.advanceTimersByTime(600)
    await vi.runOnlyPendingTimersAsync()

    expect(suggestAdCategory).toHaveBeenCalledWith('Sprzedam iPhone 13')
    expect(patches).toContainEqual({ category_id: 42 })
  })

  it('does not overwrite a manually chosen category', async () => {
    const values = ref<AdFormValues>({ ...emptyAdForm(), category_id: 7 })
    const patches: Array<Partial<AdFormValues>> = []

    useAdCategorySuggestion(values, (partial) => {
      values.value = { ...values.value, ...partial }
      patches.push(partial)
    })

    values.value = { ...values.value, title: 'Sprzedam iPhone 13' }

    vi.advanceTimersByTime(600)
    await vi.runOnlyPendingTimersAsync()

    expect(suggestAdCategory).not.toHaveBeenCalled()
    expect(patches).toHaveLength(0)
  })
})