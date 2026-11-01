import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/client'
import * as endpoints from '@/api/endpoints'
import * as fx from '@/design/fixtures'
import { flushPromises, mountApp } from '@/test/helpers'
import type { Question, User } from '@/types/api'
import PracticeView from './PracticeView.vue'

vi.mock('@/api/endpoints')
const mocked = vi.mocked(endpoints)

const user: User = { id: 1, name: 'Sanne', email: 'sanne@example.com', level_id: 1 }

const mc: Question = {
  ...fx.multipleChoice,
  id: 11,
  topic: {
    id: 1,
    slug: 'werkwoordspelling',
    name: 'Werkwoordspelling',
    description: null,
    sort_order: 1,
  },
  level: { id: 1, slug: 'vmbo', name: 'VMBO', sort_order: 1 },
}
const mark: Question = { ...fx.markError, id: 12 }

beforeEach(() => {
  localStorage.clear()
  localStorage.setItem('grammatica.token', 'tok')
  vi.resetAllMocks()
  mocked.me.mockResolvedValue(user)
})

async function open(questions: Question[] = [mc, mark]) {
  mocked.questions.mockResolvedValue(questions)
  const app = await mountApp(PracticeView, { route: '/oefenen?level=vmbo&topic=werkwoordspelling' })
  await flushPromises()
  return app
}

const button = (wrapper: Awaited<ReturnType<typeof open>>['wrapper']) =>
  wrapper.find('button[type=submit]')

describe('PracticeView', () => {
  it('loads questions for the chosen level and topic and shows the first', async () => {
    const { wrapper } = await open()

    expect(mocked.questions).toHaveBeenCalledWith(
      expect.objectContaining({ level: 'vmbo', topic: 'werkwoordspelling' }),
    )
    expect(wrapper.find('h2').text()).toBe('Welke zin is goed gespeld?')
    expect(wrapper.text()).toContain('Vraag 1')
    expect(wrapper.text()).toContain('Werkwoordspelling')
    expect(wrapper.text()).toContain('VMBO')
  })

  it('shows a loading state while questions are fetched', async () => {
    mocked.questions.mockReturnValue(new Promise(() => {}))
    const { wrapper } = await mountApp(PracticeView, { route: '/oefenen?level=vmbo' })

    expect(wrapper.find('[aria-busy=true]').exists()).toBe(true)
  })

  it('only lets the student check once the question is answered', async () => {
    const { wrapper } = await open()

    expect(button(wrapper).text()).toBe('Controleer')
    expect(button(wrapper).attributes('disabled')).toBeDefined()

    await wrapper.findAll('[role=radio]')[1].trigger('click')

    expect(button(wrapper).attributes('disabled')).toBeUndefined()
  })

  it('checks on the server, then shows verdict, explanation and a next button', async () => {
    const { wrapper } = await open()
    mocked.checkAnswer.mockResolvedValue({
      ...fx.result(11, true, { selected: [1] }),
      attempt_id: 5,
    })

    await wrapper.findAll('[role=radio]')[1].trigger('click')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(mocked.checkAnswer).toHaveBeenCalledWith(
      11,
      expect.objectContaining({ answer: { selected: [1] }, attempt_id: null }),
    )
    const feedback = wrapper.find('[role=status]')
    expect(feedback.text()).toContain('Goed')
    expect(feedback.text()).toContain('Dit is de uitleg.')
    expect(feedback.text()).toContain('En dit is een tweede alinea.')
    expect(feedback.findAll('p')).toHaveLength(2)
    expect(button(wrapper).text()).toBe('Volgende vraag')
    expect(wrapper.text()).toContain('1 van 1 goed')
    // The chosen answer can no longer be changed.
    expect(
      wrapper.findAll('[role=radio]').every((r) => r.attributes('disabled') !== undefined),
    ).toBe(true)
  })

  it('shows a wrong answer as wrong, with the correct answer marked', async () => {
    const { wrapper } = await open()
    mocked.checkAnswer.mockResolvedValue(fx.result(11, false, { selected: [1] }))

    await wrapper.findAll('[role=radio]')[0].trigger('click')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[role=status]').text()).toContain('Fout')
    const options = wrapper.findAll('[role=radio]')
    expect(options[0].text()).toContain('Fout')
    expect(options[1].text()).toContain('Juiste antwoord')
    expect(wrapper.text()).toContain('0 van 1 goed')
  })

  it('goes to the next question and keeps the session together', async () => {
    const { wrapper } = await open()
    mocked.checkAnswer.mockResolvedValueOnce({
      ...fx.result(11, true, { selected: [1] }),
      attempt_id: 9,
    })
    await wrapper.findAll('[role=radio]')[1].trigger('click')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    await wrapper.find('form').trigger('submit') // "Volgende vraag"
    await flushPromises()

    expect(wrapper.find('h2').text()).toBe('Tik op het woord dat fout is.')
    expect(wrapper.text()).toContain('Vraag 2')
    expect(wrapper.find('[role=status]').exists()).toBe(false)
    expect(button(wrapper).text()).toBe('Controleer')

    mocked.checkAnswer.mockResolvedValueOnce({
      ...fx.result(12, false, { index: 1, correction: 'fietsen' }),
      attempt_id: 9,
    })
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'naar')!
      .trigger('click')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(mocked.checkAnswer).toHaveBeenLastCalledWith(
      12,
      expect.objectContaining({ attempt_id: 9, answer: { index: 2 } }),
    )
    expect(wrapper.text()).toContain('Dit moet zijn: fietsen')
  })

  it('shows a summary after the last question', async () => {
    const { wrapper } = await open([mc])
    mocked.checkAnswer.mockResolvedValue(fx.result(11, true, { selected: [1] }))
    await wrapper.findAll('[role=radio]')[1].trigger('click')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Goed gedaan!')
    expect(wrapper.text()).toContain('1 vragen, 1 goed')
    expect(wrapper.text()).toContain('(100%)')
    expect(wrapper.text()).toContain('Nog een keer')
  })

  it('explains when there are no questions', async () => {
    const { wrapper } = await open([])

    expect(wrapper.text()).toContain('Geen vragen gevonden')
    expect(wrapper.text()).toContain('Andere keuze')
    expect(wrapper.text()).not.toContain('Nog een keer')
  })

  it('shows an error with a retry button when loading fails', async () => {
    mocked.questions.mockRejectedValueOnce(new TypeError('offline'))
    const { wrapper } = await mountApp(PracticeView, { route: '/oefenen?level=vmbo' })
    await flushPromises()

    expect(wrapper.text()).toContain('Geen verbinding met de server')

    mocked.questions.mockResolvedValueOnce([mc])
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Opnieuw proberen')!
      .trigger('click')
    await flushPromises()

    expect(wrapper.find('h2').text()).toBe('Welke zin is goed gespeld?')
  })

  it('keeps the answer and says so when checking fails', async () => {
    const { wrapper } = await open()
    mocked.checkAnswer.mockRejectedValueOnce(new ApiError(500, null))
    await wrapper.findAll('[role=radio]')[1].trigger('click')

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Controleren is niet gelukt')
    expect(wrapper.findAll('[role=radio]')[1].attributes('aria-checked')).toBe('true')
    expect(button(wrapper).text()).toBe('Controleer')
    expect(button(wrapper).attributes('disabled')).toBeUndefined()
  })

  it('leaves for the home screen with the stop button', async () => {
    const { wrapper, router } = await open()

    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('Stoppen'))!
      .trigger('click')
    await flushPromises()

    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('home'))
  })
})
