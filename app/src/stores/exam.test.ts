import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/client'
import * as endpoints from '@/api/endpoints'
import * as fx from '@/design/fixtures'
import type { ExamAttempt, ExamQuestion } from '@/types/api'
import {
  CHOICE_DELAY_MS,
  CRITICAL_SECONDS,
  LOW_SECONDS,
  RETRY_DELAY_MS,
  TYPING_DELAY_MS,
  useExamStore,
} from './exam'

vi.mock('@/api/endpoints')
const mocked = vi.mocked(endpoints)

const mc: ExamQuestion = { ...fx.multipleChoice, id: 101, points: 1 }
const mcMulti: ExamQuestion = { ...fx.multipleChoiceMulti, id: 102, points: 2 }
const fill: ExamQuestion = { ...fx.fillBlank, id: 103, points: 2 }
const order: ExamQuestion = { ...fx.orderWords, id: 104, points: 1 }
const mark: ExamQuestion = { ...fx.markError, id: 105, points: 1 }
const parse: ExamQuestion = { ...fx.parseSentence, id: 106, points: 2 }

function attempt(overrides: Partial<ExamAttempt> = {}): ExamAttempt {
  return {
    id: 7,
    exam: { id: 1, title: 'Oefenexamen', time_limit_minutes: 30, pass_mark_percent: 55 },
    started_at: '2026-11-01T10:00:00Z',
    ends_at: '2026-11-01T10:30:00Z',
    remaining_seconds: 1800,
    finished_at: null,
    questions: [mc, mcMulti, fill, order, mark, parse],
    answers: {},
    ...overrides,
  }
}

async function started(a: ExamAttempt = attempt()) {
  mocked.attempt.mockResolvedValue(a)
  const exam = useExamStore()
  await exam.load(a.id)
  return exam
}

const advance = (ms: number) => vi.advanceTimersByTimeAsync(ms)
const pending = () => localStorage.getItem('grammatica.exam.7.pending')

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
  vi.resetAllMocks()
  mocked.saveExamAnswer.mockResolvedValue()
  mocked.deleteExamAnswer.mockResolvedValue()
  vi.useFakeTimers({ now: new Date('2026-11-01T10:00:00Z') })
})

afterEach(() => {
  useExamStore().dispose()
  vi.useRealTimers()
})

describe('loading', () => {
  it('shows the questions in order with empty answers and starts at the first', async () => {
    const exam = await started()

    expect(exam.status).toBe('running')
    expect(exam.questions.map((q) => q.id)).toEqual([101, 102, 103, 104, 105, 106])
    expect(exam.index).toBe(0)
    expect(exam.answers[101]).toEqual({ selected: [] })
    expect(exam.answers[104]).toEqual({ order: fx.orderWords.data.words })
    expect(exam.answeredCount).toBe(0)
    expect(exam.remaining).toBe(1800)
  })

  it('restores the answers the server has and continues at the first open question', async () => {
    const exam = await started(
      attempt({ answers: { 101: { selected: [1] }, 102: { selected: [0] } } }),
    )

    expect(exam.answers[101]).toEqual({ selected: [1] })
    expect(exam.index).toBe(2)
    expect(exam.answeredCount).toBe(2)
    expect(exam.saving).toBe('saved')
  })

  it('treats a server-side order as answered, but an untouched one as not', async () => {
    const exam = await started(
      attempt({ answers: { 104: { order: ['Gisteren', 'ging', 'ik', 'zwemmen'] } } }),
    )

    expect(exam.progress.find((p) => p.id === 104)?.state).toBe('complete')
    expect((await started()).progress.find((p) => p.id === 104)?.state).toBe('none')
  })

  it('knows an already finished attempt', async () => {
    const exam = await started(attempt({ finished_at: '2026-11-01T10:20:00Z' }))

    expect(exam.status).toBe('finished')
  })

  it('reports when the attempt cannot be loaded', async () => {
    mocked.attempt.mockRejectedValue(new TypeError('offline'))
    const exam = useExamStore()

    await exam.load(7)

    expect(exam.status).toBe('error')
    expect(exam.error).toBe('network')
  })

  it('puts answers that were never saved (refresh, crash, offline) back and saves them', async () => {
    localStorage.setItem(
      'grammatica.exam.7.pending',
      JSON.stringify({ 101: { answer: { selected: [2] } }, 999: { answer: { selected: [0] } } }),
    )
    const exam = await started()

    expect(exam.answers[101]).toEqual({ selected: [2] })
    expect(exam.saving).toBe('dirty')
    expect(exam.index).toBe(1)

    await advance(CHOICE_DELAY_MS)

    expect(mocked.saveExamAnswer).toHaveBeenCalledTimes(1)
    expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 101, { selected: [2] })
    expect(exam.saving).toBe('saved')
    expect(pending()).toBeNull()
  })

  it('ignores damaged saved data', async () => {
    localStorage.setItem('grammatica.exam.7.pending', '{not json')

    const exam = await started()

    expect(exam.status).toBe('running')
  })
})

