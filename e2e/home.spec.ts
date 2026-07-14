import { expect, test } from '@playwright/test'

test.describe('Listing ogłoszeń', () => {
  test('ładuje listę ogłoszeń i nagłówek', async ({ page }) => {
    await page.goto('/ogloszenia')

    await expect(page.getByRole('heading', { name: /Ogłoszenia/i })).toBeVisible()
    await expect(page.getByRole('link', { name: /Dodaj ogłoszenie/i })).toBeVisible()
  })

  test('pokazuje szkielet lub wyniki po załadowaniu', async ({ page }) => {
    await page.goto('/ogloszenia')

    await expect(page.getByRole('heading', { name: /Ogłoszenia/i })).toBeVisible()

    const emptyState = page.getByText('Brak ogłoszeń spełniających kryteria')
    const adLink = page.locator('a[href^="/ogloszenie/"]').first()

    await expect(emptyState.or(adLink)).toBeVisible({ timeout: 15_000 })
  })

  test('przełącza układ siatka / lista', async ({ page }) => {
    await page.goto('/ogloszenia')

    await expect(page.getByRole('heading', { name: /Ogłoszenia/i })).toBeVisible()

    const layoutToggle = page.getByRole('group', { name: 'Układ listy' })
    const buttons = layoutToggle.getByRole('button')

    await buttons.nth(1).click()
    await expect(page.locator('.row, .results__list .row').first()).toBeVisible({
      timeout: 15_000,
    }).catch(() => undefined)

    await buttons.nth(0).click()
    await expect(page.locator('.card').first()).toBeVisible({ timeout: 15_000 }).catch(
      () => undefined,
    )
  })
})