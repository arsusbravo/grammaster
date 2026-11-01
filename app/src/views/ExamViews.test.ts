import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/client'
import * as endpoints from '@/api/endpoints'
import * as fx from '@/design/fixtures'
import { flushPromises, mountApp } from '@/test/helpers'
import type {
  ExamAttempt,
  ExamQuestion,
  ExamResult,
  ExamSummary,
  Level,
  ReviewItem,
  User,
} from '@/types/api'
import ExamResultView from './ExamResultView.vue'
import ExamRunView from './ExamRunView.vue'
import ExamsView from './ExamsView.vue'

vi.mock('@/api/endpoints')
const mocked = vi.mocked(endpoints)

const user: User = { id: 1, name: 'Sanne', email: 'sanne@example.com', level_id: 1 }
const levels: Level[] = [
  { id: 1, slug: 'vmbo', name: 'VMBO', sort_order: 1 },
  { id: 4, slug: 'vwo', name: 'VWO', sort_order: 4 },
]

beforeEach(() => {
  localStorage.clear()
  localStorage.setItem('grammatica.token', 'tok')
  vi.resetAllMocks()
  mocked.me.mockResolvedValue(user)
  mocked.levels.mockResolvedValue(levels)
  mocked.saveExamAnswer.mockResolvedValue()
  mocked.deleteExamAnswer.mockResolvedValue()
})

const exam = (overrides: Partial<ExamSummary> = {}): ExamSummary => ({
  id: 1,
  title: 'Oefenexamen VMBO',
  level: levels[0],
  time_limit_minutes: 30,
  pass_mark_percent: 55,
  question_count: 10,
  total_points: 12,
  in_progress_attempt_id: null,
  best_score: null,
  last_attempt_id: null,
  attempts_count: 0,
  ...overrides,
})

