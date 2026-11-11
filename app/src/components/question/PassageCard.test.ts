import { beforeEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import * as fx from '@/design/fixtures'
import { i18n } from '@/i18n'
import { resetCollapsed } from '@/lib/passage'
import PassageCard from './PassageCard.vue'

const mountCard = () =>
  mount(PassageCard, { props: { passage: fx.passage }, global: { plugins: [i18n] } })

beforeEach(resetCollapsed)

describe('PassageCard', () => {
  it('shows the title and every paragraph of the story', () => {
    const wrapper = mountCard()

    expect(wrapper.text()).toContain('Tekst')
    expect(wrapper.text()).toContain('De fiets van Daan')
    expect(
      wrapper.findAll('p').filter((p) => p.classes().includes('whitespace-pre-line')),
    ).toHaveLength(3)
    expect(wrapper.text()).toContain('Op maandag was de fiets verdwenen.')
  })

  it('is a region named after the story', () => {
    const wrapper = mountCard()
    const region = wrapper.find('section')

    expect(region.attributes('aria-labelledby')).toBe(
      wrapper
        .findAll('p')
        .find((p) => p.text() === 'De fiets van Daan')
        ?.attributes('id'),
    )
  })

  it('hides and shows the story with a button that tells its state', async () => {
    const wrapper = mountCard()
    const button = wrapper.find('button')
    const body = () => wrapper.find(`#${button.attributes('aria-controls')}`)
    const shown = () => (body().element as HTMLElement).style.display !== 'none'

    expect(button.text()).toBe('Tekst verbergen')
    expect(button.attributes('aria-expanded')).toBe('true')
    expect(shown()).toBe(true)

    await button.trigger('click')
    expect(button.text()).toBe('Tekst tonen')
    expect(button.attributes('aria-expanded')).toBe('false')
    expect(shown()).toBe(false)

    await button.trigger('click')
    expect(button.attributes('aria-expanded')).toBe('true')
    expect(shown()).toBe(true)
  })

  it('stays folded when the next question about the same story appears', async () => {
    const first = mountCard()
    await first.find('button').trigger('click')
    first.unmount()

    const next = mountCard()
    expect(next.find('button').attributes('aria-expanded')).toBe('false')
  })
})
