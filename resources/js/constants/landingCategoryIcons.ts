/** Ikony PrimeIcons dla kafelków kategorii na stronie głównej. */
export const LANDING_CATEGORY_ICONS: Record<string, string> = {
  motoryzacja: 'pi pi-car',
  nieruchomosci: 'pi pi-home',
  elektronika: 'pi pi-mobile',
  'dom-i-ogrod': 'pi pi-sun',
  moda: 'pi pi-tag',
  'dla-dzieci': 'pi pi-heart',
  'sport-i-hobby': 'pi pi-star',
  praca: 'pi pi-briefcase',
  uslugi: 'pi pi-wrench',
}

export function landingCategoryIcon(slug: string): string {
  return LANDING_CATEGORY_ICONS[slug] ?? 'pi pi-box'
}