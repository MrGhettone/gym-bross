import { api, type ApiResponse } from './api'

export interface Exercise {
  id: number
  name: string
  description: string | null
}

export const exercisesService = {
  async list(search?: string): Promise<Exercise[]> {
    const query = search ? `?search=${encodeURIComponent(search)}` : ''
    const response = await api.get<ApiResponse<Exercise[]>>(`/exercises${query}`)
    return response.data
  },

  async create(name: string, description?: string): Promise<Exercise> {
    const response = await api.post<ApiResponse<Exercise>>('/exercises', { name, description })
    return response.data
  },
}
