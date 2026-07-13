import { client } from '@/api/client'
import type {
  Ad,
  AdReport,
  AdStatus,
  AdSummary,
  Category,
  Paginated,
  ReportStatus,
  ResourceEnvelope,
} from '@/types/api'

const BASE = '/api/v1/admin'

export interface AdminSettings {
  auto_approve_ads: boolean
}

export interface CategoryPayload {
  name: string
  parent_id: number | null
  position?: number
  is_visible?: boolean
}

export async function fetchAdminAds(status?: AdStatus, page = 1): Promise<Paginated<AdSummary>> {
  const { data } = await client.get<Paginated<AdSummary>>(`${BASE}/ads`, {
    params: { status, page },
  })

  return data
}

export async function approveAd(slug: string): Promise<Ad> {
  const { data } = await client.post<ResourceEnvelope<Ad>>(`${BASE}/ads/${slug}/approve`)

  return data.data
}

export async function rejectAd(slug: string, reason: string): Promise<Ad> {
  const { data } = await client.post<ResourceEnvelope<Ad>>(`${BASE}/ads/${slug}/reject`, { reason })

  return data.data
}

export async function deleteAdAsAdmin(slug: string): Promise<void> {
  await client.delete(`${BASE}/ads/${slug}`)
}

export async function fetchAdminCategories(): Promise<Category[]> {
  const { data } = await client.get<ResourceEnvelope<Category[]>>(`${BASE}/categories`)

  return data.data
}

export async function createCategory(payload: CategoryPayload): Promise<Category> {
  const { data } = await client.post<ResourceEnvelope<Category>>(`${BASE}/categories`, payload)

  return data.data
}

export async function updateCategory(slug: string, payload: CategoryPayload): Promise<Category> {
  const { data } = await client.put<ResourceEnvelope<Category>>(`${BASE}/categories/${slug}`, payload)

  return data.data
}

export async function deleteCategory(slug: string): Promise<void> {
  await client.delete(`${BASE}/categories/${slug}`)
}

export async function fetchReports(page = 1): Promise<Paginated<AdReport>> {
  const { data } = await client.get<Paginated<AdReport>>(`${BASE}/reports`, { params: { page } })

  return data
}

export async function resolveReport(id: number, status: ReportStatus): Promise<void> {
  await client.put(`${BASE}/reports/${id}`, { status })
}

export async function fetchSettings(): Promise<AdminSettings> {
  const { data } = await client.get<AdminSettings>(`${BASE}/settings`)

  return data
}

export async function updateSettings(autoApprove: boolean): Promise<AdminSettings> {
  const { data } = await client.put<AdminSettings>(`${BASE}/settings`, {
    auto_approve_ads: autoApprove,
  })

  return data
}
