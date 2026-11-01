import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as endpoints from '@/api/endpoints'
import { ApiError } from '@/api/client'
import { errorKind, type ErrorKind } from '@/api/errors'
import { answerProgress, emptyAnswer, hasAnswer, type AnswerProgress } from '@/lib/answers'
import { readStorage, writeStorage } from '@/lib/storage'
import type { Answer, ExamAttempt, ExamQuestion } from '@/types/api'

/** Typing is saved after a pause; discrete choices (a click, a drag) almost immediately. */
export const TYPING_DELAY_MS = 700
export const CHOICE_DELAY_MS = 100
export const RETRY_DELAY_MS = 5000
/** Below these many seconds the timer warns. */
export const LOW_SECONDS = 300
export const CRITICAL_SECONDS = 60

export type ExamStatus = 'idle' | 'loading' | 'running' | 'submitting' | 'finished' | 'error'
export type SaveState = 'saved' | 'dirty' | 'saving' | 'error'
export type Urgency = 'normal' | 'low' | 'critical'

const pendingKey = (attemptId: number) => `grammatica.exam.${attemptId}.pending`

interface Pending {
  /** null = the student cleared the answer */
  answer: Answer | null
}

/**
 * A running exam. Answers are saved to the server as the student works (no feedback), kept in
 * localStorage until the server has them, and the server is the source of truth for the deadline.
 * Refreshing the page, closing the tab or losing the connection must never lose an answer.
 */
