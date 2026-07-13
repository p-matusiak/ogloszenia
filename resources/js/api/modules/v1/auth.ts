import { client, ensureCsrfCookie } from '@/api/client'
import type { ResourceEnvelope, User } from '@/types/api'

const BASE = '/api/v1'

export interface Credentials {
  email: string
  password: string
}

export interface Registration extends Credentials {
  name: string
  password_confirmation: string
}

export async function register(payload: Registration): Promise<User> {
  await ensureCsrfCookie()
  const { data } = await client.post<ResourceEnvelope<User>>(`${BASE}/auth/register`, payload)

  return data.data
}

export async function login(payload: Credentials): Promise<User> {
  await ensureCsrfCookie()
  const { data } = await client.post<ResourceEnvelope<User>>(`${BASE}/auth/login`, payload)

  return data.data
}

export async function logout(): Promise<void> {
  await client.post(`${BASE}/auth/logout`)
}

export async function currentUser(): Promise<User> {
  const { data } = await client.get<ResourceEnvelope<User>>(`${BASE}/auth/me`)

  return data.data
}

/** Throws with an EMAIL_ALREADY_VERIFIED envelope if the address is confirmed. */
export async function resendVerificationEmail(): Promise<void> {
  await ensureCsrfCookie()
  await client.post(`${BASE}/auth/email/verification-notification`)
}

export async function requestPasswordReset(email: string): Promise<void> {
  await ensureCsrfCookie()
  await client.post(`${BASE}/auth/forgot-password`, { email })
}

export async function resetPassword(payload: {
  token: string
  email: string
  password: string
  password_confirmation: string
}): Promise<void> {
  await ensureCsrfCookie()
  await client.post(`${BASE}/auth/reset-password`, payload)
}
