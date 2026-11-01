import { afterEach, describe, expect, it, vi } from 'vitest'
import { activityCount, beginActivity, subscribeActivity } from './activity'

afterEach(() => {
  // Leave nothing running for the next test.
  while (activityCount() > 0) beginActivity()()
})

describe('activity', () => {
  it('counts what is running', () => {
    const before = activityCount()
    const first = beginActivity()
    const second = beginActivity()
    expect(activityCount()).toBe(before + 2)

    first()
    expect(activityCount()).toBe(before + 1)
    second()
    expect(activityCount()).toBe(before)
  })

  it('ignores finishing the same thing twice', () => {
    const before = activityCount()
    const keep = beginActivity()
    const done = beginActivity()

    done()
    done()

    expect(activityCount()).toBe(before + 1)
    keep()
  })

  it('tells listeners the current number right away and on every change', () => {
    const listener = vi.fn()
    const unsubscribe = subscribeActivity(listener)
    const start = activityCount()
    expect(listener).toHaveBeenLastCalledWith(start)

    const done = beginActivity()
    expect(listener).toHaveBeenLastCalledWith(start + 1)
    done()
    expect(listener).toHaveBeenLastCalledWith(start)

    unsubscribe()
    beginActivity()()
    expect(listener).toHaveBeenCalledTimes(3)
  })
})
