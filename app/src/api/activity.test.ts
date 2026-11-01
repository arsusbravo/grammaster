import { afterEach, describe, expect, it, vi } from 'vitest'
import { activityCount } from '@/lib/activity'
import { api, ApiError } from './client'

afterEach(() => vi.unstubAllGlobals())

function slowFetch(status = 200) {
  let finish: () => void = () => {}
  const fetchMock = vi.fn().mockReturnValue(
    new Promise<Response>((resolve) => {
      finish = () => resolve(new Response(JSON.stringify({ data: 1 }), { status }))
    }),
  )
  vi.stubGlobal('fetch', fetchMock)
  return { finish: () => finish() }
}

describe('api activity', () => {
  it('counts as running from the request until the response', async () => {
    const before = activityCount()
    const { finish } = slowFetch()

    const request = api('/levels')
    expect(activityCount()).toBe(before + 1)
    finish()
    await request

    expect(activityCount()).toBe(before)
  })

  it('stops counting when the request fails', async () => {
    const before = activityCount()
    const { finish } = slowFetch(500)

    const request = api('/levels')
    finish()
    await expect(request).rejects.toBeInstanceOf(ApiError)

    expect(activityCount()).toBe(before)
  })

  it('stops counting when the server cannot be reached', async () => {
    const before = activityCount()
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('offline')))

    await expect(api('/levels')).rejects.toBeInstanceOf(TypeError)

    expect(activityCount()).toBe(before)
  })

  it('does not count background requests (autosave, clock sync)', async () => {
    const before = activityCount()
    const { finish } = slowFetch()

    const request = api('/attempts/1/answers/2', { method: 'PUT', background: true })
    expect(activityCount()).toBe(before)
    finish()
    await request
  })

  it('does not send the background flag to the server', async () => {
    const { finish } = slowFetch()
    const request = api('/ping', { background: true })
    finish()
    await request

    expect(vi.mocked(fetch).mock.calls[0][1]).not.toHaveProperty('background')
  })
})
