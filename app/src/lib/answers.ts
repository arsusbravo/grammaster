import type {
  Answer,
  AnswerResult,
  FillBlankAnswer,
  MarkErrorAnswer,
  MultipleChoiceAnswer,
  OrderWordsAnswer,
  ParseSentenceAnswer,
  Question,
} from '@/types/api'

export const BLANK_MARKER = '{{blank}}'

/** The answer a question starts with (nothing chosen yet, words in the shuffled order received). */
export function emptyAnswer(question: Question): Answer {
  switch (question.type) {
    case 'multiple_choice':
      return { selected: [] }
    case 'fill_blank':
      return { blanks: question.data.blanks.map(() => '') }
    case 'order_words':
      return { order: [...question.data.words] }
    case 'mark_error':
      return { index: -1 }
    case 'parse_sentence':
      return { roles: question.data.tokens.map(() => null) }
  }
}

/** Can the student check this answer yet? Every part of the question needs an answer. */
export function isAnswerComplete(question: Question, answer: Answer | null): boolean {
  if (!answer) return false

  switch (question.type) {
    case 'multiple_choice':
      return (answer as MultipleChoiceAnswer).selected?.length > 0
    case 'fill_blank': {
      const { blanks } = answer as FillBlankAnswer
      return (
        blanks?.length === question.data.blanks.length &&
        blanks.every((b) => (b ?? '').trim() !== '')
      )
    }
    case 'order_words':
      return (answer as OrderWordsAnswer).order?.length === question.data.words.length
    case 'mark_error': {
      const { index } = answer as MarkErrorAnswer
      return Number.isInteger(index) && index >= 0 && index < question.data.tokens.length
    }
    case 'parse_sentence': {
      const { roles } = answer as ParseSentenceAnswer
      return roles?.length === question.data.tokens.length && roles.every((r) => !!r)
    }
  }
}

/** Split fill-in-the-blank text around its {{blank}} markers: n blanks give n + 1 text parts. */
export function splitBlankText(text: string): string[] {
  return text.split(BLANK_MARKER)
}

const same = (a: string, b: string) => a.trim().toLowerCase() === b.trim().toLowerCase()

/**
 * Of the accepted word orders, the one that matches the student's order in the most places.
 * Used only to show which words were in the wrong place; the server decides what is correct.
 */
export function closestOrder(given: string[], accepted: string[][]): string[] {
  let best = accepted[0] ?? []
  let bestScore = -1

  for (const order of accepted) {
    const score = order.reduce(
      (n, word, i) => n + (given[i] !== undefined && same(given[i], word) ? 1 : 0),
      0,
    )
    if (score > bestScore) {
      best = order
      bestScore = score
    }
  }

  return best
}

export type PartState = 'idle' | 'correct' | 'incorrect'

/** State of each word of an order_words answer after checking. */
export function orderStates(given: string[], result: AnswerResult): PartState[] {
  if (result.is_correct) return given.map(() => 'correct')

  const accepted = [
    result.correct_answer.order ?? [],
    ...(result.correct_answer.alternative_orders ?? []),
  ]
  const target = closestOrder(given, accepted)

  return given.map((word, i) =>
    target[i] !== undefined && same(word, target[i]) ? 'correct' : 'incorrect',
  )
}

/**
 * Has the student put anything into this answer? Different from isAnswerComplete: a half-filled
 * answer counts as answered (and is saved), an untouched one does not.
 * `touched` matters for order_words only: the shuffled starting order is not an answer.
 */
export function hasAnswer(question: Question, answer: Answer | null, touched = true): boolean {
  if (!answer) return false

  switch (question.type) {
    case 'multiple_choice':
      return ((answer as MultipleChoiceAnswer).selected?.length ?? 0) > 0
    case 'fill_blank':
      return ((answer as FillBlankAnswer).blanks ?? []).some((b) => (b ?? '').trim() !== '')
    case 'order_words':
      return touched
    case 'mark_error':
      return ((answer as MarkErrorAnswer).index ?? -1) >= 0
    case 'parse_sentence':
      return ((answer as ParseSentenceAnswer).roles ?? []).some((r) => !!r)
  }
}

export type AnswerProgress = 'none' | 'partial' | 'complete'

export function answerProgress(
  question: Question,
  answer: Answer | null,
  touched = true,
): AnswerProgress {
  if (!hasAnswer(question, answer, touched)) return 'none'
  return isAnswerComplete(question, answer) ? 'complete' : 'partial'
}