describe('autosave', () => {
  it('saves a choice shortly after it is made, never showing a verdict', async () => {
    const exam = await started()

    exam.setAnswer(101, { selected: [1] })
    expect(exam.saving).toBe('dirty')
    expect(mocked.saveExamAnswer).not.toHaveBeenCalled()

    await advance(CHOICE_DELAY_MS)

    expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 101, { selected: [1] })
    expect(exam.saving).toBe('saved')
    expect(exam.answeredCount).toBe(1)
  })

  it('waits for a pause in typing and saves only the final text', async () => {
    const exam = await started()

    for (const text of ['s', 'sc', 'schr', 'schrijf']) {
      exam.setAnswer(103, { blanks: [text, ''] })
      await advance(300)
    }
    expect(mocked.saveExamAnswer).not.toHaveBeenCalled()

    await advance(TYPING_DELAY_MS)

    expect(mocked.saveExamAnswer).toHaveBeenCalledTimes(1)
    expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 103, { blanks: ['schrijf', ''] })
  })

  it('saves different questions independently', async () => {
    const exam = await started()

    exam.setAnswer(101, { selected: [0] })
    exam.setAnswer(105, { index: 2 })
    await advance(CHOICE_DELAY_MS)

    expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 101, { selected: [0] })
    expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 105, { index: 2 })
  })

  it('keeps the answer in localStorage until the server has it', async () => {
    const exam = await started()
    mocked.saveExamAnswer.mockReturnValue(new Promise(() => {}))

    exam.setAnswer(101, { selected: [1] })

    expect(JSON.parse(pending()!)).toEqual({ 101: { answer: { selected: [1] } } })

    mocked.saveExamAnswer.mockResolvedValue()
    const fresh = await started()
    expect(fresh.status).toBe('running')
  })

  it('removes the local copy once saved', async () => {
    const exam = await started()

    exam.setAnswer(101, { selected: [1] })
    await advance(CHOICE_DELAY_MS)

    expect(pending()).toBeNull()
  })

  it('does not count an unchanged shuffled order as an answer, but a move is saved', async () => {
    const exam = await started()
    expect(exam.progress.find((p) => p.id === 104)?.state).toBe('none')

    exam.setAnswer(104, { order: ['Gisteren', 'ging', 'ik', 'zwemmen'] })
    await advance(CHOICE_DELAY_MS)

    expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 104, {
      order: ['Gisteren', 'ging', 'ik', 'zwemmen'],
    })
    expect(exam.progress.find((p) => p.id === 104)?.state).toBe('complete')
  })

  it('saves half-finished answers and marks them as partial', async () => {
    const exam = await started()

    exam.setAnswer(103, { blanks: ['schrijf', ''] })
    exam.setAnswer(106, { roles: ['onderwerp', null, null] })
    await advance(TYPING_DELAY_MS)

    expect(mocked.saveExamAnswer).toHaveBeenCalledTimes(2)
    expect(exam.progress.find((p) => p.id === 103)?.state).toBe('partial')
    expect(exam.progress.find((p) => p.id === 106)?.state).toBe('partial')
    expect(exam.answeredCount).toBe(2)
  })

  it('removes a saved answer from the server when the student clears it', async () => {
    const exam = await started()
    exam.setAnswer(102, { selected: [0, 1] })
    await advance(CHOICE_DELAY_MS)

    exam.setAnswer(102, { selected: [] })
    await advance(CHOICE_DELAY_MS)

    expect(mocked.deleteExamAnswer).toHaveBeenCalledWith(7, 102)
    expect(exam.saving).toBe('saved')
    expect(exam.progress.find((p) => p.id === 102)?.state).toBe('none')
  })

  it('does nothing when an answer that was never saved is cleared again', async () => {
    const exam = await started()

    exam.setAnswer(102, { selected: [0] })
    exam.setAnswer(102, { selected: [] })
    await advance(CHOICE_DELAY_MS)

    expect(mocked.saveExamAnswer).not.toHaveBeenCalled()
    expect(mocked.deleteExamAnswer).not.toHaveBeenCalled()
    expect(exam.saving).toBe('saved')
  })

  it('saves again when the answer changed while it was being saved', async () => {
    const exam = await started()
    let finishFirst: () => void = () => {}
    mocked.saveExamAnswer.mockImplementationOnce(() => new Promise<void>((r) => (finishFirst = r)))

    exam.setAnswer(101, { selected: [0] })
    await advance(CHOICE_DELAY_MS)
    expect(exam.saving).toBe('saving')

    exam.setAnswer(101, { selected: [2] })
    finishFirst()
    await advance(CHOICE_DELAY_MS + 10)

    expect(mocked.saveExamAnswer).toHaveBeenCalledTimes(2)
    expect(mocked.saveExamAnswer).toHaveBeenLastCalledWith(7, 101, { selected: [2] })
    expect(exam.saving).toBe('saved')
  })

  it('shows an error and retries when the connection is lost', async () => {
    const exam = await started()
    mocked.saveExamAnswer.mockRejectedValueOnce(new TypeError('offline'))

    exam.setAnswer(101, { selected: [1] })
    await advance(CHOICE_DELAY_MS)

    expect(exam.saving).toBe('error')
    expect(pending()).not.toBeNull()

    await advance(RETRY_DELAY_MS)

    expect(mocked.saveExamAnswer).toHaveBeenCalledTimes(2)
    expect(exam.saving).toBe('saved')
    expect(pending()).toBeNull()
  })

  it('does not retry an answer the server rejected', async () => {
    const exam = await started()
    mocked.saveExamAnswer.mockRejectedValue(new ApiError(422, { errors: {} }))

    exam.setAnswer(101, { selected: [1] })
    await advance(CHOICE_DELAY_MS + RETRY_DELAY_MS * 3)

    expect(mocked.saveExamAnswer).toHaveBeenCalledTimes(1)
    expect(exam.saving).toBe('error')
  })

  it('finishes when the server says the time is up while saving', async () => {
    const exam = await started()
    mocked.saveExamAnswer.mockRejectedValue(new ApiError(409, null))
    mocked.submitExam.mockResolvedValue({} as never)

    exam.setAnswer(101, { selected: [1] })
    await advance(CHOICE_DELAY_MS)

    expect(mocked.submitExam).toHaveBeenCalledWith(7)
    expect(exam.status).toBe('finished')
    expect(pending()).toBeNull()
  })

  it('ignores answers once the exam is not running', async () => {
    const exam = await started(attempt({ finished_at: '2026-11-01T10:20:00Z' }))

    exam.setAnswer(101, { selected: [1] })
    await advance(CHOICE_DELAY_MS)

    expect(mocked.saveExamAnswer).not.toHaveBeenCalled()
  })

  it('navigates between questions without losing answers', async () => {
    const exam = await started()

    exam.setAnswer(101, { selected: [1] })
    exam.goTo(3)
    exam.goTo(99)
    exam.goTo(-1)

    expect(exam.index).toBe(3)
    expect(exam.current?.id).toBe(104)
    expect(exam.answers[101]).toEqual({ selected: [1] })
  })
})