describe('ExamsView', () => {
  async function open(list: ExamSummary[]) {
    mocked.exams.mockResolvedValue(list)
    const app = await mountApp(ExamsView, { route: '/examens', attachTo: document.body })
    await flushPromises()
    return app
  }

  it('lists exams with level, size, time limit and pass mark', async () => {
    const { wrapper } = await open([
      exam(),
      exam({
        id: 2,
        title: 'Oefenexamen VWO',
        level: levels[1],
        time_limit_minutes: 40,
        pass_mark_percent: 60,
        total_points: 20,
      }),
    ])

    expect(wrapper.text()).toContain('Oefenexamen VMBO')
    expect(wrapper.text()).toContain('10 vragen · 12 punten · 30 minuten · norm 55%')
    expect(wrapper.text()).toContain('20 punten · 40 minuten · norm 60%')
  })

  it('filters by level', async () => {
    const { wrapper } = await open([
      exam(),
      exam({ id: 2, title: 'Oefenexamen VWO', level: levels[1] }),
    ])

    await wrapper
      .findAll('[aria-pressed]')
      .find((b) => b.text() === 'VWO')!
      .trigger('click')

    expect(wrapper.text()).not.toContain('Oefenexamen VMBO')
    expect(wrapper.text()).toContain('Oefenexamen VWO')
  })

  it('says when there are no exams', async () => {
    const { wrapper } = await open([])

    expect(wrapper.text()).toContain('Er zijn nog geen examens')
  })

  it('shows earlier results and links to the last one', async () => {
    const { wrapper } = await open([
      exam({ attempts_count: 2, best_score: '75.50', last_attempt_id: 33 }),
    ])

    expect(wrapper.text()).toContain('Beste score: 76%')
    expect(wrapper.text()).toContain('2 keer gemaakt')
    expect(wrapper.find('a[href="/examens/resultaat/33"]').exists()).toBe(true)
  })

  it('asks before starting, since the clock starts running', async () => {
    const { wrapper } = await open([exam()])

    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Start examen')!
      .trigger('click')

    expect(mocked.startExam).not.toHaveBeenCalled()
    expect(wrapper.find('dialog').attributes('open')).toBeDefined()
    expect(wrapper.find('dialog').text()).toContain('Je hebt 30 minuten')
    wrapper.unmount()
  })

  it('starts the exam after confirming and opens the attempt', async () => {
    mocked.startExam.mockResolvedValue({ id: 55 } as ExamAttempt)
    const { wrapper, router } = await open([exam()])

    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Start examen')!
      .trigger('click')
    await wrapper
      .findAll('dialog button')
      .find((b) => b.text() === 'Start nu')!
      .trigger('click')
    await flushPromises()

    expect(mocked.startExam).toHaveBeenCalledWith(1)
    await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/examens/poging/55'))
    wrapper.unmount()
  })

  it('does not start when cancelled', async () => {
    const { wrapper } = await open([exam()])

    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Start examen')!
      .trigger('click')
    await wrapper
      .findAll('dialog button')
      .find((b) => b.text() === 'Annuleren')!
      .trigger('click')

    expect(mocked.startExam).not.toHaveBeenCalled()
    wrapper.unmount()
  })

  it('says so when starting fails and stays on the list', async () => {
    mocked.startExam.mockRejectedValue(new TypeError('offline'))
    const { wrapper, router } = await open([exam()])

    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Start examen')!
      .trigger('click')
    await wrapper
      .findAll('dialog button')
      .find((b) => b.text() === 'Start nu')!
      .trigger('click')
    await flushPromises()

    expect(wrapper.find('dialog').text()).toContain('kon niet worden gestart')
    expect(router.currentRoute.value.name).toBe('exams')
    wrapper.unmount()
  })

  it('continues a running attempt without starting a new one', async () => {
    const { wrapper, router } = await open([exam({ in_progress_attempt_id: 9 })])

    expect(wrapper.text()).toContain('Bezig')
    expect(wrapper.findAll('button').some((b) => b.text() === 'Start examen')).toBe(false)
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Doorgaan')!
      .trigger('click')

    await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/examens/poging/9'))
    expect(mocked.startExam).not.toHaveBeenCalled()
  })

  it('offers a retry when the list cannot be loaded', async () => {
    mocked.exams.mockRejectedValueOnce(new TypeError('offline'))
    const { wrapper } = await mountApp(ExamsView, { route: '/examens' })
    await flushPromises()
    expect(wrapper.text()).toContain('Geen verbinding met de server')

    mocked.exams.mockResolvedValue([exam()])
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Opnieuw proberen')!
      .trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Oefenexamen VMBO')
  })
})

const q = (question: ExamQuestion, id: number, points = 1): ExamQuestion =>
  ({ ...question, id, points }) as ExamQuestion
const attempt = (overrides: Partial<ExamAttempt> = {}): ExamAttempt => ({
  id: 7,
  exam: { id: 1, title: 'Oefenexamen VMBO', time_limit_minutes: 30, pass_mark_percent: 55 },
  started_at: '2026-11-01T10:00:00Z',
  ends_at: '2026-11-01T10:30:00Z',
  remaining_seconds: 1500,
  finished_at: null,
  questions: [
    q(fx.multipleChoice as ExamQuestion, 101),
    q(fx.markError as ExamQuestion, 102, 2),
    q(fx.parseSentence as ExamQuestion, 103),
  ],
  answers: {},
  ...overrides,
})

