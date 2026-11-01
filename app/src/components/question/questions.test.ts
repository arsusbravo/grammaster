import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import * as fx from '@/design/fixtures'
import { i18n } from '@/i18n'
import { lastModel, mountQuestion } from '@/test/helpers'
import FillBlank from './FillBlank.vue'
import MarkError from './MarkError.vue'
import MultipleChoice from './MultipleChoice.vue'
import OrderWords from './OrderWords.vue'
import ParseSentence from './ParseSentence.vue'
import QuestionRenderer from './QuestionRenderer.vue'

describe('MultipleChoice', () => {
  it('shows the options as a radio group with markers', () => {
    const wrapper = mountQuestion(MultipleChoice, { question: fx.multipleChoice, modelValue: null })

    expect(wrapper.find('[role=radiogroup]').exists()).toBe(true)
    const radios = wrapper.findAll('[role=radio]')
    expect(radios).toHaveLength(3)
    expect(radios[1].text()).toContain('Hij wordt morgen veertien.')
    expect(radios[0].text()).toContain('A')
    expect(radios.every((r) => r.attributes('aria-checked') === 'false')).toBe(true)
  })

  it('selects one option at a time', async () => {
    const wrapper = mountQuestion(MultipleChoice, { question: fx.multipleChoice, modelValue: null })

    await wrapper.findAll('[role=radio]')[1].trigger('click')
    expect(lastModel(wrapper)).toEqual({ selected: [1] })
    expect(wrapper.findAll('[role=radio]')[1].attributes('aria-checked')).toBe('true')

    await wrapper.findAll('[role=radio]')[2].trigger('click')
    expect(lastModel(wrapper)).toEqual({ selected: [2] })
    expect(wrapper.findAll('[role=radio]')[1].attributes('aria-checked')).toBe('false')
  })

  it('toggles options when several answers are allowed', async () => {
    const wrapper = mountQuestion(MultipleChoice, {
      question: fx.multipleChoiceMulti,
      modelValue: null,
    })

    expect(wrapper.find('[role=group]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Kies alle goede antwoorden.')
    const boxes = wrapper.findAll('[role=checkbox]')

    await boxes[2].trigger('click')
    await boxes[0].trigger('click')
    expect(lastModel(wrapper)).toEqual({ selected: [0, 2] })

    await boxes[2].trigger('click')
    expect(lastModel(wrapper)).toEqual({ selected: [0] })
  })

  it('ignores clicks when disabled', async () => {
    const wrapper = mountQuestion(MultipleChoice, {
      question: fx.multipleChoice,
      modelValue: null,
      disabled: true,
    })

    await wrapper.findAll('[role=radio]')[0].trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeUndefined()
  })

  it('shows right, wrong and missed answers with text, not just colour', () => {
    const wrong = mountQuestion(MultipleChoice, {
      question: fx.multipleChoice,
      modelValue: { selected: [0] },
      disabled: true,
      result: fx.result(1, false, { selected: [1] }),
    })
    const options = wrong.findAll('[role=radio]')

    expect(options[0].text()).toContain('Fout')
    expect(options[1].text()).toContain('Juiste antwoord')
    expect(options[2].text()).not.toMatch(/Fout|Goed|Juiste/)

    const right = mountQuestion(MultipleChoice, {
      question: fx.multipleChoice,
      modelValue: { selected: [1] },
      disabled: true,
      result: fx.result(1, true, { selected: [1] }),
    })
    expect(right.findAll('[role=radio]')[1].text()).toContain('Goed')
  })
})

describe('FillBlank', () => {
  it('renders the text with one labelled input per blank', () => {
    const wrapper = mountQuestion(FillBlank, { question: fx.fillBlank, modelValue: null })
    const inputs = wrapper.findAll('input')

    expect(inputs).toHaveLength(2)
    expect(inputs[0].attributes('aria-label')).toBe('Invulveld 1 van 2 (schrijven)')
    expect(wrapper.text()).toContain('Nee, ik denk dat zij hem')
    expect(wrapper.text()).not.toContain('{{blank}}')
  })

  it("does not let the phone keyboard fix the student's spelling", () => {
    const input = mountQuestion(FillBlank, { question: fx.fillBlank, modelValue: null }).find(
      'input',
    )

    expect(input.attributes('autocapitalize')).toBe('off')
    expect(input.attributes('autocorrect')).toBe('off')
    expect(input.attributes('spellcheck')).toBe('false')
    expect(input.attributes('autocomplete')).toBe('off')
  })

  it('reports what was typed per blank', async () => {
    const wrapper = mountQuestion(FillBlank, { question: fx.fillBlank, modelValue: null })
    const inputs = wrapper.findAll('input')

    await inputs[1].setValue('schrijft')
    expect(lastModel(wrapper)).toEqual({ blanks: ['', 'schrijft'] })

    await inputs[0].setValue('Schrijf')
    expect(lastModel(wrapper)).toEqual({ blanks: ['Schrijf', 'schrijft'] })
  })

  it('marks wrong blanks and shows the expected answer', () => {
    const wrapper = mountQuestion(FillBlank, {
      question: fx.fillBlank,
      modelValue: { blanks: ['Schrijf', 'schrijf'] },
      disabled: true,
      result: fx.result(3, false, { blanks: ['schrijf', 'schrijft'] }, [true, false]),
    })
    const inputs = wrapper.findAll('input')

    expect(inputs[0].attributes('aria-invalid')).toBeUndefined()
    expect(inputs[1].attributes('aria-invalid')).toBe('true')
    expect(inputs.every((i) => i.attributes('disabled') !== undefined)).toBe(true)
    expect(wrapper.text()).toContain('Invulveld 2: schrijft')
    expect(wrapper.text()).not.toContain('Invulveld 1:')
  })
})

describe('OrderWords', () => {
  const chips = (w: ReturnType<typeof mountQuestion>) => w.findAll('button')
  const words = (w: ReturnType<typeof mountQuestion>) => chips(w).map((c) => c.text())

  it('shows all words as movable chips and reports the starting order', () => {
    const wrapper = mountQuestion(OrderWords, { question: fx.orderWords, modelValue: null })

    expect(words(wrapper)).toEqual(['zwemmen', 'ik', 'Gisteren', 'ging'])
    expect(lastModel(wrapper)).toEqual({ order: ['zwemmen', 'ik', 'Gisteren', 'ging'] })
    expect(wrapper.text()).toContain('pijltjestoetsen')
  })

  it('moves a word with the arrow keys and announces it', async () => {
    const wrapper = mountQuestion(OrderWords, { question: fx.orderWords, modelValue: null })

    await chips(wrapper)[2].trigger('keydown', { key: 'ArrowLeft' })
    expect(lastModel(wrapper)).toEqual({ order: ['zwemmen', 'Gisteren', 'ik', 'ging'] })
    expect(wrapper.find('[aria-live]').text()).toBe('Gisteren, plaats 2 van 4')

    await chips(wrapper)[1].trigger('keydown', { key: 'ArrowLeft' })
    expect(lastModel(wrapper)).toEqual({ order: ['Gisteren', 'zwemmen', 'ik', 'ging'] })
  })

  it('does not move past the ends', async () => {
    const wrapper = mountQuestion(OrderWords, { question: fx.orderWords, modelValue: null })
    const before = wrapper.emitted('update:modelValue')?.length

    await chips(wrapper)[0].trigger('keydown', { key: 'ArrowLeft' })
    await chips(wrapper)[3].trigger('keydown', { key: 'ArrowRight' })

    expect(wrapper.emitted('update:modelValue')?.length).toBe(before)
  })

  it('keeps repeated words apart', async () => {
    const question = { ...fx.orderWords, data: { words: ['de', 'kat', 'de', 'bank'] } }
    const wrapper = mountQuestion(OrderWords, { question, modelValue: null })

    await chips(wrapper)[2].trigger('keydown', { key: 'ArrowLeft' })

    expect(lastModel(wrapper)).toEqual({ order: ['de', 'de', 'kat', 'bank'] })
  })

  it('does not move words when disabled', async () => {
    const wrapper = mountQuestion(OrderWords, {
      question: fx.orderWords,
      modelValue: null,
      disabled: true,
    })
    const before = wrapper.emitted('update:modelValue')?.length

    await chips(wrapper)[2].trigger('keydown', { key: 'ArrowLeft' })

    expect(wrapper.emitted('update:modelValue')?.length).toBe(before)
  })

  it('follows an answer set from outside', async () => {
    const wrapper = mountQuestion(OrderWords, { question: fx.orderWords, modelValue: null })

    await wrapper.setProps({
      modelValue: { order: ['Gisteren', 'ging', 'ik', 'zwemmen'] },
    } as never)
    await nextTick()

    expect(words(wrapper)).toEqual(['Gisteren', 'ging', 'ik', 'zwemmen'])
  })

  it('shows the correct order after a wrong answer, and nothing extra after a right one', () => {
    const order = ['Gisteren', 'ging', 'ik', 'zwemmen']
    const wrong = mountQuestion(OrderWords, {
      question: fx.orderWords,
      modelValue: { order: ['ik', 'ging', 'Gisteren', 'zwemmen'] },
      disabled: true,
      result: fx.result(4, false, { order }),
    })
    expect(wrong.text()).toContain('Juiste volgorde: Gisteren ging ik zwemmen')
    expect(wrong.text()).toContain('Fout')

    const right = mountQuestion(OrderWords, {
      question: fx.orderWords,
      modelValue: { order },
      disabled: true,
      result: fx.result(4, true, { order }),
    })
    expect(right.text()).not.toContain('Juiste volgorde')
    expect(right.text()).toContain('Goed')
  })
})

describe('MarkError', () => {
  it('lets the student pick one word', async () => {
    const wrapper = mountQuestion(MarkError, { question: fx.markError, modelValue: null })
    const tokens = wrapper.findAll('button')

    expect(tokens.map((t) => t.text())).toEqual(['Wij', 'fietst', 'naar', 'school'])

    await tokens[1].trigger('click')
    expect(lastModel(wrapper)).toEqual({ index: 1 })
    expect(wrapper.findAll('button')[1].attributes('aria-pressed')).toBe('true')

    await tokens[3].trigger('click')
    expect(lastModel(wrapper)).toEqual({ index: 3 })
    expect(wrapper.findAll('button')[1].attributes('aria-pressed')).toBeUndefined()
  })

  it('shows the wrong guess, the real error and the correction', () => {
    const wrapper = mountQuestion(MarkError, {
      question: fx.markError,
      modelValue: { index: 0 },
      disabled: true,
      result: fx.result(5, false, { index: 1, correction: 'fietsen' }),
    })
    const tokens = wrapper.findAll('button')

    expect(tokens[0].text()).toContain('Fout')
    expect(tokens[1].text()).toContain('Juiste antwoord')
    expect(wrapper.text()).toContain('Dit moet zijn: fietsen')
  })

  it('confirms a right answer', () => {
    const wrapper = mountQuestion(MarkError, {
      question: fx.markError,
      modelValue: { index: 1 },
      disabled: true,
      result: fx.result(5, true, { index: 1, correction: 'fietsen' }),
    })

    expect(wrapper.findAll('button')[1].text()).toContain('Goed')
  })

  it('cannot be changed when disabled', async () => {
    const wrapper = mountQuestion(MarkError, {
      question: fx.markError,
      modelValue: null,
      disabled: true,
    })

    await wrapper.findAll('button')[2].trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeUndefined()
  })
})

describe('ParseSentence', () => {
  it('offers the roles per sentence part', async () => {
    const wrapper = mountQuestion(ParseSentence, { question: fx.parseSentence, modelValue: null })
    const selects = wrapper.findAll('select')

    expect(selects).toHaveLength(3)
    expect(selects[0].attributes('aria-label')).toBe('Functie van De kat')
    expect(selects[0].findAll('option').map((o) => o.text())).toEqual([
      'Kies functie',
      'onderwerp',
      'persoonsvorm',
      'lijdend voorwerp',
    ])

    await selects[1].setValue('persoonsvorm')
    expect(lastModel(wrapper)).toEqual({ roles: [null, 'persoonsvorm', null] })

    await selects[0].setValue('onderwerp')
    expect(lastModel(wrapper)).toEqual({ roles: ['onderwerp', 'persoonsvorm', null] })
  })

  it('marks each wrong part and names the right function', () => {
    const wrapper = mountQuestion(ParseSentence, {
      question: fx.parseSentence,
      modelValue: { roles: ['onderwerp', 'persoonsvorm', 'onderwerp'] },
      disabled: true,
      result: fx.result(6, false, { roles: ['onderwerp', 'persoonsvorm', 'lijdend voorwerp'] }, [
        true,
        true,
        false,
      ]),
    })
    const rows = wrapper.findAll('li')

    expect(rows[2].text()).toContain('Dit is: lijdend voorwerp')
    expect(rows[0].text()).not.toContain('Dit is:')
    expect(wrapper.findAll('select').every((s) => s.attributes('disabled') !== undefined)).toBe(
      true,
    )
  })
})

describe('QuestionRenderer', () => {
  it.each([
    [fx.multipleChoice, MultipleChoice],
    [fx.fillBlank, FillBlank],
    [fx.orderWords, OrderWords],
    [fx.markError, MarkError],
    [fx.parseSentence, ParseSentence],
  ])('renders the component for %#', (question, component) => {
    const wrapper = mount(QuestionRenderer, {
      props: { question, modelValue: null },
      global: { plugins: [i18n] },
    })

    expect(wrapper.findComponent(component).exists()).toBe(true)
    expect(wrapper.find('h2').text()).toBe(question.prompt)
    expect(wrapper.find('[role=group]').attributes('aria-labelledby')).toBe(
      wrapper.find('h2').attributes('id'),
    )
  })

  it('passes the answer, disabled and result through to the question component', async () => {
    const result = fx.result(5, false, { index: 1, correction: 'fietsen' })
    const wrapper = mount(QuestionRenderer, {
      props: { question: fx.markError, modelValue: { index: 0 }, disabled: true, result },
      global: { plugins: [i18n] },
    })
    const inner = wrapper.findComponent(MarkError)

    expect(inner.props('modelValue')).toEqual({ index: 0 })
    expect(inner.props('disabled')).toBe(true)
    expect(inner.props('result')).toEqual(result)
  })

  it('bubbles the answer up', async () => {
    const wrapper = mount(QuestionRenderer, {
      props: { question: fx.markError, modelValue: null },
      global: { plugins: [i18n] },
    })

    await wrapper.findAll('button')[2].trigger('click')

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([{ index: 2 }])
  })
})
