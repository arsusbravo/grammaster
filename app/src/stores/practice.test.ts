import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/client'
import * as endpoints from '@/api/endpoints'
import * as fx from '@/design/fixtures'
import type { AnswerResult, Question } from '@/types/api'
import { cachedQuestions } from '@/lib/offline/questionCache'
import { queuedAnswers } from '@/lib/offline/outbox'
import { useAuthStore } from './auth'
import { BATCH_SIZE, usePracticeStore } from './practice'

vi.mock('@/api/endpoints')

const mocked = vi.mocked(endpoints)

/** A multiple choice question with a given id. */
const q = (id: number): Question => ({ ...fx.multipleChoice, id })
const batch = (from: number, count: number) => Array.from({ length: count }, (_, i) => q(from + i))

function checked(id: number, isCorrect: boolean, attemptId = 7): AnswerResult {
  return (
    fx.result(id, isCorrect, { selected: [1] }, null) && {
      ...fx.result(id, isCorrect, { selected: [1] }),
      attempt_id: attemptId,
    }
  )
}

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
  useAuthStore().user = { id: 7, name: 'Sanne', email: 's@example.nl', level_id: null }
  vi.resetAllMocks()
  vi.useFakeTimers()
})

afterEach(() => vi.useRealTimers())

describe('starting a session', () => {
  it('loads questions and shows the first one with an empty answer', async () => {
    mocked.questions.mockResolvedValue(batch(1, BATCH_SIZE))
    const practice = usePracticeStore()

    await practice.start({ level: 'vmbo', topic: 'lidwoorden' })

    expect(mocked.questions).toHaveBeenCalledWith({
      level: 'vmbo',
      topic: 'lidwoorden',
      limit: BATCH_SIZE,
      exclude: [],
    })
    expect(practice.status).toBe('answering')
    expect(practice.current?.id).toBe(1)
    expect(practice.answer).toEqual({ selected: [] })
    expect(practice.result).toBeNull()
  })

  it('finishes straight away when there are no questions', async () => {
    mocked.questions.mockResolvedValue([])
    const practice = usePracticeStore()

    await practice.start({ level: 'vmbo' })

    expect(practice.status).toBe('finished')
    expect(practice.current).toBeNull()
    expect(practice.answered).toBe(0)
  })

  it('reports a failed load and can retry', async () => {
    mocked.questions.mockRejectedValueOnce(new TypeError('Failed to fetch'))
    const practice = usePracticeStore()

    await practice.start({})
    expect(practice.status).toBe('loading')
    expect(practice.error).toBe('network')

    mocked.questions.mockResolvedValueOnce(batch(1, 3))
    await practice.retry()

    expect(practice.error).toBeNull()
    expect(practice.status).toBe('answering')
    expect(practice.current?.id).toBe(1)
  })

  it('starts over cleanly', async () => {
    mocked.questions.mockResolvedValue(batch(1, 2))
    mocked.checkAnswer.mockResolvedValue(checked(1, true))
    const practice = usePracticeStore()
    await practice.start({ level: 'vmbo' })
    practice.answer = { selected: [1] }
    await practice.check()

    await practice.start({ level: 'havo' })

    expect(practice.answered).toBe(0)
    expect(practice.correct).toBe(0)
    expect(practice.attemptId).toBeNull()
    expect(mocked.questions).toHaveBeenLastCalledWith({
      level: 'havo',
      limit: BATCH_SIZE,
      exclude: [],
    })
  })
})

