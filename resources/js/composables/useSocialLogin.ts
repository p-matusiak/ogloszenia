import { useRoute } from 'vue-router'

export type SocialProvider = 'google' | 'facebook'

export function useSocialLogin(): { start: (provider: SocialProvider) => void } {
  const route = useRoute()

  function start(provider: SocialProvider): void {
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'

    window.location.assign(
      `/auth/${provider}/redirect?redirect=${encodeURIComponent(redirect)}`,
    )
  }

  return { start }
}