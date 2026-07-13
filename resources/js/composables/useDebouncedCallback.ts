import { getCurrentScope, onScopeDispose } from 'vue'

interface DebouncedCallback<TArgs extends unknown[]> {
  (...args: TArgs): void
  cancel: () => void
}

/**
 * Odracza wywołanie do czasu, aż wpisywanie ucichnie na `delayMs`.
 * Timer jest kasowany przy zniszczeniu komponentu, żeby nie strzelił
 * po odmontowaniu i nie dotknął martwego stanu.
 */
export function useDebouncedCallback<TArgs extends unknown[]>(
  callback: (...args: TArgs) => void,
  delayMs = 300,
): DebouncedCallback<TArgs> {
  let timer: ReturnType<typeof setTimeout> | undefined

  function cancel(): void {
    if (timer !== undefined) {
      clearTimeout(timer)
      timer = undefined
    }
  }

  const debounced = (...args: TArgs): void => {
    cancel()
    timer = setTimeout(() => callback(...args), delayMs)
  }

  debounced.cancel = cancel

  // Poza komponentem (np. w teście) nie ma scope'u do sprzątania.
  if (getCurrentScope()) {
    onScopeDispose(cancel)
  }

  return debounced
}
