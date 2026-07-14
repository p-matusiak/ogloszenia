import { expect, test } from '@playwright/test'

test.describe('Wyszukiwanie', () => {
  test('aktualizuje adres po wpisaniu frazy', async ({ page }) => {
    await page.goto('/ogloszenia')

    const searchField = page
      .getByRole('search')
      .filter({ visible: true })
      .getByLabel('Czego szukasz?')
    await searchField.fill('rower')
    await searchField.press('Enter')

    await expect(page).toHaveURL(/q=rower/, { timeout: 5_000 })
  })

  test('pokazuje stan pusty dla frazy bez wyników', async ({ page }) => {
    await page.goto('/ogloszenia?q=zzzxxyyynoresults12345')

    await expect(page.getByText('Brak ogłoszeń spełniających kryteria')).toBeVisible({
      timeout: 15_000,
    })
  })
})