describe('ExamRunView', () => {
  async function run(a: ExamAttempt = attempt()) {
    mocked.attempt.mockResolvedValue(a)
    const app = await mountApp(ExamRunView, { route: '/examens/poging/7', attachTo: document.body })
    await flushPromises()
    return app
  }

  it('shows the exam with timer, position and question, and no feedback', async () => {
    const { wrapper } = await run()

    expect(wrapper.find('[role=timer]').text()).toBe('25:00')
    expect(wrapper.find('[role=timer]').attributes('aria-label')).toBe('Resterende tijd')
    expect(wrapper.text()).toContain('Vraag 1 van 3')
    expect(wrapper.find('h2').text()).toBe('Welke zin is goed gespeld?')
    expect(wrapper.text()).toContain('Alles opgeslagen')
    expect(wrapper.findAll('nav button')).toHaveLength(3)
    wrapper.unmount()
  })

  it('resumes where the student left off with their saved answers', async () => {
    const { wrapper } = await run(attempt({ answers: { 101: { selected: [1] } } }))

    expect(wrapper.text()).toContain('Vraag 2 van 3')
    await wrapper.findAll('nav button')[0].trigger('click')
    expect(wrapper.findAll('[role=radio]')[1].attributes('aria-checked')).toBe('true')
    wrapper.unmount()
  })

  it('saves an answer as it is given, without showing whether it is right', async () => {
    const { wrapper } = await run()

    await wrapper.findAll('[role=radio]')[1].trigger('click')

    await vi.waitFor(() =>
      expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 101, { selected: [1] }),
    )
    expect(wrapper.text()).not.toMatch(/Goed|Fout|Juiste antwoord|Uitleg/)
    expect(wrapper.findAll('nav button')[0].attributes('aria-label')).toBe('Vraag 1, beantwoord')
    wrapper.unmount()
  })

  it('moves between questions and keeps answers', async () => {
    const { wrapper } = await run()
    await wrapper.findAll('[role=radio]')[2].trigger('click')

    await wrapper
      .findAll('footer button')
      .find((b) => b.text() === 'Volgende')!
      .trigger('click')
    expect(wrapper.text()).toContain('Vraag 2 van 3')
    expect(wrapper.find('h2').text()).toBe('Tik op het woord dat fout is.')

    await wrapper
      .findAll('footer button')
      .find((b) => b.text() === 'Vorige')!
      .trigger('click')
    expect(wrapper.findAll('[role=radio]')[2].attributes('aria-checked')).toBe('true')

    await wrapper.findAll('nav button')[2].trigger('click')
    expect(wrapper.text()).toContain('Vraag 3 van 3')
    expect(wrapper.findAll('footer button').find((b) => b.text() === 'Inleveren')).toBeTruthy()
    wrapper.unmount()
  })

  it('shows a warning with an icon and text when saving fails', async () => {
    mocked.saveExamAnswer.mockRejectedValue(new TypeError('offline'))
    const { wrapper } = await run()

    await wrapper.findAll('[role=radio]')[1].trigger('click')

    await vi.waitFor(() =>
      expect(wrapper.text()).toContain('Niet opgeslagen. We proberen het opnieuw.'),
    )
    wrapper.unmount()
  })

  it('summarises what is missing before handing in', async () => {
    const { wrapper } = await run()
    await wrapper.findAll('[role=radio]')[1].trigger('click')

    await wrapper
      .findAll('header button')
      .find((b) => b.text() === 'Inleveren')!
      .trigger('click')

    const dialog = wrapper.find('dialog')
    expect(dialog.attributes('open')).toBeDefined()
    expect(dialog.text()).toContain('Je hebt 1 van 3 vragen beantwoord.')
    expect(dialog.text()).toContain('Nog niet beantwoord: vraag 2, 3.')
    expect(dialog.text()).toContain('Na het inleveren kun je niet meer terug.')
    wrapper.unmount()
  })

  it('hands in after confirming, then shows the result', async () => {
    mocked.submitExam.mockResolvedValue({} as ExamResult)
    const { wrapper, router } = await run()
    await wrapper.findAll('[role=radio]')[1].trigger('click')

    await wrapper
      .findAll('header button')
      .find((b) => b.text() === 'Inleveren')!
      .trigger('click')
    await wrapper
      .findAll('dialog button')
      .find((b) => b.text() === 'Nu inleveren')!
      .trigger('click')

    await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/examens/resultaat/7'))
    expect(mocked.saveExamAnswer).toHaveBeenCalledWith(7, 101, { selected: [1] })
    expect(mocked.submitExam).toHaveBeenCalledWith(7)
    wrapper.unmount()
  })

  it('does not hand in while answers are unsaved, and says so', async () => {
    mocked.saveExamAnswer.mockRejectedValue(new TypeError('offline'))
    const { wrapper } = await run()
    await wrapper.findAll('[role=radio]')[1].trigger('click')

    await wrapper
      .findAll('header button')
      .find((b) => b.text() === 'Inleveren')!
      .trigger('click')
    await wrapper
      .findAll('dialog button')
      .find((b) => b.text() === 'Nu inleveren')!
      .trigger('click')

    await vi.waitFor(() => expect(wrapper.text()).toContain('Niet alle antwoorden zijn opgeslagen'))
    expect(mocked.submitExam).not.toHaveBeenCalled()
    expect(wrapper.find('dialog').attributes('open')).toBeDefined()
    wrapper.unmount()
  })

  it('asks before leaving and explains that the clock keeps running', async () => {
    const { wrapper, router } = await run()

    await wrapper
      .findAll('header button')
      .find((b) => b.text() === 'Verlaten')!
      .trigger('click')
    const dialog = wrapper.findAll('dialog')[1]
    expect(dialog.attributes('open')).toBeDefined()
    expect(dialog.text()).toContain('De tijd loopt door')

    await dialog
      .findAll('button')
      .find((b) => b.text() === 'Verlaten')!
      .trigger('click')
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('exams'))
    wrapper.unmount()
  })

  it('goes straight to the result when the attempt is already finished', async () => {
    const { wrapper, router } = await run(
      attempt({ finished_at: '2026-11-01T10:25:00Z', remaining_seconds: 0 }),
    )

    await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/examens/resultaat/7'))
    wrapper.unmount()
  })

  it('shows the time-is-up message and hands in when the clock runs out', async () => {
    mocked.submitExam.mockReturnValue(new Promise(() => {}))
    const { wrapper } = await run(attempt({ remaining_seconds: 1 }))

    await vi.waitFor(() => expect(wrapper.find('[role=alertdialog]').exists()).toBe(true), {
      timeout: 4000,
    })

    expect(wrapper.find('[role=alertdialog]').text()).toContain('De tijd is om')
    expect(mocked.submitExam).toHaveBeenCalledWith(7)
    wrapper.unmount()
  })

  it('shows an error with a retry when the exam cannot be loaded', async () => {
    mocked.attempt.mockRejectedValueOnce(new TypeError('offline'))
    const { wrapper } = await mountApp(ExamRunView, { route: '/examens/poging/7' })
    await flushPromises()
    expect(wrapper.text()).toContain('Geen verbinding met de server')

    mocked.attempt.mockResolvedValue(attempt())
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Opnieuw proberen')!
      .trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Vraag 1 van 3')
    wrapper.unmount()
  })
})