export const useExamStore = defineStore('exam', () => {
  const attempt = ref<ExamAttempt | null>(null)
  const answers = ref<Record<number, Answer>>({})
  const touched = ref<Record<number, boolean>>({})
  const saveStates = ref<Record<number, SaveState>>({})
  const index = ref(0)
  const status = ref<ExamStatus>('idle')
  const error = ref<ErrorKind | null>(null)
  const submitError = ref<'unsaved' | 'network' | 'server' | null>(null)
  const remaining = ref(0)

  let deadline = 0
  let autoRetryAt = 0
  let retryTimer: ReturnType<typeof setTimeout> | null = null
  const serverHas = new Set<number>()
  const versions = new Map<number, number>()
  /** Version of each answer the server has, so an unchanged answer is not sent twice. */
  const savedVersions = new Map<number, number>()
  const timers = new Map<number, ReturnType<typeof setTimeout>>()
  const inflight = new Map<number, Promise<boolean>>()

  const questions = computed<ExamQuestion[]>(() => attempt.value?.questions ?? [])
  const current = computed(() => questions.value[index.value] ?? null)

  const progress = computed<{ id: number; state: AnswerProgress }[]>(() =>
    questions.value.map((q) => ({
      id: q.id,
      state: answerProgress(q, answers.value[q.id] ?? null, touched.value[q.id]),
    })),
  )
  const answeredCount = computed(() => progress.value.filter((p) => p.state !== 'none').length)

  const urgency = computed<Urgency>(() =>
    remaining.value <= CRITICAL_SECONDS
      ? 'critical'
      : remaining.value <= LOW_SECONDS
        ? 'low'
        : 'normal',
  )

  /** Overall state of saving, for the indicator. */
  const saving = computed<SaveState>(() => {
    const states = Object.values(saveStates.value)
    if (states.includes('error')) return 'error'
    if (states.includes('saving')) return 'saving'
    if (states.includes('dirty')) return 'dirty'
    return 'saved'
  })

  function reset() {
    for (const t of timers.values()) clearTimeout(t)
    timers.clear()
    if (retryTimer) clearTimeout(retryTimer)
    retryTimer = null
    inflight.clear()
    serverHas.clear()
    versions.clear()
    savedVersions.clear()
    attempt.value = null
    answers.value = {}
    touched.value = {}
    saveStates.value = {}
    index.value = 0
    status.value = 'idle'
    error.value = null
    submitError.value = null
    remaining.value = 0
    deadline = 0
    autoRetryAt = 0
  }

  /** Stop timers when leaving the screen. Unsaved answers stay in localStorage for the next visit. */
  function dispose() {
    for (const t of timers.values()) clearTimeout(t)
    timers.clear()
    if (retryTimer) clearTimeout(retryTimer)
    retryTimer = null
  }

  function setDeadline(secondsLeft: number) {
    deadline = Date.now() + secondsLeft * 1000
    remaining.value = Math.max(0, Math.ceil(secondsLeft))
  }

  // --- local persistence of answers the server does not have yet ---------------------------------

  function persistPending() {
    if (!attempt.value) return
    const pending: Record<number, Pending> = {}

    for (const [id, state] of Object.entries(saveStates.value)) {
      if (state === 'saved') continue
      const qid = Number(id)
      const q = questions.value.find((x) => x.id === qid)
      if (!q) continue
      pending[qid] = {
        answer: hasAnswer(q, answers.value[qid] ?? null, touched.value[qid])
          ? answers.value[qid]
          : null,
      }
    }

    writeStorage(
      pendingKey(attempt.value.id),
      Object.keys(pending).length ? JSON.stringify(pending) : null,
    )
  }

  function restorePending(attemptId: number) {
    const raw = readStorage(pendingKey(attemptId))
    if (!raw) return

    let pending: Record<string, Pending>
    try {
      pending = JSON.parse(raw)
    } catch {
      return
    }

    for (const [id, entry] of Object.entries(pending)) {
      const q = questions.value.find((x) => x.id === Number(id))
      if (!q) continue
      const qid = q.id

      answers.value[qid] = entry.answer ?? emptyAnswer(q)
      touched.value[qid] = entry.answer !== null
      saveStates.value[qid] = 'dirty'
      versions.set(qid, (versions.get(qid) ?? 0) + 1)
      schedule(qid, 0)
    }
  }

  // --- loading -----------------------------------------------------------------------------------

  async function load(attemptId: number) {
    reset()
    status.value = 'loading'

    let loaded: ExamAttempt
    try {
      loaded = await endpoints.attempt(attemptId)
    } catch (e) {
      error.value = errorKind(e)
      status.value = 'error'
      return
    }

    attempt.value = loaded
    if (loaded.finished_at) {
      status.value = 'finished'
      return
    }

    for (const q of loaded.questions) {
      const saved = loaded.answers[q.id]
      if (saved) {
        answers.value[q.id] = saved
        touched.value[q.id] = true
        serverHas.add(q.id)
      } else {
        answers.value[q.id] = emptyAnswer(q)
        touched.value[q.id] = false
      }
      saveStates.value[q.id] = 'saved'
    }

    restorePending(attemptId)
    setDeadline(loaded.remaining_seconds)

    // Continue where the student left off: the first question that has no answer yet.
    const firstOpen = progress.value.findIndex((p) => p.state === 'none')
    index.value = firstOpen === -1 ? 0 : firstOpen
    status.value = 'running'
  }

  function goTo(position: number) {
    if (position >= 0 && position < questions.value.length) index.value = position
  }

  // --- answering and autosave ---------------------------------------------------------------------

  function setAnswer(questionId: number, answer: Answer) {
    if (status.value !== 'running') return
    const q = questions.value.find((x) => x.id === questionId)
    if (!q) return

    answers.value[questionId] = answer
    touched.value[questionId] = true
    versions.set(questionId, (versions.get(questionId) ?? 0) + 1)
    saveStates.value[questionId] = 'dirty'
    persistPending()
    schedule(questionId, q.type === 'fill_blank' ? TYPING_DELAY_MS : CHOICE_DELAY_MS)
  }

  function schedule(questionId: number, delay: number) {
    const existing = timers.get(questionId)
    if (existing) clearTimeout(existing)
    timers.set(
      questionId,
      setTimeout(() => {
        timers.delete(questionId)
        void save(questionId)
      }, delay),
    )
  }

  function save(questionId: number): Promise<boolean> {
    const run = doSave(questionId).finally(() => {
      if (inflight.get(questionId) === run) inflight.delete(questionId)
    })
    inflight.set(questionId, run)
    return run
  }

  async function doSave(questionId: number): Promise<boolean> {
    const a = attempt.value
    const q = questions.value.find((x) => x.id === questionId)
    if (!a || !q) return true

    const version = versions.get(questionId) ?? 0
    if (savedVersions.get(questionId) === version) {
      saveStates.value[questionId] = 'saved'
      return true
    }
    const answer = answers.value[questionId]
    const keep = hasAnswer(q, answer ?? null, touched.value[questionId])

    // Nothing to save, and nothing saved before that needs removing.
    if (!keep && !serverHas.has(questionId)) {
      saveStates.value[questionId] = 'saved'
      persistPending()
      return true
    }

    saveStates.value[questionId] = 'saving'
    try {
      if (keep) {
        await endpoints.saveExamAnswer(a.id, questionId, answer)
        serverHas.add(questionId)
      } else {
        await endpoints.deleteExamAnswer(a.id, questionId)
        serverHas.delete(questionId)
      }
    } catch (e) {
      if (e instanceof ApiError && e.status === 409) {
        // The exam is finished or the time is up: the server scores what it has.
        await closedByServer()
        return false
      }
      saveStates.value[questionId] = 'error'
      // A rejected answer will not get better by retrying; a lost connection might.
      if (!(e instanceof ApiError && e.status === 422)) scheduleRetry()
      return false
    }

    // The student changed the answer while this was being saved.
    if ((versions.get(questionId) ?? 0) !== version) return save(questionId)

    savedVersions.set(questionId, version)
    saveStates.value[questionId] = 'saved'
    persistPending()
    return true
  }

  function scheduleRetry() {
    if (retryTimer) return
    retryTimer = setTimeout(() => {
      retryTimer = null
      for (const [id, state] of Object.entries(saveStates.value)) {
        if (state === 'error') void save(Number(id))
      }
    }, RETRY_DELAY_MS)
  }

  /** Save everything that is not saved yet. True when the server has all answers. */
  async function flush(): Promise<boolean> {
    for (const t of timers.values()) clearTimeout(t)
    timers.clear()

    const results = await Promise.all(
      Object.keys(saveStates.value).map(async (id) => {
        const qid = Number(id)
        await inflight.get(qid)
        return saveStates.value[qid] === 'saved' ? true : save(qid)
      }),
    )

    return results.every(Boolean)
  }

  // --- finishing ----------------------------------------------------------------------------------

  /** The server says the exam is over (409). Make sure it is scored, then show the result. */
  async function closedByServer() {
    const a = attempt.value
    if (!a || status.value === 'finished') return
    try {
      await endpoints.submitExam(a.id)
    } catch {
      // Scoring also happens when the attempt is opened again.
    }
    writeStorage(pendingKey(a.id), null)
    status.value = 'finished'
  }

  /** Hand in the exam. `auto` is the deadline passing: submit even if some answers could not be saved. */
  async function submit(auto = false): Promise<boolean> {
    const a = attempt.value
    if (!a || status.value !== 'running') return false

    status.value = 'submitting'
    submitError.value = null

    const flushed = await flush()
    if ((status.value as ExamStatus) === 'finished') return true

    if (!flushed && !auto) {
      status.value = 'running'
      submitError.value = 'unsaved'
      return false
    }

    try {
      await endpoints.submitExam(a.id)
    } catch (e) {
      status.value = 'running'
      submitError.value = errorKind(e) === 'network' ? 'network' : 'server'
      return false
    }

    writeStorage(pendingKey(a.id), null)
    status.value = 'finished'
    return true
  }

  // --- the clock ----------------------------------------------------------------------------------

  /** Call once per second. Hands the exam in when the time is up. */
  function tick() {
    if (!attempt.value || status.value !== 'running') return

    remaining.value = Math.max(0, Math.ceil((deadline - Date.now()) / 1000))

    if (remaining.value === 0 && Date.now() >= autoRetryAt) {
      autoRetryAt = Date.now() + RETRY_DELAY_MS
      void submit(true)
    }
  }

  /** Take the deadline from the server again (tab was asleep, connection came back). */
  async function resync() {
    const a = attempt.value
    if (!a || status.value !== 'running') return

    try {
      const fresh = await endpoints.attempt(a.id, { background: true })
      if (fresh.finished_at) {
        status.value = 'finished'
        return
      }
      setDeadline(fresh.remaining_seconds)
    } catch {
      // Keep counting on the local clock.
    }
    for (const [id, state] of Object.entries(saveStates.value)) {
      if (state === 'error') void save(Number(id))
    }
  }

  return {
    attempt,
    answers,
    saveStates,
    index,
    status,
    error,
    submitError,
    remaining,
    questions,
    current,
    progress,
    answeredCount,
    urgency,
    saving,
    load,
    goTo,
    setAnswer,
    flush,
    submit,
    tick,
    resync,
    dispose,
    reset,
  }
})
