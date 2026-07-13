/**
 * Pełne złote: „1 850 zł”. Grosze pokazujemy tylko wtedy, gdy istnieją.
 * `useGrouping: true` jest konieczne (Intl normalizuje je do „always”), bo
 * polski locale domyślnie nie grupuje liczb czterocyfrowych i dałby „1850 zł”.
 */
const wholePriceFormatter = new Intl.NumberFormat('pl-PL', {
  style: 'currency',
  currency: 'PLN',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
  useGrouping: true,
})

const exactPriceFormatter = new Intl.NumberFormat('pl-PL', {
  style: 'currency',
  currency: 'PLN',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
  useGrouping: true,
})

const dateFormatter = new Intl.DateTimeFormat('pl-PL', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
})

/** Price is optional on an ad, and "0 zł" is not the same as "no price". */
export function formatPrice(price: number | null): string {
  if (price === null) {
    return 'Cena do uzgodnienia'
  }

  return Number.isInteger(price)
    ? wholePriceFormatter.format(price)
    : exactPriceFormatter.format(price)
}

export function formatDate(iso: string | null): string {
  if (iso === null) {
    return '—'
  }

  const date = new Date(iso)

  return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date)
}

const timeFormatter = new Intl.DateTimeFormat('pl-PL', { hour: '2-digit', minute: '2-digit' })
const shortDateFormatter = new Intl.DateTimeFormat('pl-PL', { day: 'numeric', month: 'short' })

/**
 * „Dodano dzisiaj, 10:23”. Porównujemy dni kalendarzowe, a nie odstęp
 * 24-godzinny: ogłoszenie z 23:50 wczoraj nie jest „dzisiaj” o 00:10.
 */
export function formatAddedAt(iso: string | null, now: Date = new Date()): string {
  if (iso === null) {
    return '—'
  }

  const added = new Date(iso)
  if (Number.isNaN(added.getTime())) {
    return '—'
  }

  const days = calendarDaysApart(added, now)
  const time = timeFormatter.format(added)

  if (days === 0) {
    return `Dodano dzisiaj, ${time}`
  }
  if (days === 1) {
    return `Dodano wczoraj, ${time}`
  }

  return `Dodano ${shortDateFormatter.format(added)}, ${time}`
}

function calendarDaysApart(earlier: Date, later: Date): number {
  const startOfDay = (date: Date): number =>
    new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime()

  return Math.round((startOfDay(later) - startOfDay(earlier)) / 86_400_000)
}

/**
 * Polska odmiana: 1 ogłoszenie, 2–4 ogłoszenia, 5+ ogłoszeń,
 * ale 12–14 to wyjątek i wracają do formy „ogłoszeń”.
 */
export function formatAdCount(count: number): string {
  const lastDigit = count % 10
  const lastTwoDigits = count % 100

  if (count === 1) {
    return '1 ogłoszenie'
  }

  const isFewForm =
    lastDigit >= 2 && lastDigit <= 4 && !(lastTwoDigits >= 12 && lastTwoDigits <= 14)

  return `${count} ${isFewForm ? 'ogłoszenia' : 'ogłoszeń'}`
}

/** Whole days from now until `iso`; negative once the date has passed. */
export function daysUntil(iso: string | null): number | null {
  if (iso === null) {
    return null
  }

  const target = new Date(iso).getTime()
  if (Number.isNaN(target)) {
    return null
  }

  const millisecondsPerDay = 86_400_000

  return Math.ceil((target - Date.now()) / millisecondsPerDay)
}