describe('checking an answer', () => {
  async function started() {
    mocked.questions.mockResolvedValue(batch(1, 3))
    const practice = usePracticeStore()
    await practice.start({ level: 'vmbo' })
    return practice
  }

  it('cannot be checked before the answer is complete', async () => {
    const practice = await started()

    expect(practice.canCheck).toBe(false)
    await practice.check()
    expect(mocked.checkAnswer).not.toHaveBeenCalled()

    practice.answer = { selected: [1] }
    expect(practice.canCheck).toBe(true)
  })

  it('sends the answer to the server, which decides what is correct', async () => {
    const practice = await started()
    mocked.checkAnswer.mockResolvedValue(checked(1, false))
    practice.answer = { selected: [0] }
    vi.advanceTimersByTime(12_400)

    await practice.check()

    expect(mocked.checkAnswer).toHaveBeenCalledWith(1, {
      answer: { selected: [0] },
      time_spent_seconds: 12,
      attempt_id: null,
    })
    expect(practice.status).toBe('checked')
    expect(practice.result?.is_correct).toBe(false)
    expect(practice.answered).toBe(1)
    expect(practice.correct).toBe(0)
  })

  it('counts correct answers and keeps one attempt for the whole session', async () => {
    const practice = await started()

    mocked.checkAnswer.mockResolvedValueOnce(checked(1, true, 42))
    practice.answer = { selected: [1] }
    await practice.check()
    await practice.next()

    mocked.checkAnswer.mockResolvedValueOnce(checked(2, false, 42))
    practice.answer = { selected: [0] }
    await practice.check()

    expect(mocked.checkAnswer).toHaveBeenNthCalledWith(
      1,
      1,
      expect.objectContaining({ attempt_id: null }),
    )
    expect(mocked.checkAnswer).toHaveBeenNthCalledWith(
      2,
      2,
      expect.objectContaining({ attempt_id: 42 }),
    )
    expect(practice.attemptId).toBe(42)
    expect(practice.answered).toBe(2)
    expect(practice.correct).toBe(1)
  })

  it('ignores a second check while the first is still running', async () => {
    const practice = await started()
    let finish: (r: AnswerResult) => void = () => {}
    mocked.checkAnswer.mockReturnValue(new Promise((resolve) => (finish = resolve)))
    practice.answer = { selected: [1] }

    const first = practice.check()
    expect(practice.status).toBe('checking')
    await practice.check()
    finish(checked(1, true))
    await first

    expect(mocked.checkAnswer).toHaveBeenCalledTimes(1)
    expect(practice.answered).toBe(1)
  })

  it('keeps the answer and lets the student retry when the server fails', async () => {
    const practice = await started()
    mocked.checkAnswer.mockRejectedValueOnce(new ApiError(503, null))
    practice.answer = { selected: [1] }

    await practice.check()

    expect(practice.status).toBe('answering')
    expect(practice.error).toBe('server')
    expect(practice.answer).toEqual({ selected: [1] })
    expect(practice.answered).toBe(0)

    mocked.checkAnswer.mockResolvedValueOnce(checked(1, true))
    await practice.check()

    expect(practice.status).toBe('checked')
    expect(practice.error).toBeNull()
  })

  it('reports a server error differently from being offline', async () => {
    const practice = await started()
    mocked.checkAnswer.mockRejectedValueOnce(new ApiError(500, null))
    practice.answer = { selected: [1] }

    await practice.check()

    expect(practice.error).toBe('server')
  })
})

