import { beforeEach, describe, expect, it } from 'vitest'
import { isCollapsed, paragraphs, resetCollapsed, toggleCollapsed } from './passage'

describe('paragraphs', () => {
  it('starts a new paragraph at every blank line', () => {
    expect(paragraphs('Eerste alinea.\n\nTweede alinea.\n\n\n\nDerde alinea.')).toEqual([
      'Eerste alinea.',
      'Tweede alinea.',
      'Derde alinea.',
    ])
  })

  it('keeps a single line break inside a paragraph', () => {
    expect(paragraphs('Regel een\nregel twee\n\nNieuwe alinea')).toEqual([
      'Regel een\nregel twee',
      'Nieuwe alinea',
    ])
  })

  it('treats a line with only spaces as a blank line, and handles Windows line endings', () => {
    expect(paragraphs('Een\r\n  \r\nTwee')).toEqual(['Een', 'Twee'])
  })

  it('trims the text and ignores empty input', () => {
    expect(paragraphs('\n\n  Alleen dit  \n\n')).toEqual(['Alleen dit'])
    expect(paragraphs('')).toEqual([])
    expect(paragraphs(' \n \n ')).toEqual([])
  })
})

describe('folding a story away', () => {
  beforeEach(resetCollapsed)

  it('remembers per story whether it is folded', () => {
    expect(isCollapsed(1)).toBe(false)

    toggleCollapsed(1)
    expect(isCollapsed(1)).toBe(true)
    expect(isCollapsed(2)).toBe(false)

    toggleCollapsed(1)
    expect(isCollapsed(1)).toBe(false)
  })
})
