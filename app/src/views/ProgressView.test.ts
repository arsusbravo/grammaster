import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as endpoints from '@/api/endpoints'
import { flushPromises, mountApp } from '@/test/helpers'
import type { Level, Progress, TopicProgress, User } from '@/types/api'
import ProgressView from './ProgressView.vue'

vi.mock('@/api/endpoints')
const mocked = vi.mocked(endpoints)

const user: User = { id: 1, name: 'Sanne', email: 'sanne@example.com', level_id: 3 }
const levels: Level[] = [
  { id: 1, slug: 'vmbo', name: 'VMBO', sort_order: 1 },
  { id: 3, slug: 'havo', name: 'HAVO', sort_order: 3 },
]

const topic = (
  id: number,
  slug: string,
  name: string,
  answered: number,
  correct: number,
): TopicProgress => ({
  topic: { id, slug, name },
  answered,
  correct,
  accuracy: answered ? Math.round((correct / answered) * 1000) / 10 : null,
})

const spelling = topic(1, 'werkwoordspelling', 'Werkwoordspelling', 10, 4) // weak
const articles = topic(2, 'lidwoorden', 'Lidwoorden', 8, 7)
const unused = topic(3, 'leestekens', 'Leestekens', 0, 0)

function progress(overrides: Partial<Progress> = {}): Progress {
  return {
    overall: { answered: 18, correct: 11, accuracy: 61.1 },
    topics: [spelling, articles, unused],
    weak_topics: [spelling],
    weak_criteria: { min_answers: 3, below_percent: 70 },
    history: [
      {
        id: 21,
        mode: 'exam',
        exam: { id: 1, title: 'Oefenexamen VMBO' },
        score: 72.5,
        passed: true,
        // 8 answered of the 10 questions in the exam: the history measures against all 10
        answered: 8,
        correct: 7,
        total: 10,
        started_at: '2026-11-01T10:00:00Z',
        finished_at: '2026-11-01T10:20:00Z',
      },
      {
        id: 20,
        mode: 'exam',
        exam: { id: 2, title: 'Oefenexamen VWO' },
        score: 30,
        passed: false,
        answered: 8,
        correct: 3,
        total: 10,
        started_at: '2026-10-30T10:00:00Z',
        finished_at: '2026-10-30T10:30:00Z',
      },
      {
        id: 19,
        mode: 'practice',
        exam: null,
        score: 66.67,
        passed: null,
        answered: 3,
        correct: 2,
        total: 3,
        started_at: '2026-10-29T10:00:00Z',
        finished_at: '2026-10-29T10:05:00Z',
      },
    ],
    ...overrides,
  }
}

beforeEach(() => {
  localStorage.clear()
  localStorage.setItem('grammatica.token', 'tok')
  vi.resetAllMocks()
  mocked.me.mockResolvedValue(user)
  mocked.levels.mockResolvedValue(levels)
})

async function open(data: Progress = progress()) {
  mocked.progress.mockResolvedValue(data)
  const app = await mountApp(ProgressView, { route: '/voortgang' })
  await flushPromises()
  return app
}

