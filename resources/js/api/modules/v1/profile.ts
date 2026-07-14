import { client, ensureCsrfCookie } from '@/api/client'
import type { ResourceEnvelope, User } from '@/types/api'

const BASE = '/api/v1'

export interface ProfilePayload {
  name: string
  phone: string
  bio: string
  avatar: File | null
  remove_avatar: boolean
}

export async function updateProfile(payload: ProfilePayload): Promise<User> {
  await ensureCsrfCookie()

  const form = new FormData()
  form.set('name', payload.name)
  form.set('phone', payload.phone)
  form.set('bio', payload.bio)
  form.set('remove_avatar', payload.remove_avatar ? '1' : '0')

  if (payload.avatar) {
    form.set('avatar', payload.avatar)
  }

  const { data } = await client.post<ResourceEnvelope<User>>(`${BASE}/auth/profile`, form)

  return data.data
}

export async function deleteAccount(): Promise<void> {
  await ensureCsrfCookie()
  await client.delete(`${BASE}/auth/account`)
}