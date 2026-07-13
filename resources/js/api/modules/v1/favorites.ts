import { client } from '@/api/client'
import type { AdSummary, Paginated } from '@/types/api'

const BASE = '/api/v1'

export async function addFavorite(slug: string): Promise<void> {
  await client.post(`${BASE}/ads/${slug}/favorite`)
}

export async function removeFavorite(slug: string): Promise<void> {
  await client.delete(`${BASE}/ads/${slug}/favorite`)
}

export async function fetchFavoriteIds(): Promise<number[]> {
  const { data } = await client.get<{ data: number[] }>(`${BASE}/my/favorites/ids`)

  return data.data
}

export async function fetchFavorites(page = 1): Promise<Paginated<AdSummary>> {
  const { data } = await client.get<Paginated<AdSummary>>(`${BASE}/my/favorites`, {
    params: { page },
  })

  return data
}
