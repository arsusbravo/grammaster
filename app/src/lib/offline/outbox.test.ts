import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/client'
import * as fx from '@/design/fixtures'
import type { AnswerResult } from '@/types/api'
import {
  clearReviewed,
  enqueueAnswer,
  onOutboxChange,
  queuedAnswers,
  reviewedAnswers,
  syncOutbox,
  type CheckFn,
} from './outbox'

const queue = (sessionId: string, questionId = 1) =>
  enqueueAnswer(7, {
    sessionId,
    question: { ...fx.multipleChoice, id: questionId },
    answer: { selected: [1] },
    timeSpentSeconds: 12,
  })

const result = (questionId: number, attemptId: number, ok = true): AnswerResult => ({
  ...fx.result(questionId, ok, { selected: [1] }),
  attempt_id: attemptId,
})

beforeEach(() => localStorage.clear())

describe('queueing', () => {
  it('keeps answers in order, per student', () => {
    const first = queue('s1', 1)
    const second = queue('s1', 2)
    enqueueAnswer(8, {
      sessionId: 'x',
      question: fx.markError,
      answer: { index: 0 },
      timeSpentSeconds: 1,
    })

    expect(queuedAnswers(7).map((q) => q.id)).toEqual([first.id, second.id])
    expect(queuedAnswers(8)).toHaveLength(1)
    expect(first.id).not.toBe(second.id)
    expect(first.queuedAt).toMatch(/^\d{4}-\d\d-\d\dT/)
  })

  it('tells listeners about changes', () => {
    const listener = vi.fn()
    const stop = onOutboxChange(listener)

    queue('s1')
    expect(listener).toHaveBeenCalledTimes(1)

    clearReviewed(7)
    expect(listener).toHaveBeenCalledTimes(2)

    stop()
    queue('s1')
    expect(listener).toHaveBeenCalledTimes(2)
  })
})

describe('syncing', () => {
  it('has the server check every answer and moves them to the reviewed list', async () => {
    queue('s1', 1)
    queue('s1', 2)
    const check = vi.fn<CheckFn>(async (id) => result(id, 50))

    const summary = await syncOutbox(7, check)

    expect(summary).toEqual({ synced: 2, dropped: 0, remaining: 0, stoppedBecause: null })
    expect(check).toHaveBeenNthCalledWith(1, 1, {
      answer: { selected: [1] },
      time_spent_seconds: 12,
      attempt_id: null,
    })
    expect(queuedAnswers(7)).toEqual([])
    expect(reviewedAnswers(7).map((r) => r.question.id)).toEqual([1, 2])
    expect(reviewedAnswers(7)[0].result.is_correct).toBe(true)
  })

  it('puts one practice session into one attempt', async () => {
    queue('s1', 1)
    queue('s1', 2)
    queue('s2', 3)
    let next = 100
    const check = vi.fn<CheckFn>(async (id, body) => result(id, body.attempt_id ?? next++))

    await syncOutbox(7, check)

    expect(check.mock.calls.map((c) => c[1].attempt_id)).toEqual([null, 100, null])
  })

  it('stops when the connection is lost and keeps the rest for next time', async () => {
    queue('s1', 1)
    queue('s1', 2)
    queue('s1', 3)
    const check = vi
      .fn<CheckFn>()
      .mockResolvedValueOnce(result(1, 50))
      .mockRejectedValueOnce(new TypeError('offline'))

    const summary = await syncOutbox(7, check)

    expect(summary).toEqual({ synced: 1, dropped: 0, remaining: 2, stoppedBecause: 'network' })
    expect(queuedAnswers(7).map((q) => q.question.id)).toEqual([2, 3])
    expect(check).toHaveBeenCalledTimes(2)

    // Later, still the same attempt.
    const again = vi.fn<CheckFn>(async (id, body) => result(id, body.attempt_id ?? 999))
    await syncOutbox(7, again)
    expect(again.mock.calls.map((c) => c[1].attempt_id)).toEqual([50, 50])
    expect(queuedAnswers(7)).toEqual([])
  })

  it('stops (and keeps everything) when the login has expired', async () => {
    queue('s1', 1)
    queue('s1', 2)
    const check = vi.fn<CheckFn>().mockRejectedValue(new ApiError(401, null))

    const summary = await syncOutbox(7, check)

    expect(summary.stoppedBecause).toBe('auth')
    expect(queuedAnswers(7)).toHaveLength(2)
    expect(check).toHaveBeenCalledTimes(1)
  })

  it('stops on a server error and tries again later', async () => {
    queue('s1', 1)
    const check = vi.fn<CheckFn>().mockRejectedValue(new ApiError(500, null))

    const summary = await syncOutbox(7, check)

    expect(summary).toMatchObject({ stoppedBecause: 'server', remaining: 1, synced: 0 })
  })

  it('gives up on answers the server will never accept and carries on', async () => {
    queue('s1', 1) // question was removed
    queue('s1', 2) // answer no longer fits
    queue('s1', 3)
    const check = vi
      .fn<CheckFn>()
      .mockRejectedValueOnce(new ApiError(404, null))
      .mockRejectedValueOnce(new ApiError(422, { errors: {} }))
      .mockResolvedValueOnce(result(3, 50))

    const summary = await syncOutbox(7, check)

    expect(summary).toEqual({ synced: 1, dropped: 2, remaining: 0, stoppedBecause: null })
    expect(reviewedAnswers(7).map((r) => r.question.id)).toEqual([3])
  })

  it('runs only one sync at a time', async () => {
    queue('s1', 1)
    let finish: () => void = () => {}
    const check = vi.fn<CheckFn>(
      () => new Promise((resolve) => (finish = () => resolve(result(1, 50)))),
    )

    const first = syncOutbox(7, check)
    const second = syncOutbox(7, check)
    finish()
    await Promise.all([first, second])

    expect(check).toHaveBeenCalledTimes(1)
    expect(await first).toBe(await second)
  })

  it('does nothing when the queue is empty', async () => {
    const check = vi.fn<CheckFn>()

    expect(await syncOutbox(7, check)).toEqual({
      synced: 0,
      dropped: 0,
      remaining: 0,
      stoppedBecause: null,
    })
    expect(check).not.toHaveBeenCalled()
  })

  it('keeps reviewed answers until they are dismissed', async () => {
    queue('s1', 1)
    await syncOutbox(7, async (id) => result(id, 50))
    expect(reviewedAnswers(7)).toHaveLength(1)

    clearReviewed(7)

    expect(reviewedAnswers(7)).toEqual([])
  })
})
