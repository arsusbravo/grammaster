import { readJson, writeJson } from '@/lib/storage'
import type { Question, QuestionFilters } from '@/types/api'

// Practice questions kept on the device so practising can continue without a connection.
// These are the PUBLIC versions the API sends (no correct answers, no explanations): answers are only
// ever checked by the server, so offline answers are queued (see outbox.ts) and checked later.

const MAX_QUESTIONS = 150
const key = (userId: number) => `grammatica.offline.questions.${userId}`

export function cachedQuestions(userId: number): Question[] {
  return readJson<Question[]>(key(userId), [])
}

/** Remember questions that were just fetched (newest first, at most MAX_QUESTIONS). */
export function rememberQuestions(userId: number, questions: Question[]): void {
  if (questions.length === 0) return

  const fresh = new Set(questions.map((q) => q.id))
  const merged = [...questions, ...cachedQuestions(userId).filter((q) => !fresh.has(q.id))].slice(
    0,
    MAX_QUESTIONS,
  )

  // Out of space: keep the newer half instead of nothing.
  if (!writeJson(key(userId), merged))
    writeJson(key(userId), merged.slice(0, Math.ceil(merged.length / 2)))
}

/** Random cached questions matching the filters, skipping the ones already seen. */
export function pickOffline(
  userId: number,
  filters: QuestionFilters,
  exclude: number[],
  limit: number,
): Question[] {
  const skip = new Set(exclude)
  const matching = cachedQuestions(userId).filter(
    (q) =>
      !skip.has(q.id) &&
      (!filters.level || q.level?.slug === filters.level) &&
      (!filters.topic || q.topic?.slug === filters.topic) &&
      (!filters.difficulty || q.difficulty === filters.difficulty),
  )

  // Fisher-Yates shuffle
  for (let i = matching.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[matching[i], matching[j]] = [matching[j], matching[i]]
  }

  return matching.slice(0, limit)
}
