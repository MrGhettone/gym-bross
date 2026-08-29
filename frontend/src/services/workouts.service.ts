import { api, type ApiResponse } from './api'
import type { Exercise } from './exercises.service'
import type { PublicUser } from './users.service'

export type WorkoutStatus = 'active' | 'completed' | 'cancelled'

export interface WorkoutSet {
  id: number
  set_number: number
  weight: string | null
  repetitions: number | null
  duration: number | null
  distance: number | null
}

export interface WorkoutExerciseEntry {
  id: number
  order: number
  exercise: Exercise
  sets: WorkoutSet[]
}

export interface Workout {
  id: number
  status: WorkoutStatus
  started_at: string
  finished_at: string | null
  user?: PublicUser
  exercises?: WorkoutExerciseEntry[]
}

export interface SetPayload {
  weight?: number
  repetitions?: number
  duration?: number
  distance?: number
}

export const workoutsService = {
  async list(): Promise<Workout[]> {
    const response = await api.get<ApiResponse<Workout[]>>('/workouts')
    return response.data
  },

  async start(): Promise<Workout> {
    const response = await api.post<ApiResponse<Workout>>('/workouts')
    return response.data
  },

  async show(id: number): Promise<Workout> {
    const response = await api.get<ApiResponse<Workout>>(`/workouts/${id}`)
    return response.data
  },

  async finish(id: number): Promise<Workout> {
    const response = await api.patch<ApiResponse<Workout>>(`/workouts/${id}/finish`)
    return response.data
  },

  async cancel(id: number): Promise<Workout> {
    const response = await api.patch<ApiResponse<Workout>>(`/workouts/${id}/cancel`)
    return response.data
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/workouts/${id}`)
  },

  async addExercise(workoutId: number, exerciseId: number): Promise<WorkoutExerciseEntry> {
    const response = await api.post<ApiResponse<WorkoutExerciseEntry>>(`/workouts/${workoutId}/exercises`, {
      exercise_id: exerciseId,
    })
    return response.data
  },

  async removeExercise(workoutId: number, workoutExerciseId: number): Promise<void> {
    await api.delete(`/workouts/${workoutId}/exercises/${workoutExerciseId}`)
  },

  async logSet(workoutId: number, workoutExerciseId: number, payload: SetPayload): Promise<WorkoutSet> {
    const response = await api.post<ApiResponse<WorkoutSet>>(
      `/workouts/${workoutId}/exercises/${workoutExerciseId}/sets`,
      payload,
    )
    return response.data
  },

  async updateSet(setId: number, payload: SetPayload): Promise<WorkoutSet> {
    const response = await api.patch<ApiResponse<WorkoutSet>>(`/workout-sets/${setId}`, payload)
    return response.data
  },

  async deleteSet(setId: number): Promise<void> {
    await api.delete(`/workout-sets/${setId}`)
  },
}