const review = (overrides: Partial<ReviewItem> & { question: ExamQuestion }): ReviewItem => ({
  answer: null,
  is_correct: false,
  items: null,
  points: 1,
  points_earned: 0,
  correct_answer: {},
  explanation: 'Dit is de uitleg.\n\nEn nog een alinea.',
  ...overrides,
})

const examResult = (overrides: Partial<ExamResult> = {}): ExamResult => ({
  id: 7,
  exam: { id: 1, title: 'Oefenexamen VMBO', pass_mark_percent: 55 },
  score: 66.67,
  points_earned: 2,
  points_total: 3,
  passed: true,
  started_at: '2026-11-01T10:00:00Z',
  finished_at: '2026-11-01T10:18:40Z',
  duration_seconds: 1120,
  review: [
    review({
      question: q(fx.multipleChoice as ExamQuestion, 101),
      answer: { selected: [1] },
      is_correct: true,
      points_earned: 1,
      correct_answer: { selected: [1] },
    }),
    review({
      question: q(fx.markError as ExamQuestion, 102, 2),
      answer: { index: 0 },
      correct_answer: { index: 1, correction: 'fietsen' },
      points: 2,
    }),
    review({
      question: q(fx.parseSentence as ExamQuestion, 103),
      answer: null,
      items: [false, false, false],
      correct_answer: { roles: ['onderwerp', 'persoonsvorm', 'lijdend voorwerp'] },
    }),
  ],
  ...overrides,
})

