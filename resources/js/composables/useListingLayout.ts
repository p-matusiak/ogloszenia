import { ref } from 'vue'

export type ListingLayout = 'grid' | 'list'

const STORAGE_KEY = 'ogloszenia:listing-layout'

function readStored(): ListingLayout {
  if (typeof localStorage === 'undefined') {
    return 'grid'
  }

  const stored = localStorage.getItem(STORAGE_KEY)

  return stored === 'list' ? 'list' : 'grid'
}

export function useListingLayout(): {
  layout: ReturnType<typeof ref<ListingLayout>>
  setLayout: (value: ListingLayout) => void
  toggleLayout: () => void
} {
  const layout = ref<ListingLayout>(readStored())

  function setLayout(value: ListingLayout): void {
    layout.value = value
    localStorage.setItem(STORAGE_KEY, value)
  }

  function toggleLayout(): void {
    setLayout(layout.value === 'grid' ? 'list' : 'grid')
  }

  return { layout, setLayout, toggleLayout }
}