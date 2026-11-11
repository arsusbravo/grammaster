import { afterEach, describe, expect, it, vi } from 'vitest'
import { newId } from './uuid'

const UUID_V4 = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/

afterEach(() => vi.unstubAllGlobals())

describe('newId', () => {
  it('returns a version 4 uuid', () => {
    expect(newId()).toMatch(UUID_V4)
  })

  it('still works where crypto.randomUUID does not exist (plain http)', () => {
    // Only getRandomValues, like a browser in a non-secure context.
    vi.stubGlobal('crypto', { getRandomValues: crypto.getRandomValues.bind(crypto) })

    const ids = new Set(Array.from({ length: 50 }, () => newId()))

    expect([...ids].every((id) => UUID_V4.test(id))).toBe(true)
    expect(ids.size).toBe(50)
  })
})
