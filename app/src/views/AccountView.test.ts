import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/client'
import * as endpoints from '@/api/endpoints'
import { flushPromises, mountApp } from '@/test/helpers'
import type { Level, User } from '@/types/api'
import AccountView from './AccountView.vue'

vi.mock('@/api/endpoints')
const mocked = vi.mocked(endpoints)

const user: User = { id: 1, name: 'Sanne', email: 'sanne@example.com', level_id: 3 }
const levels: Level[] = [
  { id: 1, slug: 'vmbo', name: 'VMBO', sort_order: 1 },
  { id: 3, slug: 'havo', name: 'HAVO', sort_order: 3 },
]

beforeEach(() => {
  localStorage.clear()
  localStorage.setItem('grammatica.token', 'tok')
  delete document.documentElement.dataset.theme
  vi.resetAllMocks()
  mocked.me.mockResolvedValue(user)
  mocked.levels.mockResolvedValue(levels)
})

async function open() {
  const app = await mountApp(AccountView, { route: '/account' })
  await flushPromises()
  return app
}

const button = (wrapper: Awaited<ReturnType<typeof open>>['wrapper'], label: string) =>
  wrapper.findAll('button').find((b) => b.text() === label)!
const inputByLabel = (wrapper: Awaited<ReturnType<typeof open>>['wrapper'], label: string) => {
  const id = wrapper
    .findAll('label')
    .find((l) => l.text() === label)!
    .attributes('for')!
  return wrapper.find(`[id="${id}"]`)
}

describe('details', () => {
  it('shows the name in an input and the e-mail address', async () => {
    const { wrapper } = await open()

    expect((inputByLabel(wrapper, 'Naam').element as HTMLInputElement).value).toBe('Sanne')
    expect(wrapper.text()).toContain('sanne@example.com')
  })

  it('can only be saved once the name has changed', async () => {
    const { wrapper } = await open()
    expect(button(wrapper, 'Naam opslaan').attributes('disabled')).toBeDefined()

    await inputByLabel(wrapper, 'Naam').setValue('Sanne de Vries')
    expect(button(wrapper, 'Naam opslaan').attributes('disabled')).toBeUndefined()

    await inputByLabel(wrapper, 'Naam').setValue('   ')
    expect(button(wrapper, 'Naam opslaan').attributes('disabled')).toBeDefined()
  })

  it('saves the new name and confirms it', async () => {
    mocked.updateMe.mockResolvedValue({ ...user, name: 'Sanne de Vries' })
    const { wrapper } = await open()

    await inputByLabel(wrapper, 'Naam').setValue('  Sanne de Vries ')
    await wrapper.findAll('form')[0].trigger('submit')
    await flushPromises()

    expect(mocked.updateMe).toHaveBeenCalledWith({ name: 'Sanne de Vries' })
    expect(wrapper.text()).toContain('Naam opgeslagen')
    expect((inputByLabel(wrapper, 'Naam').element as HTMLInputElement).value).toBe('Sanne de Vries')
    expect(button(wrapper, 'Naam opslaan').attributes('disabled')).toBeDefined()
  })

  it('hides the confirmation again when editing continues', async () => {
    mocked.updateMe.mockResolvedValue({ ...user, name: 'Anders' })
    const { wrapper } = await open()
    await inputByLabel(wrapper, 'Naam').setValue('Anders')
    await wrapper.findAll('form')[0].trigger('submit')
    await flushPromises()

    await inputByLabel(wrapper, 'Naam').setValue('Anders nog een keer')

    expect(wrapper.text()).not.toContain('Naam opgeslagen')
  })

  it("shows the server's message when the name is rejected", async () => {
    mocked.updateMe.mockRejectedValue(
      new ApiError(422, { errors: { name: ['Naam mag niet uit meer dan 255 tekens bestaan.'] } }),
    )
    const { wrapper } = await open()

    await inputByLabel(wrapper, 'Naam').setValue('x'.repeat(300))
    await wrapper.findAll('form')[0].trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Naam mag niet uit meer dan 255 tekens bestaan.')
    expect(wrapper.text()).not.toContain('Naam opgeslagen')
  })

  it('says so when the server cannot be reached', async () => {
    mocked.updateMe.mockRejectedValue(new TypeError('offline'))
    const { wrapper } = await open()

    await inputByLabel(wrapper, 'Naam').setValue('Nieuw')
    await wrapper.findAll('form')[0].trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Geen verbinding met de server')
  })
})

