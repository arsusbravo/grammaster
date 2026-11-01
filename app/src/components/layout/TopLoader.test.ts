import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { activityCount, beginActivity } from '@/lib/activity'
import { i18n } from '@/i18n'
import TopLoader from './TopLoader.vue'

beforeEach(() => vi.useFakeTimers())
afterEach(() => {
  while (activityCount() > 0) beginActivity()()
  vi.useRealTimers()
})

const mountLoader = () => mount(TopLoader, { global: { plugins: [i18n] } })
const bar = (w: ReturnType<typeof mountLoader>) => w.find('[data-testid=top-loader]')

describe('TopLoader', () => {
  it('is hidden when nothing is loading', () => {
    const wrapper = mountLoader()

    expect(bar(wrapper).exists()).toBe(false)
    expect(wrapper.find('[role=status]').text()).toBe('')
  })

  it('does not flash for quick requests', async () => {
    const wrapper = mountLoader()

    const done = beginActivity()
    await vi.advanceTimersByTimeAsync(100)
    done()
    await vi.advanceTimersByTimeAsync(1000)

    expect(bar(wrapper).exists()).toBe(false)
  })

  it('appears after a short delay for slower requests, and says so for screen readers', async () => {
    const wrapper = mountLoader()

    const done = beginActivity()
    await vi.advanceTimersByTimeAsync(200)

    expect(bar(wrapper).exists()).toBe(true)
    expect(wrapper.find('[role=status]').text()).toBe('Bezig met laden')
    done()
  })

  it('stays visible for a moment so it never blinks, then goes away', async () => {
    const wrapper = mountLoader()
    const done = beginActivity()
    await vi.advanceTimersByTimeAsync(160) // shown
    expect(bar(wrapper).exists()).toBe(true)

    done() // finished almost at once
    await vi.advanceTimersByTimeAsync(200)
    expect(bar(wrapper).exists()).toBe(true)

    await vi.advanceTimersByTimeAsync(400)
    expect(bar(wrapper).exists()).toBe(false)
    expect(wrapper.find('[role=status]').text()).toBe('')
  })

  it('stays while anything is still running', async () => {
    const wrapper = mountLoader()
    const first = beginActivity()
    const second = beginActivity()
    await vi.advanceTimersByTimeAsync(200)

    first()
    await vi.advanceTimersByTimeAsync(2000)
    expect(bar(wrapper).exists()).toBe(true)

    second()
    await vi.advanceTimersByTimeAsync(1000)
    expect(bar(wrapper).exists()).toBe(false)
  })

  it('shows again for the next request', async () => {
    const wrapper = mountLoader()
    const first = beginActivity()
    await vi.advanceTimersByTimeAsync(200)
    first()
    await vi.advanceTimersByTimeAsync(1000)
    expect(bar(wrapper).exists()).toBe(false)

    const second = beginActivity()
    await vi.advanceTimersByTimeAsync(200)

    expect(bar(wrapper).exists()).toBe(true)
    second()
  })

  it('stops listening when removed', () => {
    const wrapper = mountLoader()

    wrapper.unmount()

    expect(() => beginActivity()()).not.toThrow()
  })
})
