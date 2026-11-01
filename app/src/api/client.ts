import { beginActivity } from '@/lib/activity'

// Single place for all API calls: base URL from env, bearer token, central 401 handling.
const baseUrl = (import.meta.env.VITE_API_BASE_URL as string | undefined) ?? '/api/v1'

let token: string | null = null
let onUnauthorized: () => void = () => {}

export function setToken(value: string | null): void {
  token = value
}

export function setUnauthorizedHandler(handler: () => void): void {
  onUnauthorized = handler
}

export class ApiError extends Error {
  status: number
  body: unknown

  constructor(status: number, body: unknown) {
    super(`API request failed with status ${status}`)
    this.status = status
    this.body = body
  }
}

export interface ApiInit extends RequestInit {
  /** A request the student did not ask for (autosave, clock sync): it does not show the loader. */
  background?: boolean
}

export async function api<T>(path: string, init: ApiInit = {}): Promise<T> {
  const { background, ...fetchInit } = init
  const done = background ? () => {} : beginActivity()

  try {
    return await request<T>(path, fetchInit)
  } finally {
    done()
  }
}

async function request<T>(path: string, init: RequestInit): Promise<T> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')
  if (init.body && !headers.has('Content-Type')) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)

  const response = await fetch(`${baseUrl}${path}`, { ...init, headers })
  const body: unknown = response.status === 204 ? null : await response.json().catch(() => null)

  if (response.status === 401) onUnauthorized()
  if (!response.ok) throw new ApiError(response.status, body)

  return body as T
}
