import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import ExamTimerBar from './ExamTimerBar.vue'

const mountBar = (props: {
  remaining: number
  total: number
  urgency?: 'normal' | 'low' | 'critical'
}) => mount(ExamTimerBar, { props: { urgency: 'normal', ...props } })

const fill = (w: ReturnType<typeof mountBar>) => w.find('[data-testid=timer-bar] > div:first-child')
const thumb = (w: ReturnType<typeof mountBar>) => w.find('[data-testid=timer-bar] > div:last-child')

describe('ExamTimerBar', () => {
  it('is full at the start and shrinks with the time left', () => {
    expect(fill(mountBar({ remaining: 1800, total: 1800 })).attributes('style')).toContain(
      'width: 100%',
    )
    expect(fill(mountBar({ remaining: 900, total: 1800 })).attributes('style')).toContain(
      'width: 50%',
    )
    expect(fill(mountBar({ remaining: 450, total: 1800 })).attributes('style')).toContain(
      'width: 25%',
    )
  })

  it('moves the thumb along with the end of the bar', () => {
    expect(thumb(mountBar({ remaining: 900, total: 1800 })).attributes('style')).toContain(
      'left: 50%',
    )
  })

  it('is empty when the time is up and never goes outside the track', () => {
    expect(fill(mountBar({ remaining: 0, total: 1800 })).attributes('style')).toContain('width: 0%')
    expect(fill(mountBar({ remaining: -5, total: 1800 })).attributes('style')).toContain(
      'width: 0%',
    )
    expect(fill(mountBar({ remaining: 5000, total: 1800 })).attributes('style')).toContain(
      'width: 100%',
    )
  })

  it('copes with an exam without a time limit', () => {
    expect(fill(mountBar({ remaining: 10, total: 0 })).attributes('style')).toContain('width: 0%')
  })

  it('changes colour when the time runs low and critical', () => {
    expect(fill(mountBar({ remaining: 1, total: 2, urgency: 'normal' })).classes()).toContain(
      'bg-secondary',
    )
    expect(fill(mountBar({ remaining: 1, total: 2, urgency: 'low' })).classes()).toContain(
      'bg-warning',
    )
    expect(fill(mountBar({ remaining: 1, total: 2, urgency: 'critical' })).classes()).toContain(
      'bg-error',
    )
    expect(thumb(mountBar({ remaining: 1, total: 2, urgency: 'critical' })).classes()).toContain(
      'border-error',
    )
  })

  it('is decorative: the digital timer is the accessible one', () => {
    expect(
      mountBar({ remaining: 1, total: 2 })
        .find('[data-testid=timer-bar]')
        .attributes('aria-hidden'),
    ).toBe('true')
  })
})
