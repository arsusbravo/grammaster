import { ApiError } from './client'

/** Laravel validation errors (422) as one message per field. */
export function fieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError) || error.status !== 422) return {}
  const errors = (error.body as { errors?: Record<string, string[]> } | null)?.errors ?? {}
  return Object.fromEntries(Object.entries(errors).map(([field, messages]) => [field, messages[0]]))
}

/** True when the request never reached the server (offline, API down, CORS). */
export function isNetworkError(error: unknown): boolean {
  return error instanceof TypeError
}

export type ErrorKind = 'network' | 'validation' | 'unauthorized' | 'notfound' | 'server'

export function errorKind(error: unknown): ErrorKind {
  if (isNetworkError(error)) return 'network'
  if (error instanceof ApiError) {
    if (error.status === 422) return 'validation'
    if (error.status === 401) return 'unauthorized'
    if (error.status === 404) return 'notfound'
  }
  return 'server'
}