describe('ProgressView', () => {
  it('shows the overall result', async () => {
    const { wrapper } = await open()
    const overall = wrapper.find('section').text()

    expect(overall).toContain('61% goed')
    expect(overall).toContain('11 van 18 vragen goed')
  })

  it('lists the weak topics with the rule behind them and a way to practise', async () => {
    const { wrapper } = await open()
    const weak = wrapper.find('[aria-labelledby=weak]')

    expect(weak.text()).toContain('Hier kun je nog winnen')
    expect(weak.text()).toContain('minder dan 70% goed hebt (na minstens 3 vragen)')
    expect(weak.text()).toContain('Werkwoordspelling')
    expect(weak.text()).toContain('40% goed')
    expect(weak.text()).toContain('10 vragen beantwoord')
    expect(weak.text()).not.toContain('Lidwoorden')
  })

  it("starts practising a weak topic at the student's own level", async () => {
    const { wrapper, router } = await open()

    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Oefen dit')!
      .trigger('click')

    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('practice'))
    expect(router.currentRoute.value.query).toEqual({ topic: 'werkwoordspelling', level: 'havo' })
  })

  it('practises a weak topic without a level when the student has none', async () => {
    mocked.me.mockResolvedValue({ ...user, level_id: null })
    const { wrapper, router } = await open()

    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Oefen dit')!
      .trigger('click')

    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('practice'))
    expect(router.currentRoute.value.query).toEqual({ topic: 'werkwoordspelling' })
  })

  it('encourages when nothing is weak', async () => {
    const { wrapper } = await open(progress({ weak_topics: [] }))

    expect(wrapper.find('[aria-labelledby=weak]').text()).toContain('Geen zwakke punten gevonden')
    expect(wrapper.findAll('button').some((b) => b.text() === 'Oefen dit')).toBe(false)
  })

  it('shows every topic with its accuracy as a labelled meter, and a text flag on weak ones', async () => {
    const { wrapper } = await open()
    const rows = wrapper.findAll('[aria-labelledby=topics] > ul > li')

    expect(rows).toHaveLength(3)
    const meters = wrapper.findAll('[role=meter]')
    expect(meters).toHaveLength(2)
    expect(meters[0].attributes('aria-valuenow')).toBe('40')
    expect(meters[0].attributes('aria-label')).toBe('Werkwoordspelling: 40% goed')
    expect(rows[0].text()).toContain('Extra oefening')
    expect(rows[1].text()).toContain('88% goed')
    expect(rows[1].text()).not.toContain('Extra oefening')
  })

  it('says when a topic has not been practised yet, without a meter', async () => {
    const { wrapper } = await open()
    const row = wrapper.findAll('[aria-labelledby=topics] > ul > li')[2]

    expect(row.text()).toContain('Leestekens')
    expect(row.text()).toContain('Nog niet geoefend')
    expect(row.find('[role=meter]').exists()).toBe(false)
  })

  it('lists the recent history with scores, results in words and a link to exam results', async () => {
    const { wrapper } = await open()
    const rows = wrapper.findAll('[aria-labelledby=history] li')

    expect(rows).toHaveLength(3)
    expect(rows[0].text()).toContain('Oefenexamen VMBO')
    expect(rows[0].text()).toContain('73%')
    // Measured against all 10 questions of the exam, not the 8 that were answered.
    expect(rows[0].text()).toContain('7 van 10 goed')
    expect(rows[0].text()).toContain('Geslaagd')
    expect(rows[0].find('a').attributes('href')).toBe('/examens/resultaat/21')
    expect(rows[1].text()).toContain('Niet geslaagd')
    expect(rows[2].text()).toContain('Oefensessie')
    expect(rows[2].text()).toContain('67%')
    expect(rows[2].text()).not.toMatch(/Geslaagd|Niet geslaagd/)
    expect(rows[2].find('a').exists()).toBe(false)
  })

  it('invites a new student to start practising instead of showing empty numbers', async () => {
    const empty = progress({
      overall: { answered: 0, correct: 0, accuracy: null },
      topics: [topic(1, 'werkwoordspelling', 'Werkwoordspelling', 0, 0)],
      weak_topics: [],
      history: [],
    })
    const { wrapper, router } = await open(empty)

    expect(wrapper.text()).toContain('Nog niets om te laten zien')
    expect(wrapper.find('[role=meter]').exists()).toBe(false)
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Begin met oefenen')!
      .trigger('click')
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('home'))
  })

  it('shows a loading state', async () => {
    mocked.progress.mockReturnValue(new Promise(() => {}))
    const { wrapper } = await mountApp(ProgressView, { route: '/voortgang' })

    expect(wrapper.find('[aria-busy=true]').exists()).toBe(true)
  })

  it('offers a retry when the progress cannot be loaded', async () => {
    mocked.progress.mockRejectedValueOnce(new TypeError('offline'))
    const { wrapper } = await mountApp(ProgressView, { route: '/voortgang' })
    await flushPromises()
    expect(wrapper.text()).toContain('Geen verbinding met de server')

    mocked.progress.mockResolvedValue(progress())
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Opnieuw proberen')!
      .trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('61% goed')
  })
})
