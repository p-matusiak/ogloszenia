import { computed, readonly, ref, type ComputedRef, type Ref } from 'vue'

const STORAGE_KEY = 'zunto:theme'
const DARK_CLASS = 'dark'

type Theme = 'light' | 'dark'

/** Poza modułem, żeby wszystkie instancje widziały ten sam motyw. */
const current = ref<Theme>('light')
let isInitialised = false

interface ThemeControls {
  theme: Readonly<Ref<Theme>>
  isDark: ComputedRef<boolean>
  toggle: () => void
  initialise: () => void
}

const isDark = computed(() => current.value === 'dark')

export function useTheme(): ThemeControls {
  return {
    theme: readonly(current),
    isDark,
    toggle,
    initialise,
  }
}

/** Wybór użytkownika ma pierwszeństwo nad preferencją systemu. */
export function resolveInitialTheme(stored: string | null, prefersDark: boolean): Theme {
  if (stored === 'light' || stored === 'dark') {
    return stored
  }

  return prefersDark ? 'dark' : 'light'
}

function initialise(): void {
  if (isInitialised) {
    return
  }

  isInitialised = true

  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  apply(resolveInitialTheme(localStorage.getItem(STORAGE_KEY), prefersDark))
}

function toggle(): void {
  apply(current.value === 'dark' ? 'light' : 'dark')
  localStorage.setItem(STORAGE_KEY, current.value)
}

function apply(theme: Theme): void {
  current.value = theme
  document.documentElement.classList.toggle(DARK_CLASS, theme === 'dark')
}
