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

export interface FeedDaySummary {
  date: string
  count: number
}

export const feedService = {
  /** @param month formato YYYY-MM, default il mese corrente lato backend */
  async summary(month?: string): Promise<FeedDaySummary[]> {
    const query = month ? `?month=${month}` : ''
    const response = await api.get<ApiResponse<FeedDaySummary[]>>(`/feed/summary${query}`)
    return response.data
  },

  /** @param date formato YYYY-MM-DD */
  async day(date: string): Promise<FeedWorkout[]> {
    const response = await api.get<ApiResponse<FeedWorkout[]>>(`/feed/day?date=${date}`)
    return response.data
  },
}