describe('moving on', () => {
  it('shows the next question with a fresh empty answer', async () => {
    mocked.questions.mockResolvedValue(batch(1, 3))
    mocked.checkAnswer.mockResolvedValue(checked(1, true))
    const practice = usePracticeStore()
    await practice.start({})
    practice.answer = { selected: [1] }
    await practice.check()

    await practice.next()

    expect(practice.current?.id).toBe(2)
    expect(practice.answer).toEqual({ selected: [] })
    expect(practice.result).toBeNull()
    expect(practice.status).toBe('answering')
  })

  it('does nothing before the current question was checked', async () => {
    mocked.questions.mockResolvedValue(batch(1, 3))
    const practice = usePracticeStore()
    await practice.start({})

    await practice.next()

    expect(practice.current?.id).toBe(1)
  })

  it('fetches more questions, excluding the ones already seen', async () => {
    mocked.questions
      .mockResolvedValueOnce(batch(1, BATCH_SIZE))
      .mockResolvedValueOnce(batch(11, BATCH_SIZE))
    mocked.checkAnswer.mockImplementation(async (id) => checked(id, true))
    const practice = usePracticeStore()
    await practice.start({ topic: 'lidwoorden' })

    // Answer questions until the queue runs low: the next batch is prefetched.
    for (let i = 0; i < 8; i++) {
      practice.answer = { selected: [1] }
      await practice.check()
      await practice.next()
    }
    await vi.runAllTimersAsync()

    expect(mocked.questions).toHaveBeenCalledTimes(2)
    expect(mocked.questions).toHaveBeenLastCalledWith({
      topic: 'lidwoorden',
      limit: BATCH_SIZE,
      exclude: Array.from({ length: BATCH_SIZE }, (_, i) => i + 1),
    })
    expect(practice.current?.id).toBe(9)
  })

  it('finishes when the server has no more unseen questions', async () => {
    mocked.questions.mockResolvedValue(batch(1, 2)) // fewer than a full batch: nothing left after these
    mocked.checkAnswer.mockImplementation(async (id) => checked(id, id === 1))
    const practice = usePracticeStore()
    await practice.start({})

    for (let i = 0; i < 2; i++) {
      practice.answer = { selected: [1] }
      await practice.check()
      await practice.next()
    }

    expect(practice.status).toBe('finished')
    expect(practice.current).toBeNull()
    expect(practice.answered).toBe(2)
    expect(practice.correct).toBe(1)
    expect(mocked.questions).toHaveBeenCalledTimes(1)
  })

  it('stays on the loading screen with an error when the server fails while fetching the next batch', async () => {
    mocked.questions.mockResolvedValueOnce(batch(1, BATCH_SIZE))
    mocked.checkAnswer.mockImplementation(async (id) => checked(id, true))
    const practice = usePracticeStore()
    await practice.start({})
    mocked.questions.mockRejectedValue(new ApiError(500, null))

    // Use up the whole first batch.
    for (let i = 0; i < BATCH_SIZE; i++) {
      practice.answer = { selected: [1] }
      await practice.check()
      await practice.next()
    }

    expect(practice.error).toBe('server')
    expect(practice.status).toBe('loading')
  })

  it('ends the session when the connection is lost after the whole batch was answered', async () => {
    mocked.questions.mockResolvedValueOnce(batch(1, BATCH_SIZE))
    mocked.checkAnswer.mockImplementation(async (id) => checked(id, true))
    const practice = usePracticeStore()
    await practice.start({})
    mocked.questions.mockRejectedValue(new TypeError('offline'))

    for (let i = 0; i < BATCH_SIZE; i++) {
      practice.answer = { selected: [1] }
      await practice.check()
      await practice.next()
    }

    expect(practice.status).toBe('finished')
    expect(practice.answered).toBe(BATCH_SIZE)
  })
})

