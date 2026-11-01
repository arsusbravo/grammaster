import { describe, expect, it } from 'vitest'
import { formatClock, formatWhen, minutesOf } from './format'

describe('formatClock', () => {
  it('shows minutes and seconds, and hours when needed', () => {
    expect(formatClock(0)).toBe('00:00')
    expect(formatClock(9)).toBe('00:09')
    expect(formatClock(754)).toBe('12:34')
    expect(formatClock(1800)).toBe('30:00')
    expect(formatClock(3725)).toBe('1:02:05')
  })

  it('never goes below zero and ignores fractions', () => {
    expect(formatClock(-5)).toBe('00:00')
    expect(formatClock(59.9)).toBe('00:59')
  })
})

describe('minutesOf', () => {
  it('rounds to whole minutes, at least one', () => {
    expect(minutesOf(20)).toBe(1)
    expect(minutesOf(1120)).toBe(19)
    expect(minutesOf(1800)).toBe(30)
  })
})

describe('formatWhen', () => {
  const now = new Date(2026, 10, 15, 16, 0) // 15 Nov 2026, 16:00 local time
  const at = (y: number, m: number, d: number, h: number, min: number) =>
    new Date(y, m, d, h, min).toISOString()

  it('says today and yesterday in words, with the time', () => {
    expect(formatWhen(at(2026, 10, 15, 9, 5), now)).toBe('Vandaag 09:05')
    expect(formatWhen(at(2026, 10, 14, 23, 59), now)).toBe('Gisteren 23:59')
  })

  it('counts calendar days, not 24 hour blocks', () => {
    expect(formatWhen(at(2026, 10, 14, 0, 1), new Date(2026, 10, 15, 0, 5))).toBe('Gisteren 00:01')
  })

  it('shows the date for older entries, with the year only when it is another year', () => {
    expect(formatWhen(at(2026, 9, 31, 14, 5), now)).toMatch(/^31 okt 14:05$/)
    expect(formatWhen(at(2025, 11, 24, 8, 30), now)).toMatch(/^24 dec 2025 08:30$/)
  })
})
