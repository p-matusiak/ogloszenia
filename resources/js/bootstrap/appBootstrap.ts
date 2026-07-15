import type { User } from '@/types/api'

interface AppBootstrapPayload {
  user: User | null
}

export function readAppBootstrap(): AppBootstrapPayload {
  const element = document.getElementById('app-bootstrap')

  if (!(element instanceof HTMLScriptElement) || element.textContent === null) {
    return { user: null }
  }

  try {
    const parsed: unknown = JSON.parse(element.textContent)

    if (typeof parsed === 'object' && parsed !== null && 'user' in parsed) {
      return {
        user: isUserLike(parsed.user) ? parsed.user : null,
      }
    }
  } catch {
    return { user: null }
  }

  return { user: null }
}

function isUserLike(value: unknown): value is User {
  return typeof value === 'object' && value !== null && 'id' in value && 'email' in value
}
