import { client } from '@/api/client'
import type { ResourceEnvelope, SellerProfile } from '@/types/api'

const BASE = '/api/v1'

export async function fetchSeller(sellerSlug: string): Promise<SellerProfile> {
  const { data } = await client.get<ResourceEnvelope<SellerProfile>>(`${BASE}/sellers/${sellerSlug}`)

  return data.data
}