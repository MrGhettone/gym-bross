import { api, ensureCsrfCookie, type ApiResponse } from './api'

export interface User {
  id: number
  username: string
  email: string
  avatar: string | null
  created_at: string
}

export interface RegisterPayload {
  username: string
  email: string
  password: string
  password_confirmation: string
}

export interface LoginPayload {
  email: string
  password: string
}

export const authService = {
  async register(payload: RegisterPayload): Promise<User> {
    await ensureCsrfCookie()
    const response = await api.post<ApiResponse<User>>('/auth/register', payload)
    return response.data
  },

  async login(payload: LoginPayload): Promise<User> {
    await ensureCsrfCookie()
    const response = await api.post<ApiResponse<User>>('/auth/login', payload)
    return response.data
  },

  async logout(): Promise<void> {
    await ensureCsrfCookie()
    await api.post('/auth/logout')
  },

  async me(): Promise<User> {
    const response = await api.get<ApiResponse<User>>('/auth/me')
    return response.data
  },
}
