import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/client'
import * as endpoints from '@/api/endpoints'
import { flushPromises, mountApp } from '@/test/helpers'
import type { Level, Topic, User } from '@/types/api'
import HomeView from './HomeView.vue'
import LoginView from './LoginView.vue'
import RegisterView from './RegisterView.vue'

vi.mock('@/api/endpoints')
const mocked = vi.mocked(endpoints)

const user: User = { id: 1, name: 'Sanne', email: 'sanne@example.com', level_id: 2 }
const levels: Level[] = [
  { id: 1, slug: 'vmbo', name: 'VMBO', sort_order: 1 },
  { id: 2, slug: 'havo', name: 'HAVO', sort_order: 2 },
]
const topic = (id: number, slug: string, name: string, count: number): Topic => ({
  id,
  slug,
  name,
  description: `Over ${name}`,
  sort_order: id,
  questions_count: count,
})

beforeEach(() => {
  localStorage.clear()
  vi.resetAllMocks()
})

describe('LoginView', () => {
  async function open(route = '/login') {
    const app = await mountApp(LoginView, { route })
    return app
  }

  it('logs in and goes to the page the student wanted', async () => {
    mocked.login.mockResolvedValue({ user, token: 'tok' })
    mocked.me.mockResolvedValue(user)
    const { wrapper, router } = await open('/login?redirect=/oefenen%3Flevel%3Dvmbo')

    await wrapper.find('input[type=email]').setValue('sanne@example.com')
    await wrapper.find('input[type=password]').setValue('geheim1234')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(mocked.login).toHaveBeenCalledWith('sanne@example.com', 'geheim1234')
    await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/oefenen?level=vmbo'))
  })

  it('never redirects to another site', async () => {
    mocked.login.mockResolvedValue({ user, token: 'tok' })
    mocked.me.mockResolvedValue(user)
    const { wrapper, router } = await open('/login?redirect=//evil.example')

    await wrapper.find('input[type=email]').setValue('a@b.nl')
    await wrapper.find('input[type=password]').setValue('x')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('home')
  })

  it('cannot be submitted while empty', async () => {
    const { wrapper } = await open()

    expect(wrapper.find('button[type=submit]').attributes('disabled')).toBeDefined()
    await wrapper.find('form').trigger('submit')
    expect(mocked.login).not.toHaveBeenCalled()
  })

  it('shows the server message when the credentials are wrong', async () => {
    mocked.login.mockRejectedValue(
      new ApiError(422, {
        errors: { email: ['Deze combinatie van e-mailadres en wachtwoord is niet bekend.'] },
      }),
    )
    const { wrapper } = await open()

    await wrapper.find('input[type=email]').setValue('sanne@example.com')
    await wrapper.find('input[type=password]').setValue('fout')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain(
      'Deze combinatie van e-mailadres en wachtwoord is niet bekend.',
    )
    expect(wrapper.find('input[type=email]').attributes('aria-invalid')).toBe('true')
    expect(wrapper.find('button[type=submit]').attributes('disabled')).toBeUndefined()
  })

  it('says so when the server cannot be reached', async () => {
    mocked.login.mockRejectedValue(new TypeError('offline'))
    const { wrapper } = await open()

    await wrapper.find('input[type=email]').setValue('a@b.nl')
    await wrapper.find('input[type=password]').setValue('x')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Geen verbinding met de server')
  })
})

