import { expect, test } from '@playwright/test'

const outDir = 'storage/visual-audit'

test.describe('Audyt wizualny UI', () => {
  test('zrzuty ekranu — desktop i mobile', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 })
    await page.goto('/')
    await expect(
      page.getByRole('heading', { name: /Kupuj, sprzedawaj i znajdź oferty lokalnie/i }),
    ).toBeVisible({
      timeout: 20_000,
    })
    await page.waitForTimeout(1500)
    await page.screenshot({ path: `${outDir}/landing-desktop.png`, fullPage: true })

    await page.goto('/logowanie')
    await page.waitForTimeout(1000)
    await page.screenshot({ path: `${outDir}/login-desktop.png`, fullPage: true })

    const adLink = page.locator('a[href^="/ogloszenie/"]').first()
    await page.goto('/')
    if (await adLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await adLink.click()
      await page.waitForTimeout(1500)
      await page.screenshot({ path: `${outDir}/ad-detail-desktop.png`, fullPage: true })
    }
  })

  test('zrzut ekranu — mobile', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 })
    await page.goto('/')
    await expect(
      page.getByRole('heading', { name: /Kupuj, sprzedawaj i znajdź oferty lokalnie/i }),
    ).toBeVisible({
      timeout: 20_000,
    })
    await page.waitForTimeout(1500)
    await page.screenshot({ path: `${outDir}/landing-mobile.png`, fullPage: true })
  })

  test('zrzut ekranu — tryb ciemny', async ({ page }) => {
    await page.addInitScript(() => {
      localStorage.setItem('zunto:theme', 'dark')
      document.documentElement.classList.add('dark')
    })
    await page.setViewportSize({ width: 1440, height: 900 })
    await page.goto('/')
    await expect(
      page.getByRole('heading', { name: /Kupuj, sprzedawaj i znajdź oferty lokalnie/i }),
    ).toBeVisible({
      timeout: 20_000,
    })
    await page.waitForTimeout(1500)
    await page.screenshot({ path: `${outDir}/landing-desktop-dark.png`, fullPage: false })

    await page.setViewportSize({ width: 390, height: 844 })
    await page.goto('/')
    await page.waitForTimeout(1500)
    await page.screenshot({ path: `${outDir}/landing-mobile-dark.png`, fullPage: false })
  })
})