describe('password', () => {
  async function fill(
    wrapper: Awaited<ReturnType<typeof open>>['wrapper'],
    current = 'oudwachtwoord1',
    next = 'nieuwwachtwoord1',
    again = 'nieuwwachtwoord1',
  ) {
    await inputByLabel(wrapper, 'Huidig wachtwoord').setValue(current)
    await inputByLabel(wrapper, 'Nieuw wachtwoord').setValue(next)
    await inputByLabel(wrapper, 'Herhaal nieuw wachtwoord').setValue(again)
  }

  it('uses password fields that browsers can fill in correctly', async () => {
    const { wrapper } = await open()

    expect(inputByLabel(wrapper, 'Huidig wachtwoord').attributes()).toMatchObject({
      type: 'password',
      autocomplete: 'current-password',
    })
    expect(inputByLabel(wrapper, 'Nieuw wachtwoord').attributes()).toMatchObject({
      type: 'password',
      autocomplete: 'new-password',
    })
  })

  it('cannot be submitted while the current or new password is empty', async () => {
    const { wrapper } = await open()
    expect(button(wrapper, 'Wachtwoord wijzigen').attributes('disabled')).toBeDefined()

    await inputByLabel(wrapper, 'Huidig wachtwoord').setValue('x')
    expect(button(wrapper, 'Wachtwoord wijzigen').attributes('disabled')).toBeDefined()

    await inputByLabel(wrapper, 'Nieuw wachtwoord').setValue('y')
    expect(button(wrapper, 'Wachtwoord wijzigen').attributes('disabled')).toBeUndefined()
  })

  it('changes the password, empties the fields and says other devices are signed out', async () => {
    mocked.changePassword.mockResolvedValue()
    const { wrapper } = await open()
    await fill(wrapper)

    await wrapper.findAll('form')[1].trigger('submit')
    await flushPromises()

    expect(mocked.changePassword).toHaveBeenCalledWith({
      current_password: 'oudwachtwoord1',
      password: 'nieuwwachtwoord1',
      password_confirmation: 'nieuwwachtwoord1',
    })
    expect(wrapper.text()).toContain(
      'Wachtwoord gewijzigd. Op je andere apparaten moet je opnieuw inloggen.',
    )
    expect((inputByLabel(wrapper, 'Huidig wachtwoord').element as HTMLInputElement).value).toBe('')
    expect((inputByLabel(wrapper, 'Nieuw wachtwoord').element as HTMLInputElement).value).toBe('')
  })

  it('stays signed in on this device', async () => {
    mocked.changePassword.mockResolvedValue()
    const { wrapper, pinia } = await open()
    await fill(wrapper)

    await wrapper.findAll('form')[1].trigger('submit')
    await flushPromises()

    expect(localStorage.getItem('grammatica.token')).toBe('tok')
    expect(pinia.state.value.auth.token).toBe('tok')
  })

  it('shows what is wrong next to the right field', async () => {
    mocked.changePassword.mockRejectedValue(
      new ApiError(422, {
        errors: {
          current_password: ['Het huidige wachtwoord is onjuist.'],
          password: ['Wachtwoord moet minimaal 8 tekens bevatten.'],
        },
      }),
    )
    const { wrapper } = await open()
    await fill(wrapper, 'fout', 'kort', 'kort')

    await wrapper.findAll('form')[1].trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Het huidige wachtwoord is onjuist.')
    expect(wrapper.text()).toContain('Wachtwoord moet minimaal 8 tekens bevatten.')
    expect(inputByLabel(wrapper, 'Huidig wachtwoord').attributes('aria-invalid')).toBe('true')
    expect(wrapper.text()).not.toContain('Wachtwoord gewijzigd')
    // What was typed is kept so the student can correct it.
    expect((inputByLabel(wrapper, 'Nieuw wachtwoord').element as HTMLInputElement).value).toBe(
      'kort',
    )
  })

  it('says so when the server cannot be reached and keeps the input', async () => {
    mocked.changePassword.mockRejectedValue(new TypeError('offline'))
    const { wrapper } = await open()
    await fill(wrapper)

    await wrapper.findAll('form')[1].trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Geen verbinding met de server')
    expect((inputByLabel(wrapper, 'Nieuw wachtwoord').element as HTMLInputElement).value).toBe(
      'nieuwwachtwoord1',
    )
  })
})

describe('theme', () => {
  it('starts on the saved choice', async () => {
    localStorage.setItem('grammatica.theme', 'dark')
    const { wrapper } = await open()

    expect(
      wrapper
        .findAll('[role=radiogroup]')
        .at(-1)!
        .findAll('[role=radio]')
        .map((r) => r.attributes('aria-checked')),
    ).toEqual(['false', 'false', 'true'])
  })

  it('switches the whole app to dark or light at once and remembers it', async () => {
    const { wrapper } = await open()
    const radios = () => wrapper.find('[aria-labelledby=account-theme]').findAll('[role=radio]')

    await radios()[2].trigger('click')
    expect(document.documentElement.dataset.theme).toBe('dark')
    expect(localStorage.getItem('grammatica.theme')).toBe('dark')

    await radios()[1].trigger('click')
    expect(document.documentElement.dataset.theme).toBe('light')
    expect(localStorage.getItem('grammatica.theme')).toBe('light')
  })

  it('goes back to following the device', async () => {
    localStorage.setItem('grammatica.theme', 'dark')
    const { wrapper } = await open()

    await wrapper
      .find('[aria-labelledby=account-theme]')
      .findAll('[role=radio]')[0]
      .trigger('click')

    expect(document.documentElement.dataset.theme).toBeUndefined()
    expect(localStorage.getItem('grammatica.theme')).toBeNull()
  })

  it('explains that the choice belongs to this device', async () => {
    const { wrapper } = await open()

    expect(wrapper.find('[aria-labelledby=account-theme]').text()).toContain(
      'Wordt onthouden op dit apparaat.',
    )
  })
})

describe('level and logout', () => {
  it('changes the level', async () => {
    mocked.updateMe.mockResolvedValue({ ...user, level_id: 1 })
    const { wrapper } = await open()

    await wrapper
      .find('[aria-labelledby=account-level]')
      .findAll('[role=radio]')[0]
      .trigger('click')
    await flushPromises()

    expect(mocked.updateMe).toHaveBeenCalledWith({ level_id: 1 })
    expect(wrapper.text()).toContain('Opgeslagen')
  })

  it('logs out and goes to the login page', async () => {
    mocked.logout.mockResolvedValue()
    const { wrapper, router } = await open()

    await button(wrapper, 'Uitloggen').trigger('click')
    await flushPromises()

    expect(mocked.logout).toHaveBeenCalled()
    expect(localStorage.getItem('grammatica.token')).toBeNull()
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('login'))
  })
})
