import { expect, test } from '@playwright/test'

test.describe('Strona główna (landing)', () => {
  test('ładuje hero i sekcje', async ({ page }) => {
    await page.goto('/')

    await expect(
      page.getByRole('heading', { name: /Kupuj, sprzedawaj i znajdź oferty lokalnie/i }),
    ).toBeVisible()
    await expect(page.getByRole('heading', { name: /^Kategorie$/i })).toBeVisible()
    await expect(page.getByRole('heading', { name: /Polecane ogłoszenia/i })).toBeVisible()
    await expect(page.getByRole('link', { name: /Dodaj ogłoszenie/i })).toBeVisible()
  })

  test('lokalizacja z hero ustawia filtry geo w adresie', async ({ page }) => {
    await page.goto('/')

    const locationField = page.getByRole('search').getByLabel('Lokalizacja')
    await locationField.fill('Warszawa')
    await page.getByRole('button', { name: /^Warszawa/i }).first().click()
    await page.getByRole('button', { name: /^Szukaj$/i }).first().click()

    await expect(page).toHaveURL(/\/ogloszenia/)
    await expect(page).toHaveURL(/lat=/)
    await expect(page).toHaveURL(/lng=/)
    await expect(page).toHaveURL(/radius_km=/)
  })

  test('wyszukiwanie z hero przenosi do listingu', async ({ page }) => {
    await page.goto('/')

    await page.getByRole('search').getByLabel('Czego szukasz?').fill('laptop')
    await page.getByRole('button', { name: /^Szukaj$/i }).first().click()

    await expect(page).toHaveURL(/\/ogloszenia/)
    await expect(page.getByRole('heading', { name: /Ogłoszenia/i })).toBeVisible()
  })
})