describe('RegisterView', () => {
  it('registers with the chosen level', async () => {
    mocked.levels.mockResolvedValue(levels)
    mocked.register.mockResolvedValue({ user, token: 'tok' })
    mocked.me.mockResolvedValue(user)
    const { wrapper, router } = await mountApp(RegisterView, { route: '/registreren' })
    await flushPromises()

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Sanne')
    await inputs[1].setValue('sanne@example.com')
    await inputs[2].setValue('geheim1234')
    await inputs[3].setValue('geheim1234')
    await wrapper.findAll('[role=radio]')[1].trigger('click')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(mocked.register).toHaveBeenCalledWith({
      name: 'Sanne',
      email: 'sanne@example.com',
      password: 'geheim1234',
      password_confirmation: 'geheim1234',
      level_id: 2,
    })
    expect(router.currentRoute.value.name).toBe('home')
  })

  it('still works when the levels cannot be loaded', async () => {
    mocked.levels.mockRejectedValue(new TypeError('offline'))
    const { wrapper } = await mountApp(RegisterView, { route: '/registreren' })
    await flushPromises()

    expect(wrapper.find('[role=radiogroup]').exists()).toBe(false)
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('shows the field errors from the server', async () => {
    mocked.levels.mockResolvedValue(levels)
    mocked.register.mockRejectedValue(
      new ApiError(422, {
        errors: {
          email: ['E-mailadres is al in gebruik.'],
          password: ['Wachtwoord moet minimaal 8 tekens bevatten.'],
        },
      }),
    )
    const { wrapper } = await mountApp(RegisterView, { route: '/registreren' })
    await flushPromises()

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Sanne')
    await inputs[1].setValue('sanne@example.com')
    await inputs[2].setValue('kort')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('E-mailadres is al in gebruik.')
    expect(wrapper.text()).toContain('Wachtwoord moet minimaal 8 tekens bevatten.')
  })
})

describe('HomeView', () => {
  async function open() {
    localStorage.setItem('grammatica.token', 'tok')
    mocked.me.mockResolvedValue(user)
    mocked.levels.mockResolvedValue(levels)
    mocked.topics.mockImplementation(async (level) =>
      level === 'havo'
        ? [topic(1, 'lidwoorden', 'Lidwoorden', 4), topic(2, 'hoofdletters', 'Hoofdletters', 0)]
        : [topic(1, 'lidwoorden', 'Lidwoorden', 2)],
    )
    const app = await mountApp(HomeView, { route: '/' })
    await flushPromises()
    return app
  }

  it('greets the student and starts on their own level', async () => {
    const { wrapper } = await open()

    expect(wrapper.find('h1').text()).toBe('Hoi Sanne')
    expect(mocked.topics).toHaveBeenCalledWith('havo')
    const radios = wrapper.findAll('[role=radio]')
    expect(radios.map((r) => r.attributes('aria-checked'))).toEqual(['false', 'true'])
  })

  it('lists the topics with their question counts and disables empty ones', async () => {
    const { wrapper } = await open()
    const text = wrapper.text()

    expect(text).toContain('Alles door elkaar')
    expect(text).toContain('4 vragen')
    expect(text).toContain('Nog geen vragen')
    const buttons = wrapper.findAll('ul button')
    const empty = buttons.find((b) => b.text().includes('Hoofdletters'))!
    expect(empty.attributes('disabled')).toBeDefined()
  })

  it('starts practising a topic at the chosen level', async () => {
    const { wrapper, router } = await open()

    await wrapper
      .findAll('ul button')
      .find((b) => b.text().includes('Lidwoorden'))!
      .trigger('click')
    await flushPromises()

    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('practice'))
    expect(router.currentRoute.value.query).toEqual({ level: 'havo', topic: 'lidwoorden' })
  })

  it('starts a mixed session without a topic', async () => {
    const { wrapper, router } = await open()

    await wrapper.findAll('ul button')[0].trigger('click')
    await flushPromises()

    await vi.waitFor(() => expect(router.currentRoute.value.query).toEqual({ level: 'havo' }))
  })

  it('reloads the topics and remembers the level when it is changed', async () => {
    mocked.updateMe.mockResolvedValue({ ...user, level_id: 1 })
    const { wrapper } = await open()

    await wrapper.findAll('[role=radio]')[0].trigger('click')
    await flushPromises()

    expect(mocked.topics).toHaveBeenCalledWith('vmbo')
    expect(mocked.updateMe).toHaveBeenCalledWith({ level_id: 1 })
    expect(wrapper.text()).toContain('2 vragen')
  })

  it('offers a retry when the catalogue cannot be loaded', async () => {
    localStorage.setItem('grammatica.token', 'tok')
    mocked.me.mockResolvedValue(user)
    mocked.levels.mockRejectedValueOnce(new TypeError('offline'))
    const { wrapper } = await mountApp(HomeView, { route: '/' })
    await flushPromises()

    expect(wrapper.text()).toContain('Geen verbinding met de server')

    mocked.levels.mockResolvedValue(levels)
    mocked.topics.mockResolvedValue([topic(1, 'lidwoorden', 'Lidwoorden', 4)])
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'Opnieuw proberen')!
      .trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Lidwoorden')
  })
})
