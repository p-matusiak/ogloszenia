import { describe, expect, it } from 'vitest'

import { buildDocumentTitle } from '@/composables/usePageTitle'

describe('buildDocumentTitle', () => {
  it('uses the bare site name when the page contributes no title', () => {
    expect(buildDocumentTitle(undefined, 'Zunto')).toBe('Zunto')
  })

  it('suffixes the site name to a page title', () => {
    expect(buildDocumentTitle('Regulamin serwisu', 'Zunto')).toBe(
      'Regulamin serwisu | Zunto',
    )
  })

  it('treats a whitespace-only title as no title at all', () => {
    // Tytuł ogłoszenia trafia tu prosto z API i bywa pusty zanim odpowiedź dojdzie.
    expect(buildDocumentTitle('   ', 'Zunto')).toBe('Zunto')
  })
})
