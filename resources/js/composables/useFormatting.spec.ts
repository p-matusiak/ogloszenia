import { describe, expect, it } from 'vitest'

import {
  daysUntil,
  formatAdCount,
  formatAddedAt,
  formatDate,
  formatPrice,
} from '@/composables/useFormatting'

describe('formatAddedAt', () => {
  const now = new Date('2026-07-09T08:00:00')

  it('says "dzisiaj" for the same calendar day', () => {
    expect(formatAddedAt('2026-07-09T06:30:00', now)).toContain('Dodano dzisiaj')
  })

  it('says "wczoraj" for the previous calendar day, even minutes earlier', () => {
    // 23:50 yesterday is not "today" just because it is under 24 hours ago.
    expect(formatAddedAt('2026-07-08T23:50:00', now)).toContain('Dodano wczoraj')
  })

  it('falls back to a short date further back', () => {
    const formatted = formatAddedAt('2026-07-04T16:30:00', now)

    expect(formatted).toContain('Dodano')
    expect(formatted).not.toContain('dzisiaj')
    expect(formatted).not.toContain('wczoraj')
  })

  it('handles a missing or broken date', () => {
    expect(formatAddedAt(null, now)).toBe('—')
    expect(formatAddedAt('nie-data', now)).toBe('—')
  })
})

describe('formatAdCount', () => {
  it('uses the singular for exactly one', () => {
    expect(formatAdCount(1)).toBe('1 ogłoszenie')
  })

  it('uses the "few" form for 2-4', () => {
    expect(formatAdCount(2)).toBe('2 ogłoszenia')
    expect(formatAdCount(23)).toBe('23 ogłoszenia')
  })

  it('uses the "many" form for 5 and up, and for the 12-14 exception', () => {
    expect(formatAdCount(0)).toBe('0 ogłoszeń')
    expect(formatAdCount(5)).toBe('5 ogłoszeń')
    expect(formatAdCount(12)).toBe('12 ogłoszeń')
    expect(formatAdCount(14)).toBe('14 ogłoszeń')
    expect(formatAdCount(112)).toBe('112 ogłoszeń')
  })
})

describe('formatPrice', () => {
  it('says the price is negotiable when the ad has none', () => {
    expect(formatPrice(null)).toBe('Cena do uzgodnienia')
  })

  it('renders zero as a real price rather than a missing one', () => {
    expect(formatPrice(0)).not.toBe('Cena do uzgodnienia')
    expect(formatPrice(0)).toContain('0')
  })

  it('formats whole złoty without decimals, grouped by thousands', () => {
    // Makieta pokazuje „1 850 zł”, nie „1850 zł” ani „1 850,00 zł”.
    // Polski locale sam z siebie nie grupuje liczb czterocyfrowych.
    const formatted = formatPrice(1850)

    expect(formatted).not.toContain(',')
    expect(formatted.replace(/\s|\u00a0|\u202f/g, ' ')).toContain('1 850')
    expect(formatted).toContain('zł')
  })

  it('keeps the grosze when they are not zero', () => {
    expect(formatPrice(1999.99)).toContain('1999,99'.slice(-5))
  })
})

describe('formatDate', () => {
  it('renders an em dash when there is no date', () => {
    expect(formatDate(null)).toBe('—')
  })

  it('renders an em dash for an unparseable date', () => {
    expect(formatDate('nie-data')).toBe('—')
  })

  it('formats an ISO timestamp as a Polish date', () => {
    expect(formatDate('2026-07-09T10:30:00+00:00')).toBe('09.07.2026')
  })
})

describe('daysUntil', () => {
  it('returns null without a date', () => {
    expect(daysUntil(null)).toBeNull()
  })

  it('is positive for a future date and negative once it has passed', () => {
    const future = new Date(Date.now() + 5 * 86_400_000).toISOString()
    const past = new Date(Date.now() - 5 * 86_400_000).toISOString()

    expect(daysUntil(future)).toBeGreaterThan(0)
    expect(daysUntil(past)).toBeLessThan(0)
  })
})
