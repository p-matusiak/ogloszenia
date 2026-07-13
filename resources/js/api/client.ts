import axios, { type AxiosInstance, AxiosError } from 'axios'

import type { ApiErrorBody, ValidationErrorBody } from '@/types/api'

export const HTTP_UNAUTHORIZED = 401
export const HTTP_FORBIDDEN = 403
export const HTTP_NOT_FOUND = 404
export const HTTP_UNPROCESSABLE = 422
export const HTTP_TOO_MANY_REQUESTS = 429

export const client: AxiosInstance = axios.create({
  baseURL: '/',
  // Sanctum authenticates the SPA with a session cookie, so credentials must
  // travel with every request, and the XSRF cookie must be echoed back.
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
})

/** Laravel requires a CSRF cookie before the first stateful write. */
export async function ensureCsrfCookie(): Promise<void> {
  await client.get('/sanctum/csrf-cookie')
}

export function isValidationError(
  error: unknown,
): error is AxiosError<ValidationErrorBody> {
  return error instanceof AxiosError && error.response?.status === HTTP_UNPROCESSABLE
}

/** Field-name to first-message map, ready to bind to form inputs. */
export function validationErrors(error: unknown): Record<string, string> {
  if (!isValidationError(error) || !error.response) {
    return {}
  }

  const flattened: Record<string, string> = {}

  for (const [field, messages] of Object.entries(error.response.data.errors)) {
    const first = messages[0]
    if (first !== undefined) {
      flattened[field] = first
    }
  }

  return flattened
}

/**
 * Domain errors (429 daily limit, 422 AD_NOT_REFRESHABLE, 409 CATEGORY_IN_USE)
 * arrive in their own envelope rather than Laravel's validation shape.
 */
export function domainError(error: unknown): ApiErrorBody | null {
  if (!(error instanceof AxiosError) || !error.response) {
    return null
  }

  const body: unknown = error.response.data

  if (
    typeof body === 'object' &&
    body !== null &&
    'code' in body &&
    'message' in body &&
    typeof (body as ApiErrorBody).code === 'string'
  ) {
    return body as ApiErrorBody
  }

  return null
}

export function errorMessage(error: unknown, fallback = 'Wystąpił nieoczekiwany błąd.'): string {
  const domain = domainError(error)
  if (domain) {
    return domain.message
  }

  if (error instanceof AxiosError && error.response?.status === HTTP_FORBIDDEN) {
    return 'Nie masz uprawnień do tej operacji.'
  }

  return fallback
}
