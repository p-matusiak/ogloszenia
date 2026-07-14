import { afterEach, describe, expect, it } from 'vitest'

import { useListingLayout } from '@/composables/useListingLayout'

describe('useListingLayout', () => {
  afterEach(() => {
    localStorage.clear()
  })

  it('defaults to grid when nothing is stored', () => {
    const { layout } = useListingLayout()

    expect(layout.value).toBe('grid')
  })

  it('restores the saved layout from localStorage', () => {
    localStorage.setItem('zunto:listing-layout', 'list')

    const { layout } = useListingLayout()

    expect(layout.value).toBe('list')
  })

  it('persists layout changes', () => {
    const { layout, setLayout } = useListingLayout()

    setLayout('list')

    expect(layout.value).toBe('list')
    expect(localStorage.getItem('zunto:listing-layout')).toBe('list')
  })

  it('toggles between grid and list', () => {
    const { layout, toggleLayout } = useListingLayout()

    toggleLayout()
    expect(layout.value).toBe('list')

    toggleLayout()
    expect(layout.value).toBe('grid')
  })
})