describe('ExamResultView', () => {
  async function show(result: ExamResult = examResult()) {
    mocked.examResult.mockResolvedValue(result)
    const app = await mountApp(ExamResultView, { route: '/examens/resultaat/7' })
    await flushPromises()
    return app
  }

  it('shows the verdict in words, the score, the points and the time', async () => {
    const { wrapper } = await show()
    const text = wrapper.find('section').text()

    expect(text).toContain('Geslaagd')
    expect(text).toContain('67%')
    expect(text).toContain('2 van 3 punten')
    expect(text).toContain('Norm: 55%')
    expect(text).toContain('Gemaakt in 19 min')
  })

  it('says when the exam was not passed', async () => {
    const { wrapper } = await show(examResult({ passed: false, score: 30 }))

    expect(wrapper.find('section').text()).toContain('Niet geslaagd')
    expect(wrapper.find('section').text()).toContain('30%')
  })

  it('reviews every question with its verdict, points and explanation', async () => {
    const { wrapper } = await show()
    const items = wrapper.findAll('ol > li')

    expect(items).toHaveLength(3)
    expect(items[0].text()).toContain('Vraag 1')
    expect(items[0].text()).toContain('1 van 1 punten')
    expect(items[0].text()).toContain('Dit is de uitleg.')
    expect(items[0].text()).toContain('En nog een alinea.')
    expect(items[1].text()).toContain('Fout')
    expect(items[1].text()).toContain('0 van 2 punten')
    expect(items[1].text()).toContain('Dit moet zijn: fietsen')
  })

  it("shows the student's own answer next to the right one", async () => {
    const { wrapper } = await show()
    const mark = wrapper.findAll('ol > li')[1]
    const tokens = mark.findAll('button')

    expect(tokens[0].text()).toContain('Fout') // the word they picked
    expect(tokens[1].text()).toContain('Juiste antwoord') // the real error
  })

  it('marks a question that was left empty and still shows the answer', async () => {
    const { wrapper } = await show()
    const empty = wrapper.findAll('ol > li')[2]

    expect(empty.text()).toContain('Niet beantwoord')
    expect(empty.text()).toContain('Dit is: onderwerp')
    expect(empty.findAll('select').every((s) => s.attributes('disabled') !== undefined)).toBe(true)
  })

  it('can show only the wrong or empty questions', async () => {
    const { wrapper } = await show()

    await wrapper
      .findAll('[aria-pressed]')
      .find((b) => b.text().startsWith('Alleen fout'))!
      .trigger('click')

    const items = wrapper.findAll('ol > li')
    expect(items).toHaveLength(2)
    expect(items.map((i) => i.text()).join()).not.toContain('Vraag 1')
  })

  it('congratulates when there was nothing wrong', async () => {
    const perfect = examResult({ review: [examResult().review[0]] })
    const { wrapper } = await show(perfect)

    await wrapper
      .findAll('[aria-pressed]')
      .find((b) => b.text().startsWith('Alleen fout'))!
      .trigger('click')

    expect(wrapper.text()).toContain('Geen fouten. Goed gedaan!')
  })

  it('starts the exam again', async () => {
    mocked.startExam.mockResolvedValue({ id: 88 } as ExamAttempt)
    const { wrapper, router } = await show()

    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Opnieuw proberen')!
      .trigger('click')

    expect(mocked.startExam).toHaveBeenCalledWith(1)
    await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/examens/poging/88'))
  })

  it('goes back to the exam list', async () => {
    const { wrapper, router } = await show()

    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Terug naar examens')!
      .trigger('click')

    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('exams'))
  })

  it('explains when the result is not available', async () => {
    mocked.examResult.mockRejectedValue(new ApiError(409, null))
    const { wrapper } = await mountApp(ExamResultView, { route: '/examens/resultaat/7' })
    await flushPromises()

    expect(wrapper.text()).toContain('kon niet worden geladen')
  })

  it('offers a retry when offline', async () => {
    mocked.examResult.mockRejectedValueOnce(new TypeError('offline'))
    const { wrapper } = await mountApp(ExamResultView, { route: '/examens/resultaat/7' })
    await flushPromises()
    expect(wrapper.text()).toContain('Geen verbinding met de server')

    mocked.examResult.mockResolvedValue(examResult())
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Opnieuw proberen')!
      .trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Geslaagd')
  })
})
