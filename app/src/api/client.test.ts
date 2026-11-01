import { afterEach, describe, expect, it, vi } from 'vitest'
import { ApiError, api, setToken, setUnauthorizedHandler } from './client'

afterEach(() => {
  vi.unstubAllGlobals()
  setToken(null)
})

function mockFetch(status: number, body: unknown) {
  const fn = vi.fn().mockResolvedValue(new Response(JSON.stringify(body), { status }))
  vi.stubGlobal('fetch', fn)
  return fn
}

describe('api client', () => {
  it('sends the bearer token and returns the JSON body', async () => {
    const fetchMock = mockFetch(200, { status: 'ok' })
    setToken('abc')

    await expect(api('/ping')).resolves.toEqual({ status: 'ok' })

    const headers = fetchMock.mock.calls[0][1].headers as Headers
    expect(headers.get('Authorization')).toBe('Bearer abc')
  })

  it('calls the unauthorized handler on 401 and throws', async () => {
    mockFetch(401, { message: 'Unauthenticated.' })
    const handler = vi.fn()
    setUnauthorizedHandler(handler)

    await expect(api('/me')).rejects.toBeInstanceOf(ApiError)
    expect(handler).toHaveBeenCalledOnce()
  })
})
