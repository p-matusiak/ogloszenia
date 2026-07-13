import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { useDebouncedCallback } from '@/composables/useDebouncedCallback'

describe('useDebouncedCallback', () => {
  beforeEach(() => vi.useFakeTimers())
  afterEach(() => vi.useRealTimers())

  it('runs once after the pause, with the last arguments', () => {
    const spy = vi.fn()
    const debounced = useDebouncedCallback(spy, 300)

    debounced('r')
    debounced('ro')
    debounced('rower')

    expect(spy).not.toHaveBeenCalled()

    vi.advanceTimersByTime(300)

    expect(spy).toHaveBeenCalledTimes(1)
    expect(spy).toHaveBeenCalledWith('rower')
  })

  it('does not fire before the delay elapses', () => {
    const spy = vi.fn()
    const debounced = useDebouncedCallback(spy, 300)

    debounced()
    vi.advanceTimersByTime(299)

    expect(spy).not.toHaveBeenCalled()
  })

  it('can be cancelled', () => {
    const spy = vi.fn()
    const debounced = useDebouncedCallback(spy, 300)

    debounced()
    debounced.cancel()
    vi.advanceTimersByTime(1000)

    expect(spy).not.toHaveBeenCalled()
  })
})
