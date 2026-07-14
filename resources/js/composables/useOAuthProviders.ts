import { onMounted, ref } from 'vue'

import { client } from '@/api/client'

import type { SocialProvider } from './useSocialLogin'

export function useOAuthProviders(): {
  providers: ReturnType<typeof ref<SocialProvider[]>>
  isLoading: ReturnType<typeof ref<boolean>>
} {
  const providers = ref<SocialProvider[]>([])
  const isLoading = ref(true)

  onMounted(async () => {
    try {
      const { data } = await client.get<{ providers: SocialProvider[] }>(
        '/api/v1/auth/oauth-providers',
      )
      providers.value = data.providers
    } catch {
      providers.value = []
    } finally {
      isLoading.value = false
    }
  })

  return { providers, isLoading }
}