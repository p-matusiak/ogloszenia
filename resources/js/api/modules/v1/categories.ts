import { client } from '@/api/client'
import type { Category, ResourceEnvelope } from '@/types/api'

const BASE = '/api/v1'

/** Visible roots, each with its visible children. */
export async function fetchCategoryTree(): Promise<Category[]> {
  const { data } = await client.get<ResourceEnvelope<Category[]>>(`${BASE}/categories`)

  return data.data
}
