import { expect, test } from '@playwright/test'

test.describe('API smoke', () => {
  test('zwraca listę ogłoszeń', async ({ request }) => {
    const response = await request.get('/api/v1/ads')

    expect(response.ok()).toBeTruthy()

    const body: { data: unknown[] } = await response.json()
    expect(Array.isArray(body.data)).toBeTruthy()
  })

  test('zwraca drzewo kategorii', async ({ request }) => {
    const response = await request.get('/api/v1/categories')

    expect(response.ok()).toBeTruthy()
  })

  test('wyszukiwanie z frazą nie zwraca 500', async ({ request }) => {
    const response = await request.get('/api/v1/ads?q=rower&sort=relevance')

    expect(response.status()).toBeLessThan(500)
  })
})