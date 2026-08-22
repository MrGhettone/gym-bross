import { api, type ApiResponse } from './api'
import type { PublicUser } from './users.service'

export type FriendshipStatus = 'pending' | 'accepted' | 'rejected' | 'blocked'
export type FriendshipDirection = 'incoming' | 'outgoing'

export interface Friendship {
  id: number
  status: FriendshipStatus
  direction: FriendshipDirection
  requester: PublicUser
  addressee: PublicUser
  created_at: string
}

export const friendsService = {
  async list(status?: FriendshipStatus): Promise<Friendship[]> {
    const query = status ? `?status=${status}` : ''
    const response = await api.get<ApiResponse<Friendship[]>>(`/friends${query}`)
    return response.data
  },

  async sendRequest(username: string): Promise<Friendship> {
    const response = await api.post<ApiResponse<Friendship>>('/friends', { username })
    return response.data
  },

  async accept(id: number): Promise<Friendship> {
    const response = await api.patch<ApiResponse<Friendship>>(`/friends/${id}/accept`)
    return response.data
  },

  async reject(id: number): Promise<Friendship> {
    const response = await api.patch<ApiResponse<Friendship>>(`/friends/${id}/reject`)
    return response.data
  },

  async block(id: number): Promise<Friendship> {
    const response = await api.patch<ApiResponse<Friendship>>(`/friends/${id}/block`)
    return response.data
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/friends/${id}`)
  },
}
