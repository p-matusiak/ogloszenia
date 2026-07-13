/** Wizualny wariant chipa; odwzorowuje kolory z makiety. */
export type ChipTone = 'neutral' | 'info' | 'success'

/** Usuwalny chip reprezentujący jeden aktywny filtr wyszukiwania. */
export interface FilterChip {
  key: string
  label: string
  tone?: ChipTone
}
