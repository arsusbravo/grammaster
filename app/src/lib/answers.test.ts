import { describe, expect, it } from 'vitest'
import * as fx from '@/design/fixtures'
import {
  answerProgress,
  closestOrder,
  emptyAnswer,
  hasAnswer,
  isAnswerComplete,
  orderStates,
  splitBlankText,
} from './answers'
import { safeRedirect } from './redirect'

describe('emptyAnswer', () => {
  it('starts every question type with nothing answered', () => {
    expect(emptyAnswer(fx.multipleChoice)).toEqual({ selected: [] })
    expect(emptyAnswer(fx.fillBlank)).toEqual({ blanks: ['', ''] })
    expect(emptyAnswer(fx.markError)).toEqual({ index: -1 })
    expect(emptyAnswer(fx.parseSentence)).toEqual({ roles: [null, null, null] })
  })

  it('starts order_words with the words in the order the server shuffled them', () => {
    const answer = emptyAnswer(fx.orderWords)

    expect(answer).toEqual({ order: ['zwemmen', 'ik', 'Gisteren', 'ging'] })
    // A copy, so dragging never mutates the question itself.
    expect((answer as { order: string[] }).order).not.toBe(fx.orderWords.data.words)
  })
})

describe('isAnswerComplete', () => {
  it('is never complete without an answer', () => {
    for (const q of fx.questions) expect(isAnswerComplete(q, null)).toBe(false)
  })

  it('needs at least one choice for multiple choice', () => {
    expect(isAnswerComplete(fx.multipleChoice, { selected: [] })).toBe(false)
    expect(isAnswerComplete(fx.multipleChoice, { selected: [2] })).toBe(true)
  })

  it('needs every blank filled in, ignoring spaces', () => {
    expect(isAnswerComplete(fx.fillBlank, { blanks: ['schrijf', ''] })).toBe(false)
    expect(isAnswerComplete(fx.fillBlank, { blanks: ['schrijf', '   '] })).toBe(false)
    expect(isAnswerComplete(fx.fillBlank, { blanks: ['schrijf', null] })).toBe(false)
    expect(isAnswerComplete(fx.fillBlank, { blanks: ['schrijf', 'schrijft'] })).toBe(true)
  })

  it('needs all words for order_words', () => {
    expect(isAnswerComplete(fx.orderWords, { order: ['a', 'b'] })).toBe(false)
    expect(isAnswerComplete(fx.orderWords, { order: ['a', 'b', 'c', 'd'] })).toBe(true)
  })

  it('needs a token inside the sentence for mark_error', () => {
    expect(isAnswerComplete(fx.markError, { index: -1 })).toBe(false)
    expect(isAnswerComplete(fx.markError, { index: 4 })).toBe(false)
    expect(isAnswerComplete(fx.markError, { index: 0 })).toBe(true)
  })

  it('needs a role for every part for parse_sentence', () => {
    expect(isAnswerComplete(fx.parseSentence, { roles: ['onderwerp', null, 'persoonsvorm'] })).toBe(
      false,
    )
    expect(isAnswerComplete(fx.parseSentence, { roles: ['onderwerp', 'persoonsvorm'] })).toBe(false)
    expect(isAnswerComplete(fx.parseSentence, { roles: ['a', 'b', 'c'] })).toBe(true)
  })
})

describe('splitBlankText', () => {
  it('gives n + 1 text parts for n blanks', () => {
    expect(splitBlankText('Mijn broer {{blank}} in {{blank}}.')).toEqual([
      'Mijn broer ',
      ' in ',
      '.',
    ])
    expect(splitBlankText('{{blank}} jij?')).toEqual(['', ' jij?'])
    expect(splitBlankText('geen gat')).toEqual(['geen gat'])
  })
})

describe('closestOrder / orderStates', () => {
  const canonical = ['Gisteren', 'ging', 'ik', 'zwemmen']
  const alternative = ['Ik', 'ging', 'gisteren', 'zwemmen']

  it('picks the accepted order that matches in most places, ignoring case', () => {
    expect(closestOrder(['ik', 'ging', 'gisteren', 'zwemmen'], [canonical, alternative])).toBe(
      alternative,
    )
    expect(closestOrder(['Gisteren', 'ik', 'ging', 'zwemmen'], [canonical, alternative])).toBe(
      canonical,
    )
  })

  it('marks every word correct when the server says the answer is right', () => {
    const states = orderStates(
      ['ik', 'ging', 'gisteren', 'zwemmen'],
      fx.result(4, true, { order: canonical }),
    )

    expect(states).toEqual(['correct', 'correct', 'correct', 'correct'])
  })

  it('marks the words that are in the wrong place', () => {
    const wrong = fx.result(4, false, { order: canonical, alternative_orders: [alternative] })

    expect(orderStates(['Gisteren', 'ik', 'ging', 'zwemmen'], wrong)).toEqual([
      'correct',
      'incorrect',
      'incorrect',
      'correct',
    ])
  })
})

describe('safeRedirect', () => {
  it('only follows paths inside the app', () => {
    expect(safeRedirect('/oefenen?level=vmbo')).toBe('/oefenen?level=vmbo')
    expect(safeRedirect('https://evil.example')).toBe('/')
    expect(safeRedirect('//evil.example')).toBe('/')
    expect(safeRedirect(undefined)).toBe('/')
    expect(safeRedirect(['/a'])).toBe('/')
  })
})

describe('hasAnswer / answerProgress', () => {
  it('counts anything the student filled in as an answer', () => {
    expect(hasAnswer(fx.multipleChoice, { selected: [] })).toBe(false)
    expect(hasAnswer(fx.multipleChoice, { selected: [0] })).toBe(true)
    expect(hasAnswer(fx.fillBlank, { blanks: ['', '  '] })).toBe(false)
    expect(hasAnswer(fx.fillBlank, { blanks: ['', 'schrijft'] })).toBe(true)
    expect(hasAnswer(fx.markError, { index: -1 })).toBe(false)
    expect(hasAnswer(fx.markError, { index: 0 })).toBe(true)
    expect(hasAnswer(fx.parseSentence, { roles: [null, null, null] })).toBe(false)
    expect(hasAnswer(fx.parseSentence, { roles: [null, 'onderwerp', null] })).toBe(true)
    expect(hasAnswer(fx.multipleChoice, null)).toBe(false)
  })

  it('does not count the shuffled starting order of order_words as an answer', () => {
    const order = { order: ['zwemmen', 'ik', 'Gisteren', 'ging'] }

    expect(hasAnswer(fx.orderWords, order, false)).toBe(false)
    expect(hasAnswer(fx.orderWords, order, true)).toBe(true)
  })

  it('tells none, partial and complete apart', () => {
    expect(answerProgress(fx.fillBlank, { blanks: ['', ''] })).toBe('none')
    expect(answerProgress(fx.fillBlank, { blanks: ['schrijf', ''] })).toBe('partial')
    expect(answerProgress(fx.fillBlank, { blanks: ['schrijf', 'schrijft'] })).toBe('complete')
    expect(answerProgress(fx.parseSentence, { roles: ['onderwerp', null, null] })).toBe('partial')
    expect(answerProgress(fx.multipleChoice, { selected: [1] })).toBe('complete')
  })
})
