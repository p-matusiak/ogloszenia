import { expect, test } from '@playwright/test'

test.describe('Autoryzacja', () => {
  test('otwiera formularz logowania', async ({ page }) => {
    await page.goto('/logowanie')

    await expect(page.getByRole('heading', { name: 'Zaloguj się' })).toBeVisible()
    await expect(page.getByLabel('Adres e-mail')).toBeVisible()
    await expect(page.getByLabel('Hasło')).toBeVisible()
  })

  test('przekierowuje niezalogowanego z chronionej trasy', async ({ page }) => {
    await page.goto('/dodaj-ogloszenie')

    await expect(page).toHaveURL(/\/logowanie/)
  })
})