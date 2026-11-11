import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as endpoints from '@/api/endpoints'
import { errorKind, type ErrorKind } from '@/api/errors'
import { emptyAnswer, isAnswerComplete } from '@/lib/answers'
import { enqueueAnswer } from '@/lib/offline/outbox'
import { pickOffline, rememberQuestions } from '@/lib/offline/questionCache'
import { newId } from '@/lib/uuid'
import { useAuthStore } from '@/stores/auth'
import type { Answer, AnswerResult, Question, QuestionFilters } from '@/types/api'

export const BATCH_SIZE = 10
/** Fetch the next batch in the background when this few questions are left. */
const PREFETCH_BELOW = 3

export type PracticeStatus = 'idle' | 'loading' | 'answering' | 'checking' | 'checked' | 'finished'

/**
 * One practice session: a stream of random questions for the chosen level/topic.
 * The server checks every answer (the client never knows the solution) and groups the answers of
 * a session in one attempt, so we hand the attempt id back with each answer.
 *
 * Without a connection the session keeps going: questions come from the copy kept on the device and
 * answers are queued (lib/offline/outbox.ts) to be checked by the server later.
 */
export const usePracticeStore = defineStore('practice', () => {
  const filters = ref<QuestionFilters>({})
  const queue = ref<Question[]>([])
  const current = ref<Question | null>(null)
  const answer = ref<Answer | null>(null)
  const result = ref<AnswerResult | null>(null)
  const status = ref<PracticeStatus>('idle')
  const error = ref<ErrorKind | null>(null)
  const attemptId = ref<number | null>(null)
  const seenIds = ref<number[]>([])
  const answered = ref(0)
  const correct = ref(0)
  const exhausted = ref(false)
  /** Practice session id: its offline answers end up in one attempt. */
  const sessionId = ref('')
  /** The current question was answered offline: no verdict yet, the server checks it later. */
  const queued = ref(false)
  /** Answers of this session waiting to be checked. */
  const queuedCount = ref(0)
  /** The questions come from this device because there is no connection. */
  const offlineQuestions = ref(false)
  let shownAt = Date.now()
  let fetching: Promise<void> | null = null

  const canCheck = computed(
    () =>
      status.value === 'answering' &&
      current.value !== null &&
      isAnswerComplete(current.value, answer.value),
  )

  function reset() {
    queue.value = []
    current.value = null
    answer.value = null
    result.value = null
    status.value = 'idle'
    error.value = null
    attemptId.value = null
    seenIds.value = []
    answered.value = 0
    correct.value = 0
    exhausted.value = false
    sessionId.value = ''
    queued.value = false
    queuedCount.value = 0
    offlineQuestions.value = false
    fetching = null
  }

  /** Fetch a batch of questions we have not shown yet. Concurrent calls share one request. */
  function loadBatch(): Promise<void> {
    if (exhausted.value) return Promise.resolve()
    if (fetching) return fetching

    fetching = fetchQuestions()
      .then((batch) => {
        queue.value.push(...batch)
        seenIds.value.push(...batch.map((q) => q.id))
        if (batch.length < BATCH_SIZE) exhausted.value = true
      })
      .finally(() => {
        fetching = null
      })

    return fetching
  }

  /** Questions from the server; without a connection, from the copy on this device. */
  async function fetchQuestions(): Promise<Question[]> {
    const userId = useAuthStore().user?.id

    try {
      const batch = await endpoints.questions({
        ...filters.value,
        limit: BATCH_SIZE,
        exclude: [...seenIds.value],
      })
      if (userId !== undefined) rememberQuestions(userId, batch)
      offlineQuestions.value = false
      return batch
    } catch (e) {
      if (errorKind(e) !== 'network' || userId === undefined) throw e

      const cached = pickOffline(userId, filters.value, seenIds.value, BATCH_SIZE)
      if (cached.length === 0) {
        // Out of questions on this device. If the student already answered some, that is simply the end of the session.
        if (answered.value + queuedCount.value > 0) return []
        throw e
      }

      offlineQuestions.value = true
      return cached
    }
  }

  function show(question: Question) {
    current.value = question
    answer.value = emptyAnswer(question)
    result.value = null
    queued.value = false
    error.value = null
    status.value = 'answering'
    shownAt = Date.now()
  }

  /** Take the next question from the queue, fetching more when needed. */
  async function advance() {
    if (queue.value.length === 0) {
      status.value = 'loading'
      current.value = null
      try {
        await loadBatch()
      } catch (e) {
        error.value = errorKind(e)
        return
      }
    }

    const next = queue.value.shift()
    if (!next) {
      current.value = null
      status.value = 'finished'
      return
    }

    show(next)
    if (queue.value.length < PREFETCH_BELOW && !exhausted.value) {
      loadBatch().catch(() => {
        // Not fatal: advance() will try again when the queue runs dry.
      })
    }
  }

  async function start(newFilters: QuestionFilters) {
    reset()
    sessionId.value = newId()
    filters.value = { ...newFilters }
    status.value = 'loading'
    await advance()
  }

  /** Retry after a failed load. */
  async function retry() {
    error.value = null
    await advance()
  }

  async function check() {
    if (!canCheck.value || !current.value || !answer.value) return

    status.value = 'checking'
    error.value = null

    const seconds = Math.max(0, Math.round((Date.now() - shownAt) / 1000))
    const question = current.value
    const given = answer.value

    try {
      const checked = await endpoints.checkAnswer(question.id, {
        answer: given,
        time_spent_seconds: seconds,
        attempt_id: attemptId.value,
      })

      result.value = checked
      attemptId.value = checked.attempt_id
      answered.value++
      if (checked.is_correct) correct.value++
      status.value = 'checked'
    } catch (e) {
      const userId = useAuthStore().user?.id

      // No connection: keep the answer on the device; the server checks it when the connection is back.
      if (errorKind(e) === 'network' && userId !== undefined) {
        enqueueAnswer(userId, {
          sessionId: sessionId.value,
          question,
          answer: given,
          timeSpentSeconds: seconds,
        })
        queued.value = true
        queuedCount.value++
        status.value = 'checked'
        return
      }

      // Keep the answer so the student can try again.
      error.value = errorKind(e)
      status.value = 'answering'
    }
  }

  async function next() {
    if (status.value !== 'checked') return
    await advance()
  }

  return {
    filters,
    queue,
    current,
    answer,
    result,
    status,
    error,
    attemptId,
    answered,
    correct,
    sessionId,
    queued,
    queuedCount,
    offlineQuestions,
    canCheck,
    start,
    retry,
    check,
    next,
    reset,
  }
})
