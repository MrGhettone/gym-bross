import { api, type ApiResponse } from './api'
import type { PublicUser } from './users.service'
import type { WorkoutStatus } from './workouts.service'

export interface FeedWorkout {
  id: number
  status: WorkoutStatus
  started_at: string
  finished_at: string | null
  exercises_count: number
  user: PublicUser
}

export const feedService = {
  async list(): Promise<FeedWorkout[]> {
    const response = await api.get<ApiResponse<FeedWorkout[]>>('/feed')
    return response.data
  },
}