describe('submitting', () => {
  it('saves everything first, then hands the exam in', async () => {
    const exam = await started()
    mocked.submitExam.mockResolvedValue({} as never)
    exam.setAnswer(101, { selected: [1] })
    exam.setAnswer(103, { blanks: ['schrijf', 'schrijft'] })

    const ok = await exam.submit()

    expect(ok).toBe(true)
    expect(mocked.saveExamAnswer).toHaveBeenCalledTimes(2)
    expect(mocked.submitExam).toHaveBeenCalledWith(7)
    expect(mocked.saveExamAnswer.mock.invocationCallOrder.at(-1)!).toBeLessThan(
      mocked.submitExam.mock.invocationCallOrder[0],
    )
    expect(exam.status).toBe('finished')
    expect(pending()).toBeNull()
  })

  it('does not hand in while answers could not be saved, and says so', async () => {
    const exam = await started()
    mocked.saveExamAnswer.mockRejectedValue(new TypeError('offline'))
    exam.setAnswer(101, { selected: [1] })

    const ok = await exam.submit()

    expect(ok).toBe(false)
    expect(mocked.submitExam).not.toHaveBeenCalled()
    expect(exam.status).toBe('running')
    expect(exam.submitError).toBe('unsaved')
    expect(exam.answers[101]).toEqual({ selected: [1] })
    expect(pending()).not.toBeNull()
  })

  it('can be tried again after a failure', async () => {
    const exam = await started()
    mocked.submitExam
      .mockRejectedValueOnce(new TypeError('offline'))
      .mockResolvedValueOnce({} as never)

    expect(await exam.submit()).toBe(false)
    expect(exam.submitError).toBe('network')
    expect(exam.status).toBe('running')

    expect(await exam.submit()).toBe(true)
    expect(exam.submitError).toBeNull()
    expect(exam.status).toBe('finished')
  })

  it('tells a server error from a lost connection', async () => {
    const exam = await started()
    mocked.submitExam.mockRejectedValue(new ApiError(500, null))

    await exam.submit()

    expect(exam.submitError).toBe('server')
  })

  it('ignores a second submit while the first is running', async () => {
    const exam = await started()
    let finish: () => void = () => {}
    mocked.submitExam.mockReturnValue(new Promise((r) => (finish = () => r({} as never))))

    const first = exam.submit()
    await vi.advanceTimersByTimeAsync(0)
    expect(exam.status).toBe('submitting')
    expect(await exam.submit()).toBe(false)
    finish()
    await first

    expect(mocked.submitExam).toHaveBeenCalledTimes(1)
  })

  it('flushes answers that are waiting for their delay', async () => {
    const exam = await started()
    mocked.submitExam.mockResolvedValue({} as never)

    exam.setAnswer(103, { blanks: ['schr', ''] }) // would wait 700 ms
    await exam.submit()

    expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 103, { blanks: ['schr', ''] })
  })
})

