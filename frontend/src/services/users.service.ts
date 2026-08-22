import { api, type ApiResponse } from './api'

export interface PublicUser {
  id: number
  username: string
  avatar: string | null
}

export const usersService = {
  async getByUsername(username: string): Promise<PublicUser> {
    const response = await api.get<ApiResponse<PublicUser>>(`/users/${encodeURIComponent(username)}`)
    return response.data
  },
}
