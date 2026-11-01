export interface TokenValue {
  light: string
  dark: string
}

export type Tokens = Record<string, TokenValue>

/** Parse `--name: light-dark(#aaa, #bbb);` declarations from theme.css. */
export function parseTokens(css: string): Tokens {
  const tokens: Tokens = {}
  const pattern = /--([a-z-]+):\s*light-dark\(\s*(#[0-9a-f]{6})\s*,\s*(#[0-9a-f]{6})\s*\)/gi
  for (const [, name, light, dark] of css.matchAll(pattern)) {
    tokens[name] = { light: light.toLowerCase(), dark: dark.toLowerCase() }
  }
  return tokens
}

function channel(value: number): number {
  const c = value / 255
  return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4
}

function luminance(hex: string): number {
  const [r, g, b] = [1, 3, 5].map((i) => channel(parseInt(hex.slice(i, i + 2), 16)))
  return 0.2126 * r + 0.7152 * g + 0.0722 * b
}

/** WCAG 2.x contrast ratio between two #rrggbb colours. */
export function contrastRatio(a: string, b: string): number {
  const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x)
  return (hi + 0.05) / (lo + 0.05)
}

export interface ContrastPair {
  foreground: string
  background: string
  /** 4.5 for text, 3 for meaningful non-text (icons, borders of inputs, focus ring) */
  min: number
}

const text = (foreground: string, background: string): ContrastPair => ({
  foreground,
  background,
  min: 4.5,
})
const graphic = (foreground: string, background: string): ContrastPair => ({
  foreground,
  background,
  min: 3,
})

export const PAIRS: ContrastPair[] = [
  text('ink', 'canvas'),
  text('ink', 'surface'),
  text('ink', 'surface-muted'),
  text('ink-muted', 'canvas'),
  text('ink-muted', 'surface'),
  text('ink-muted', 'surface-muted'),
  text('on-primary', 'primary'),
  text('on-primary', 'primary-hover'),
  text('on-secondary', 'secondary'),
  text('on-secondary', 'secondary-hover'),
  text('on-accent', 'accent'),
  text('on-accent', 'accent-hover'),
  text('secondary', 'canvas'),
  text('secondary', 'surface'),
  text('accent-ink', 'canvas'),
  text('accent-ink', 'surface'),
  text('success-ink', 'success-soft'),
  text('error-ink', 'error-soft'),
  text('warning-ink', 'warning-soft'),
  text('success-ink', 'surface'),
  text('error-ink', 'surface'),
  text('warning-ink', 'surface'),
  graphic('success', 'success-soft'),
  graphic('error', 'error-soft'),
  graphic('warning', 'warning-soft'),
  graphic('success', 'surface'),
  graphic('error', 'surface'),
  graphic('warning', 'surface'),
  graphic('border-strong', 'canvas'),
  graphic('border-strong', 'surface'),
  graphic('focus', 'canvas'),
  graphic('focus', 'surface'),
]
