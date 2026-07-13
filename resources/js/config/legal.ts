/**
 * Dane wchodzące do regulaminu i polityki prywatności. Trzymane w jednym
 * miejscu, bo powtarzają się w obu dokumentach, a rozbieżność między nimi
 * jest wadą prawną, nie kosmetyczną.
 *
 * Dane rejestrowe pochodzą z wykazu podatników VAT Ministerstwa Finansów
 * (tzw. biała lista), stan na 10.07.2026.
 */
export const OPERATOR = {
  name: 'Paweł Matusiak',
  legalForm: 'osoba fizyczna prowadząca działalność gospodarczą',
  street: 'ul. Wielkopolska 38/2',
  city: '43-300 Bielsko-Biała',
  country: 'Polska',
  nip: '9372553467',
  regon: '380242220',

  // TODO: uzupełnić przed publikacją.
  email: 'kontakt@example.pl',
} as const

export const SITE = {
  name: 'Ogłoszenia',
  domain: 'ogloszenia.gesoft.pl',
} as const

/**
 * Podmioty przetwarzające. RODO wymaga wskazania kategorii odbiorców danych,
 * a hosting i poczta wychodząca to jedyni odbiorcy w tej aplikacji — nie ma
 * tu analityki, reklam ani bramek płatności.
 *
 * TODO: uzupełnić przed publikacją nazwami rzeczywistych dostawców.
 */
export const PROCESSORS = {
  hosting: '[nazwa i siedziba dostawcy hostingu]',
  email: '[nazwa i siedziba dostawcy poczty wychodzącej]',
} as const

export const LAST_UPDATED = '10 lipca 2026'

/** Reguły z `config/ads.php`; dokument nie może się z nimi rozjechać. */
export const AD_RULES = {
  lifetimeDays: 30,
  dailyLimitPerUser: 5,
  maxImages: 12,
  maxImageMb: 10,
  imageFormats: 'JPG, PNG lub WebP',
} as const