describe('without a connection', () => {
  const offline = () => new TypeError('Failed to fetch')

  /** A level-tagged question, as the API sends it. */
  const tagged = (id: number, level = 'vmbo', topic = 'lidwoorden'): Question => ({
    ...fx.multipleChoice,
    id,
    level: { id: 1, slug: level, name: level, sort_order: 1 },
    topic: { id: 1, slug: topic, name: topic, description: null, sort_order: 1 },
  })

  it('remembers the questions it receives for later', async () => {
    mocked.questions.mockResolvedValue([tagged(1), tagged(2)])

    await usePracticeStore().start({ level: 'vmbo' })

    expect(cachedQuestions(7).map((q) => q.id)).toEqual([1, 2])
  })

  it('starts a session from the questions on this device when the connection is down', async () => {
    mocked.questions.mockResolvedValueOnce([tagged(1), tagged(2), tagged(3, 'havo')])
    await usePracticeStore().start({})

    setActivePinia(createPinia())
    useAuthStore().user = { id: 7, name: 'Sanne', email: 's@example.nl', level_id: null }
    mocked.questions.mockRejectedValue(offline())
    const practice = usePracticeStore()
    await practice.start({ level: 'vmbo' })

    expect(practice.status).toBe('answering')
    expect(practice.offlineQuestions).toBe(true)
    expect([1, 2]).toContain(practice.current?.id)
    expect(practice.error).toBeNull()
  })

  it('shows the normal error when nothing is stored on the device', async () => {
    mocked.questions.mockRejectedValue(offline())
    const practice = usePracticeStore()

    await practice.start({})

    expect(practice.status).toBe('loading')
    expect(practice.error).toBe('network')
  })

  it('does not hide other errors behind the offline fallback', async () => {
    mocked.questions.mockResolvedValueOnce([tagged(1)])
    await usePracticeStore().start({})
    setActivePinia(createPinia())
    useAuthStore().user = { id: 7, name: 'S', email: 's@example.nl', level_id: null }
    mocked.questions.mockRejectedValue(new ApiError(500, null))
    const practice = usePracticeStore()

    await practice.start({})

    expect(practice.error).toBe('server')
    expect(practice.offlineQuestions).toBe(false)
  })

  async function offlineSession() {
    mocked.questions.mockResolvedValueOnce([tagged(1), tagged(2)])
    await usePracticeStore().start({ level: 'vmbo' })
    setActivePinia(createPinia())
    useAuthStore().user = { id: 7, name: 'S', email: 's@example.nl', level_id: null }
    mocked.questions.mockRejectedValue(offline())
    mocked.checkAnswer.mockRejectedValue(offline())
    const practice = usePracticeStore()
    await practice.start({ level: 'vmbo' })
    return practice
  }

  it('queues an answer that cannot reach the server instead of failing', async () => {
    const practice = await offlineSession()
    const question = practice.current!
    practice.answer = { selected: [1] }
    vi.advanceTimersByTime(8_000)

    await practice.check()

    expect(practice.status).toBe('checked')
    expect(practice.queued).toBe(true)
    expect(practice.result).toBeNull()
    expect(practice.queuedCount).toBe(1)
    expect(practice.answered).toBe(0)
    const [entry] = queuedAnswers(7)
    expect(entry).toMatchObject({
      answer: { selected: [1] },
      timeSpentSeconds: 8,
      sessionId: expect.any(String),
    })
    expect(entry.question.id).toBe(question.id)
  })

  it('carries on with the next question and queues that answer too, in the same session', async () => {
    const practice = await offlineSession()
    practice.answer = { selected: [1] }
    await practice.check()
    await practice.next()
    expect(practice.queued).toBe(false)
    expect(practice.status).toBe('answering')

    practice.answer = { selected: [0] }
    await practice.check()

    const queue = queuedAnswers(7)
    expect(queue).toHaveLength(2)
    expect(queue[0].sessionId).toBe(queue[1].sessionId)
    expect(practice.queuedCount).toBe(2)
  })

  it('ends the session when the stored questions run out, instead of showing an error', async () => {
    const practice = await offlineSession()
    for (let i = 0; i < 2; i++) {
      practice.answer = { selected: [1] }
      await practice.check()
      await practice.next()
    }

    expect(practice.status).toBe('finished')
    expect(practice.error).toBeNull()
    expect(practice.queuedCount).toBe(2)
  })

  it('does not queue an answer the server rejected', async () => {
    const practice = await offlineSession()
    mocked.checkAnswer.mockRejectedValue(new ApiError(422, { errors: {} }))
    practice.answer = { selected: [1] }

    await practice.check()

    expect(queuedAnswers(7)).toEqual([])
    expect(practice.status).toBe('answering')
    expect(practice.error).toBe('validation')
  })

  it('gives every new session its own id', async () => {
    mocked.questions.mockResolvedValue([tagged(1)])
    const practice = usePracticeStore()

    await practice.start({})
    const first = practice.sessionId
    await practice.start({})

    expect(first).not.toBe('')
    expect(practice.sessionId).not.toBe(first)
  })
})
