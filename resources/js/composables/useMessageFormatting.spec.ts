import { describe, expect, it } from 'vitest'

import {
  formatMessageWhen,
  messageDayLabel,
  participantInitials,
} from '@/composables/useMessageFormatting'

describe('useMessageFormatting', () => {
  it('builds initials from a display name', () => {
    expect(participantInitials('Jan Kowalski')).toBe('JK')
  })

  it('formats today as time only', () => {
    const now = new Date()
    now.setHours(14, 30, 0, 0)

    expect(formatMessageWhen(now.toISOString())).toMatch(/14:30/)
  })

  it('labels today as Dziś', () => {
    expect(messageDayLabel(new Date().toISOString())).toBe('Dziś')
  })
})