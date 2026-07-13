import { describe, expect, it } from 'vitest'

import { resolveInitialTheme } from '@/composables/useTheme'

describe('resolveInitialTheme', () => {
  it('prefers an explicit choice over the system preference', () => {
    expect(resolveInitialTheme('light', true)).toBe('light')
    expect(resolveInitialTheme('dark', false)).toBe('dark')
  })

  it('falls back to the system preference when nothing was chosen', () => {
    expect(resolveInitialTheme(null, true)).toBe('dark')
    expect(resolveInitialTheme(null, false)).toBe('light')
  })

  it('ignores a corrupted stored value', () => {
    expect(resolveInitialTheme('banana', true)).toBe('dark')
  })
})
