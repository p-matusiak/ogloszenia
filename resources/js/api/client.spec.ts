import { AxiosError, AxiosHeaders } from 'axios'
import { describe, expect, it } from 'vitest'

import { domainError, errorMessage, isValidationError, validationErrors } from '@/api/client'

function axiosErrorWith(status: number, data: unknown): AxiosError {
  const error = new AxiosError('request failed')
  const headers = new AxiosHeaders()

  error.response = { status, data, statusText: '', headers, config: { headers } }

  return error
}

describe('validationErrors', () => {
  it('flattens Laravel 422 field errors to their first message', () => {
    const error = axiosErrorWith(422, {
      message: 'The given data was invalid.',
      errors: { title: ['Tytuł jest wymagany.', 'Za krótki.'], category_id: ['Wybierz subkategorię.'] },
    })

    expect(isValidationError(error)).toBe(true)
    expect(validationErrors(error)).toEqual({
      title: 'Tytuł jest wymagany.',
      category_id: 'Wybierz subkategorię.',
    })
  })

  it('returns nothing for a non-validation failure', () => {
    expect(validationErrors(axiosErrorWith(500, {}))).toEqual({})
    expect(validationErrors(new Error('boom'))).toEqual({})
  })
})

describe('domainError', () => {
  it('reads the shared error envelope', () => {
    const error = axiosErrorWith(429, {
      code: 'ADS_DAILY_LIMIT_REACHED',
      message: 'You may publish at most 5 ads per day.',
      details: { limit: 5 },
    })

    expect(domainError(error)?.code).toBe('ADS_DAILY_LIMIT_REACHED')
    expect(domainError(error)?.details).toEqual({ limit: 5 })
  })

  it('does not mistake a 422 validation body for a domain error', () => {
    const error = axiosErrorWith(422, { message: 'invalid', errors: { title: ['wymagany'] } })

    expect(domainError(error)).toBeNull()
  })
})

describe('errorMessage', () => {
  it('prefers the domain error message', () => {
    const error = axiosErrorWith(422, {
      code: 'AD_NOT_REFRESHABLE',
      message: 'This ad cannot be refreshed yet.',
      details: {},
    })

    expect(errorMessage(error)).toBe('This ad cannot be refreshed yet.')
  })

  it('explains a 403 in Polish', () => {
    expect(errorMessage(axiosErrorWith(403, {}))).toBe('Nie masz uprawnień do tej operacji.')
  })

  it('falls back to the supplied message', () => {
    expect(errorMessage(new Error('boom'), 'Nie udało się.')).toBe('Nie udało się.')
  })
})
