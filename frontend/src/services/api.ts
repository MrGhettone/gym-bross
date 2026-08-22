const API_URL = import.meta.env.VITE_API_URL as string

export interface ApiResponse<T> {
  data: T
}

export interface ApiErrorBody {
  message: string
  errors?: Record<string, string[]>
}

export class ApiError extends Error {
  status: number
  body: ApiErrorBody

  constructor(status: number, body: ApiErrorBody) {
    super(body.message)
    this.status = status
    this.body = body
  }
}

let authToken: string | null = null

/**
 * Frontend e backend sono su domini diversi in produzione: l'auth cookie-based
 * di Sanctum non e' utilizzabile (un cookie del backend non e' leggibile via
 * JS da un altro dominio). Si usa quindi un token Bearer, tenuto in memoria
 * qui e persistito da stores/auth.ts.
 */
export function setAuthToken(token: string | null): void {
  authToken = token
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string> | undefined),
  }

  if (authToken) {
    headers.Authorization = `Bearer ${authToken}`
  }

  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers,
  })

  if (!response.ok) {
    const body = (await response.json().catch(() => ({ message: response.statusText }))) as ApiErrorBody
    throw new ApiError(response.status, body)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}

export const api = {
  get: <T>(path: string) => request<T>(path, { method: 'GET' }),
  post: <T>(path: string, data?: unknown) =>
    request<T>(path, { method: 'POST', body: data ? JSON.stringify(data) : undefined }),
  patch: <T>(path: string, data?: unknown) =>
    request<T>(path, { method: 'PATCH', body: data ? JSON.stringify(data) : undefined }),
  delete: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
}
