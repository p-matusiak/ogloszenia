import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import * as authApi from '@/api/modules/v1/auth'
import * as profileApi from '@/api/modules/v1/profile'
import type { Credentials, Registration } from '@/api/modules/v1/auth'
import type { ProfilePayload } from '@/api/modules/v1/profile'
import type { User } from '@/types/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isLoading = ref(false)
  /** Distinguishes "not signed in" from "we have not asked the server yet". */
  const isResolved = ref(false)

  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => user.value?.is_admin === true)
  const isEmailVerified = computed(() => user.value?.is_email_verified === true)

  async function register(payload: Registration): Promise<void> {
    isLoading.value = true
    try {
      user.value = await authApi.register(payload)
      isResolved.value = true
    } finally {
      isLoading.value = false
    }
  }

  async function login(payload: Credentials): Promise<void> {
    isLoading.value = true
    try {
      user.value = await authApi.login(payload)
      isResolved.value = true
    } finally {
      isLoading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } finally {
      user.value = null
      isResolved.value = true
    }
  }

  async function updateProfile(payload: ProfilePayload): Promise<void> {
    isLoading.value = true
    try {
      user.value = await profileApi.updateProfile(payload)
      isResolved.value = true
    } finally {
      isLoading.value = false
    }
  }

  async function resendVerification(): Promise<void> {
    isLoading.value = true
    try {
      await authApi.resendVerificationEmail()
    } finally {
      isLoading.value = false
    }
  }

  /** Called once on boot; a 401 simply means the visitor is a guest. */
  async function resolve(): Promise<void> {
    if (isResolved.value) {
      return
    }

    try {
      user.value = await authApi.currentUser()
    } catch {
      user.value = null
    } finally {
      isResolved.value = true
    }
  }

  /**
   * The activation link is consumed by the backend, not by the SPA, so the
   * cached user still reads as unverified when the visitor lands back here.
   */
  async function refresh(): Promise<void> {
    isResolved.value = false
    await resolve()
  }

  return {
    user,
    isLoading,
    isResolved,
    isAuthenticated,
    isAdmin,
    isEmailVerified,
    register,
    login,
    logout,
    updateProfile,
    resendVerification,
    resolve,
    refresh,
  }
})
