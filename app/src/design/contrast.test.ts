import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { PAIRS, contrastRatio, parseTokens } from './contrast'

const tokens = parseTokens(readFileSync('src/styles/theme.css', 'utf8'))

describe('theme contrast (WCAG AA)', () => {
  it('reads all tokens from theme.css', () => {
    expect(Object.keys(tokens).length).toBeGreaterThanOrEqual(27)
  })

  for (const scheme of ['light', 'dark'] as const) {
    for (const { foreground, background, min } of PAIRS) {
      it(`${scheme}: ${foreground} on ${background} is at least ${min}:1`, () => {
        const fg = tokens[foreground]?.[scheme]
        const bg = tokens[background]?.[scheme]
        expect(fg, `missing token ${foreground}`).toBeDefined()
        expect(bg, `missing token ${background}`).toBeDefined()
        expect(contrastRatio(fg, bg)).toBeGreaterThanOrEqual(min)
      })
    }
  }

  it('shows why the raw logo gold cannot be used as text on white', () => {
    expect(contrastRatio('#b5853a', '#ffffff')).toBeLessThan(4.5)
  })
})
