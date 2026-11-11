import { ApiError } from '@/api/client'
import { readJson, writeJson } from '@/lib/storage'
import { newId } from '@/lib/uuid'
import type { Answer, AnswerResult, Question } from '@/types/api'

// Answers given without a connection. They wait here (per student, on this device) and the server checks
// them when the connection is back: the server stays the only place where answers are judged.

export interface QueuedAnswer {
  id: string
  /** One practice session; its answers end up in one attempt on the server. */
  sessionId: string
  /** The question as shown, so the review does not depend on the question cache. */
  question: Question
  answer: Answer
  timeSpentSeconds: number
  queuedAt: string
}

export interface ReviewedAnswer {
  id: string
  question: Question
  answer: Answer
  result: AnswerResult
  reviewedAt: string
}

export type CheckFn = (
  questionId: number,
  body: { answer: Answer; time_spent_seconds: number; attempt_id: number | null },
) => Promise<AnswerResult>

export interface SyncSummary {
  synced: number
  /** Answers the server will never accept (question removed, invalid): given up. */
  dropped: number
  remaining: number
  /** Why syncing stopped early, if it did. */
  stoppedBecause: 'network' | 'auth' | 'server' | null
}

const queueKey = (userId: number) => `grammatica.offline.queue.${userId}`
const reviewedKey = (userId: number) => `grammatica.offline.reviewed.${userId}`
const attemptsKey = (userId: number) => `grammatica.offline.attempts.${userId}`

const listeners = new Set<() => void>()
const notify = () => listeners.forEach((listener) => listener())

/** Be told whenever the queue or the reviewed list changes. */
export function onOutboxChange(listener: () => void): () => void {
  listeners.add(listener)
  return () => listeners.delete(listener)
}

export const queuedAnswers = (userId: number) => readJson<QueuedAnswer[]>(queueKey(userId), [])
export const reviewedAnswers = (userId: number) =>
  readJson<ReviewedAnswer[]>(reviewedKey(userId), [])

export function enqueueAnswer(
  userId: number,
  entry: Omit<QueuedAnswer, 'id' | 'queuedAt'>,
): QueuedAnswer {
  const queued: QueuedAnswer = {
    ...entry,
    id: newId(),
    queuedAt: new Date().toISOString(),
  }
  writeJson(queueKey(userId), [...queuedAnswers(userId), queued])
  notify()
  return queued
}

export function clearReviewed(userId: number): void {
  writeJson(reviewedKey(userId), [])
  notify()
}

function removeQueued(userId: number, id: string) {
  writeJson(
    queueKey(userId),
    queuedAnswers(userId).filter((q) => q.id !== id),
  )
}

function addReviewed(userId: number, entry: ReviewedAnswer) {
  writeJson(reviewedKey(userId), [...reviewedAnswers(userId), entry])
}

let running: Promise<SyncSummary> | null = null

/** Send the queued answers to the server, oldest first. Only one sync runs at a time. */
export function syncOutbox(userId: number, check: CheckFn): Promise<SyncSummary> {
  running ??= doSync(userId, check).finally(() => {
    running = null
  })
  return running
}

async function doSync(userId: number, check: CheckFn): Promise<SyncSummary> {
  const attempts = readJson<Record<string, number>>(attemptsKey(userId), {})
  const summary: SyncSummary = { synced: 0, dropped: 0, remaining: 0, stoppedBecause: null }

  for (const entry of queuedAnswers(userId)) {
    try {
      const result = await check(entry.question.id, {
        answer: entry.answer,
        time_spent_seconds: entry.timeSpentSeconds,
        attempt_id: attempts[entry.sessionId] ?? null,
      })

      attempts[entry.sessionId] = result.attempt_id
      writeJson(attemptsKey(userId), attempts)
      addReviewed(userId, {
        id: entry.id,
        question: entry.question,
        answer: entry.answer,
        result,
        reviewedAt: new Date().toISOString(),
      })
      removeQueued(userId, entry.id)
      summary.synced++
    } catch (e) {
      if (e instanceof ApiError && (e.status === 404 || e.status === 422)) {
        // The question was removed or the answer no longer fits: it will never work, so give up on it.
        removeQueued(userId, entry.id)
        summary.dropped++
        continue
      }

      summary.stoppedBecause =
        e instanceof ApiError ? (e.status === 401 ? 'auth' : 'server') : 'network'
      break
    }
  }

  summary.remaining = queuedAnswers(userId).length
  if (summary.remaining === 0) writeJson(attemptsKey(userId), {})
  if (summary.synced > 0 || summary.dropped > 0) notify()

  return summary
}
