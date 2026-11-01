// Shapes of the /api/v1 responses (see CLAUDE.md). Answers are validated on the server only.

export interface Level {
  id: number
  slug: string
  name: string
  sort_order: number
}

export interface Topic {
  id: number
  slug: string
  name: string
  description: string | null
  sort_order: number
  questions_count?: number
}

export interface User {
  id: number
  name: string
  email: string
  level_id: number | null
  level?: Level
}

export type QuestionType =
  'multiple_choice' | 'fill_blank' | 'order_words' | 'mark_error' | 'parse_sentence'

// What a student sends per question type.
export interface MultipleChoiceAnswer {
  selected: number[]
}
export interface FillBlankAnswer {
  blanks: (string | null)[]
}
export interface OrderWordsAnswer {
  order: string[]
}
export interface MarkErrorAnswer {
  index: number
}
export interface ParseSentenceAnswer {
  roles: (string | null)[]
}

interface QuestionBase {
  id: number
  prompt: string
  difficulty: number
  topic?: Topic
  level?: Level
}

// What the API sends before answering: never the solution.
export interface MultipleChoiceQuestion extends QuestionBase {
  type: 'multiple_choice'
  data: { options: string[]; multiple: boolean }
}
export interface FillBlankQuestion extends QuestionBase {
  type: 'fill_blank'
  data: { text: string; blanks: { hint: string | null }[] }
}
export interface OrderWordsQuestion extends QuestionBase {
  type: 'order_words'
  data: { words: string[] }
}
export interface MarkErrorQuestion extends QuestionBase {
  type: 'mark_error'
  data: { tokens: string[] }
}
export interface ParseSentenceQuestion extends QuestionBase {
  type: 'parse_sentence'
  data: { tokens: string[]; roles: string[] }
}

export type Question =
  | MultipleChoiceQuestion
  | FillBlankQuestion
  | OrderWordsQuestion
  | MarkErrorQuestion
  | ParseSentenceQuestion

export type Answer =
  MultipleChoiceAnswer | FillBlankAnswer | OrderWordsAnswer | MarkErrorAnswer | ParseSentenceAnswer

// What the API returns after checking a practice answer.
export interface CorrectAnswers {
  selected?: number[]
  blanks?: string[]
  accepted?: string[][]
  order?: string[]
  alternative_orders?: string[][]
  index?: number
  correction?: string
  roles?: string[]
}

export interface AnswerResult {
  question_id: number
  attempt_id: number
  is_correct: boolean
  /** Per blank / per token result for fill_blank and parse_sentence. */
  items: boolean[] | null
  correct_answer: CorrectAnswers
  explanation: string
}

export interface QuestionFilters {
  level?: string
  topic?: string
  difficulty?: number
}

// Exams

export interface ExamSummary {
  id: number
  title: string
  level: Level
  time_limit_minutes: number
  pass_mark_percent: number
  question_count: number
  total_points: number
  /** Set while the student has an unfinished attempt. */
  in_progress_attempt_id: number | null
  /** Decimal string from the API, e.g. "75.50". */
  best_score: string | null
  last_attempt_id: number | null
  attempts_count: number
}

export type ExamQuestion = Question & { points: number }

/** A running (or just finished) exam attempt: questions without solutions, plus saved answers. */
export interface ExamAttempt {
  id: number
  exam: { id: number; title: string; time_limit_minutes: number; pass_mark_percent: number }
  started_at: string
  ends_at: string
  remaining_seconds: number
  finished_at: string | null
  questions: ExamQuestion[]
  /** Saved answers keyed by question id. */
  answers: Record<string, Answer>
}

export interface ReviewItem {
  question: ExamQuestion
  /** What the student answered; null when left empty. */
  answer: Answer | null
  is_correct: boolean
  items: boolean[] | null
  points: number
  points_earned: number
  correct_answer: CorrectAnswers
  explanation: string
}

export interface ExamResult {
  id: number
  exam: { id: number; title: string; pass_mark_percent: number }
  score: number
  points_earned: number
  points_total: number
  passed: boolean
  started_at: string
  finished_at: string
  duration_seconds: number
  review: ReviewItem[]
}

// Progress

export interface TopicProgress {
  topic: { id: number; slug: string; name: string }
  answered: number
  correct: number
  /** Percentage correct, null when nothing was answered yet. */
  accuracy: number | null
}

export interface HistoryItem {
  id: number
  mode: 'practice' | 'exam'
  exam: { id: number; title: string } | null
  score: number
  /** Only for exams. */
  passed: boolean | null
  answered: number
  correct: number
  /** What `correct` is measured against: all questions of an exam, the answered ones of a practice session. */
  total: number
  started_at: string
  finished_at: string
}

export interface Progress {
  overall: { answered: number; correct: number; accuracy: number | null }
  topics: TopicProgress[]
  weak_topics: TopicProgress[]
  weak_criteria: { min_answers: number; below_percent: number }
  history: HistoryItem[]
}
