import { defineStore } from 'pinia'
import { ref } from 'vue'
import { ApiError } from '../services/api'
import { authService, type LoginPayload, type RegisterPayload, type User } from '../services/auth.service'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const status = ref<'idle' | 'loading' | 'ready'>('idle')

  async function fetchMe(): Promise<void> {
    status.value = 'loading'
    try {
      user.value = await authService.me()
    } catch (error) {
      user.value = null
      if (!(error instanceof ApiError && error.status === 401)) {
        throw error
      }
    } finally {
      status.value = 'ready'
    }
  }

  async function register(payload: RegisterPayload): Promise<void> {
    user.value = await authService.register(payload)
  }

  async function login(payload: LoginPayload): Promise<void> {
    user.value = await authService.login(payload)
  }

  async function logout(): Promise<void> {
    await authService.logout()
    user.value = null
  }

  return { user, status, fetchMe, register, login, logout }
})
