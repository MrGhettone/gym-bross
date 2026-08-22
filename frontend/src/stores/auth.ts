import { defineStore } from 'pinia'
import { ref } from 'vue'
import { ApiError, setAuthToken } from '../services/api'
import { authService, type LoginPayload, type RegisterPayload, type User } from '../services/auth.service'

const TOKEN_STORAGE_KEY = 'gymbros_token'

function readStoredToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_STORAGE_KEY)
  } catch {
    return null
  }
}

function persistToken(token: string | null): void {
  try {
    if (token) {
      localStorage.setItem(TOKEN_STORAGE_KEY, token)
    } else {
      localStorage.removeItem(TOKEN_STORAGE_KEY)
    }
  } catch {
    // localStorage non disponibile (es. private browsing): il login
    // funziona comunque per la sessione corrente, solo non sopravvive al reload.
  }
}

const initialToken = readStoredToken()
setAuthToken(initialToken)

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(initialToken)
  const status = ref<'idle' | 'loading' | 'ready'>('idle')

  function setSession(newUser: User, newToken: string): void {
    user.value = newUser
    token.value = newToken
    setAuthToken(newToken)
    persistToken(newToken)
  }

  function clearSession(): void {
    user.value = null
    token.value = null
    setAuthToken(null)
    persistToken(null)
  }

  async function fetchMe(): Promise<void> {
    status.value = 'loading'

    if (!token.value) {
      user.value = null
      status.value = 'ready'
      return
    }

    try {
      user.value = await authService.me()
    } catch (error) {
      clearSession()
      if (!(error instanceof ApiError && error.status === 401)) {
        throw error
      }
    } finally {
      status.value = 'ready'
    }
  }

  async function register(payload: RegisterPayload): Promise<void> {
    const result = await authService.register(payload)
    setSession(result.user, result.token)
  }

  async function login(payload: LoginPayload): Promise<void> {
    const result = await authService.login(payload)
    setSession(result.user, result.token)
  }

  async function logout(): Promise<void> {
    try {
      await authService.logout()
    } finally {
      clearSession()
    }
  }

  return { user, token, status, fetchMe, register, login, logout }
})
