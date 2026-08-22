import { api, type ApiResponse } from './api'

export interface User {
  id: number
  username: string
  email: string
  avatar: string | null
  created_at: string
}

export interface AuthResult {
  user: User
  token: string
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

interface AuthResponse extends ApiResponse<User> {
  token: string
}

export const authService = {
  async register(payload: RegisterPayload): Promise<AuthResult> {
    const response = await api.post<AuthResponse>('/auth/register', payload)
    return { user: response.data, token: response.token }
  },

  async login(payload: LoginPayload): Promise<AuthResult> {
    const response = await api.post<AuthResponse>('/auth/login', payload)
    return { user: response.data, token: response.token }
  },

  async logout(): Promise<void> {
    await api.post('/auth/logout')
  },

  async me(): Promise<User> {
    const response = await api.get<ApiResponse<User>>('/auth/me')
    return response.data
  },
}
