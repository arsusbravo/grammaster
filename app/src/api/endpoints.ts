import { api } from './client'
import type {
  Answer,
  AnswerResult,
  ExamAttempt,
  ExamResult,
  ExamSummary,
  Level,
  Progress,
  Question,
  QuestionFilters,
  Topic,
  User,
} from '@/types/api'

interface Wrapped<T> {
  data: T
}

const json = (body: unknown): RequestInit => ({ method: 'POST', body: JSON.stringify(body) })

function query(params: Record<string, unknown>): string {
  const search = new URLSearchParams()
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === null || value === '') continue
    if (Array.isArray(value)) value.forEach((v) => search.append(`${key}[]`, String(v)))
    else search.append(key, String(value))
  }
  const text = search.toString()
  return text ? `?${text}` : ''
}

export interface AuthPayload {
  user: User
  token: string
}

export async function login(email: string, password: string): Promise<AuthPayload> {
  return (await api<Wrapped<AuthPayload>>('/auth/login', json({ email, password }))).data
}

export async function register(input: {
  name: string
  email: string
  password: string
  password_confirmation: string
  level_id: number | null
}): Promise<AuthPayload> {
  return (await api<Wrapped<AuthPayload>>('/auth/register', json(input))).data
}

export async function logout(): Promise<void> {
  await api('/auth/logout', { method: 'POST' })
}

export async function me(): Promise<User> {
  return (await api<Wrapped<User>>('/auth/me')).data
}

export async function updateMe(input: { name?: string; level_id?: number | null }): Promise<User> {
  return (await api<Wrapped<User>>('/auth/me', { method: 'PATCH', body: JSON.stringify(input) }))
    .data
}

export async function levels(): Promise<Level[]> {
  return (await api<Wrapped<Level[]>>('/levels')).data
}

export async function topics(level?: string): Promise<Topic[]> {
  return (await api<Wrapped<Topic[]>>(`/topics${query({ level })}`)).data
}

export async function questions(
  filters: QuestionFilters & { limit?: number; exclude?: number[] },
): Promise<Question[]> {
  return (await api<Wrapped<Question[]>>(`/questions${query({ ...filters })}`)).data
}

export async function checkAnswer(
  questionId: number,
  body: { answer: Answer; time_spent_seconds?: number; attempt_id?: number | null },
): Promise<AnswerResult> {
  return (await api<Wrapped<AnswerResult>>(`/questions/${questionId}/answer`, json(body))).data
}

// Exams

export async function exams(level?: string): Promise<ExamSummary[]> {
  return (await api<Wrapped<ExamSummary[]>>(`/exams${query({ level })}`)).data
}

/** Start an exam, or get the running attempt back (the server decides which). */
export async function startExam(examId: number): Promise<ExamAttempt> {
  return (await api<Wrapped<ExamAttempt>>(`/exams/${examId}/start`, { method: 'POST' })).data
}

/** The attempt as it stands. A running attempt whose time ran out comes back finished. */
export async function attempt(
  attemptId: number,
  options: { background?: boolean } = {},
): Promise<ExamAttempt> {
  return (await api<Wrapped<ExamAttempt>>(`/attempts/${attemptId}`, options)).data
}

export async function saveExamAnswer(
  attemptId: number,
  questionId: number,
  answer: Answer,
): Promise<void> {
  await api(`/attempts/${attemptId}/answers/${questionId}`, {
    method: 'PUT',
    body: JSON.stringify({ answer }),
    background: true,
  })
}

export async function deleteExamAnswer(attemptId: number, questionId: number): Promise<void> {
  await api(`/attempts/${attemptId}/answers/${questionId}`, { method: 'DELETE', background: true })
}

export async function submitExam(attemptId: number): Promise<ExamResult> {
  return (await api<Wrapped<ExamResult>>(`/attempts/${attemptId}/submit`, { method: 'POST' })).data
}

export async function examResult(attemptId: number): Promise<ExamResult> {
  return (await api<Wrapped<ExamResult>>(`/attempts/${attemptId}/result`)).data
}

export async function progress(): Promise<Progress> {
  return (await api<Wrapped<Progress>>('/progress')).data
}

export async function changePassword(input: {
  current_password: string
  password: string
  password_confirmation: string
}): Promise<void> {
  await api('/auth/password', { method: 'PUT', body: JSON.stringify(input) })
}
