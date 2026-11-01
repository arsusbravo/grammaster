import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import { i18n } from '@/i18n'
import QuestionNavigator from './QuestionNavigator.vue'

const plugins = { global: { plugins: [i18n] } }

describe('QuestionNavigator', () => {
  const items = [
    { id: 1, state: 'complete' as const },
    { id: 2, state: 'partial' as const },
    { id: 3, state: 'none' as const },
  ]

  it('tells the state of every question in words, not just in looks', () => {
    const wrapper = mount(QuestionNavigator, { props: { items, current: 0 }, ...plugins })
    const buttons = wrapper.findAll('button')

    expect(buttons.map((b) => b.attributes('aria-label'))).toEqual([
      'Vraag 1, beantwoord',
      'Vraag 2, gedeeltelijk beantwoord',
      'Vraag 3, nog niet beantwoord',
    ])
  })

  it('marks the current question', () => {
    const wrapper = mount(QuestionNavigator, { props: { items, current: 1 }, ...plugins })
    const buttons = wrapper.findAll('button')

    expect(buttons.map((b) => b.attributes('aria-current'))).toEqual([undefined, 'step', undefined])
  })

  it('selects a question', async () => {
    const wrapper = mount(QuestionNavigator, { props: { items, current: 0 }, ...plugins })

    await wrapper.findAll('button')[2].trigger('click')

    expect(wrapper.emitted('select')).toEqual([[2]])
  })
})

describe('ConfirmDialog', () => {
  const props = { title: 'Zeker weten?', confirmLabel: 'Ja', cancelLabel: 'Nee' }

  function open(extra: Record<string, unknown> = {}) {
    return mount(ConfirmDialog, {
      props: { open: true, ...props, ...extra },
      slots: { default: '<p>Uitleg</p>' },
      attachTo: document.body,
    })
  }

  it('shows the title and text in a modal dialog when open', async () => {
    const wrapper = open()
    await nextTick()
    const dialog = wrapper.find('dialog')

    expect(dialog.attributes('open')).toBeDefined()
    expect(dialog.attributes('aria-labelledby')).toBe(wrapper.find('h2').attributes('id'))
    expect(wrapper.text()).toContain('Zeker weten?')
    expect(wrapper.text()).toContain('Uitleg')
    wrapper.unmount()
  })

  it('stays closed until opened, and follows the open state', async () => {
    const wrapper = open({ open: false })
    await nextTick()
    expect(wrapper.find('dialog').attributes('open')).toBeUndefined()

    await wrapper.setProps({ open: true })
    expect(wrapper.find('dialog').attributes('open')).toBeDefined()

    await wrapper.setProps({ open: false })
    expect(wrapper.find('dialog').attributes('open')).toBeUndefined()
    wrapper.unmount()
  })

  it('confirms and cancels', async () => {
    const wrapper = open()
    const [cancel, confirm] = wrapper.findAll('button')

    await confirm.trigger('click')
    expect(wrapper.emitted('confirm')).toHaveLength(1)

    await cancel.trigger('click')
    expect(wrapper.emitted('cancel')).toHaveLength(1)
    expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false])
    wrapper.unmount()
  })

  it('cancels with Escape and with a click on the backdrop', async () => {
    const wrapper = open()

    await wrapper.find('dialog').trigger('cancel')
    expect(wrapper.emitted('cancel')).toHaveLength(1)

    await wrapper.find('dialog').trigger('click') // target is the dialog itself: the backdrop
    expect(wrapper.emitted('cancel')).toHaveLength(2)

    await wrapper.find('h2').trigger('click') // inside the dialog: not a backdrop click
    expect(wrapper.emitted('cancel')).toHaveLength(2)
    wrapper.unmount()
  })

  it('cannot be dismissed or confirmed again while busy', async () => {
    const wrapper = open({ busy: true })

    await wrapper.find('dialog').trigger('cancel')
    await wrapper.findAll('button')[0].trigger('click')

    expect(wrapper.emitted('cancel')).toBeUndefined()
    expect(wrapper.findAll('button').every((b) => b.attributes('disabled') !== undefined)).toBe(
      true,
    )
    wrapper.unmount()
  })
})