describe('the clock', () => {
  it('counts down from the time the server reported', async () => {
    const exam = await started(attempt({ remaining_seconds: 600 }))

    await advance(90_000)
    exam.tick()

    expect(exam.remaining).toBe(510)
  })

  it('follows the real clock, not the number of ticks (a sleeping tab skips ticks)', async () => {
    const exam = await started(attempt({ remaining_seconds: 600 }))

    vi.setSystemTime(new Date('2026-11-01T10:07:00Z'))
    exam.tick()

    expect(exam.remaining).toBe(180)
  })

  it('warns when the time runs low and critical', async () => {
    const exam = await started(attempt({ remaining_seconds: LOW_SECONDS + 100 }))
    expect(exam.urgency).toBe('normal')

    vi.setSystemTime(new Date(Date.now() + 101_000))
    exam.tick()
    expect(exam.urgency).toBe('low')

    vi.setSystemTime(new Date(Date.now() + (LOW_SECONDS - CRITICAL_SECONDS) * 1000))
    exam.tick()
    expect(exam.urgency).toBe('critical')
  })

  it('hands the exam in automatically when the time is up', async () => {
    const exam = await started(attempt({ remaining_seconds: 5 }))
    mocked.submitExam.mockResolvedValue({} as never)
    exam.setAnswer(101, { selected: [1] })

    vi.setSystemTime(new Date('2026-11-01T10:00:06Z'))
    exam.tick()
    await advance(0)

    expect(exam.remaining).toBe(0)
    expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 101, { selected: [1] })
    expect(mocked.submitExam).toHaveBeenCalledTimes(1)
    expect(exam.status).toBe('finished')
  })

  it('submits at the deadline even when answers could not be saved', async () => {
    const exam = await started(attempt({ remaining_seconds: 5 }))
    mocked.saveExamAnswer.mockRejectedValue(new TypeError('offline'))
    mocked.submitExam.mockResolvedValue({} as never)
    exam.setAnswer(101, { selected: [1] })

    vi.setSystemTime(new Date('2026-11-01T10:00:06Z'))
    exam.tick()
    await advance(0)

    expect(mocked.submitExam).toHaveBeenCalledTimes(1)
    expect(exam.status).toBe('finished')
  })

  it('keeps trying to hand in when offline at the deadline, without hammering the server', async () => {
    const exam = await started(attempt({ remaining_seconds: 5 }))
    mocked.submitExam.mockRejectedValue(new TypeError('offline'))
    vi.setSystemTime(new Date('2026-11-01T10:00:06Z'))

    exam.tick()
    await advance(0)
    exam.tick()
    exam.tick()
    await advance(0)
    expect(mocked.submitExam).toHaveBeenCalledTimes(1)
    expect(exam.status).toBe('running')

    vi.setSystemTime(new Date(Date.now() + RETRY_DELAY_MS + 100))
    exam.tick()
    await advance(0)

    expect(mocked.submitExam).toHaveBeenCalledTimes(2)
  })

  it('does not tick when the exam is not running', async () => {
    const exam = await started(attempt({ finished_at: '2026-11-01T10:20:00Z' }))

    exam.tick()

    expect(mocked.submitExam).not.toHaveBeenCalled()
  })

  it('takes the deadline from the server again when asked', async () => {
    const exam = await started(attempt({ remaining_seconds: 600 }))
    mocked.attempt.mockResolvedValue(attempt({ remaining_seconds: 420 }))

    await exam.resync()
    exam.tick()

    expect(exam.remaining).toBe(420)
  })

  it('notices on resync that the server already scored the exam', async () => {
    const exam = await started()
    mocked.attempt.mockResolvedValue(
      attempt({ finished_at: '2026-11-01T10:30:00Z', remaining_seconds: 0 }),
    )

    await exam.resync()

    expect(exam.status).toBe('finished')
  })

  it('keeps counting locally when the server cannot be reached for a resync', async () => {
    const exam = await started(attempt({ remaining_seconds: 600 }))
    mocked.attempt.mockRejectedValue(new TypeError('offline'))

    await exam.resync()

    expect(exam.status).toBe('running')
    expect(exam.remaining).toBe(600)
  })
})
