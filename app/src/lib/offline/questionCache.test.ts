import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as fx from '@/design/fixtures'
import type { Level, Question, Topic } from '@/types/api'
import { cachedQuestions, pickOffline, rememberQuestions } from './questionCache'

const level = (slug: string): Level => ({ id: 1, slug, name: slug, sort_order: 1 })
const topic = (slug: string): Topic => ({
  id: 1,
  slug,
  name: slug,
  description: null,
  sort_order: 1,
})

const q = (id: number, levelSlug = 'vmbo', topicSlug = 'lidwoorden', difficulty = 1): Question => ({
  ...fx.multipleChoice,
  id,
  difficulty,
  level: level(levelSlug),
  topic: topic(topicSlug),
})

beforeEach(() => localStorage.clear())

describe('remembering questions', () => {
  it('stores questions per student', () => {
    rememberQuestions(1, [q(1), q(2)])
    rememberQuestions(2, [q(9)])

    expect(cachedQuestions(1).map((x) => x.id)).toEqual([1, 2])
    expect(cachedQuestions(2).map((x) => x.id)).toEqual([9])
    expect(cachedQuestions(3)).toEqual([])
  })

  it('keeps the story of a question about a text', () => {
    rememberQuestions(1, [{ ...q(1), passage: fx.passage }, q(2)])

    expect(cachedQuestions(1)[0].passage).toEqual(fx.passage)
    expect(cachedQuestions(1)[1].passage ?? null).toBeNull()
  })

  it('puts new questions first and does not keep duplicates', () => {
    rememberQuestions(1, [q(1), q(2)])
    rememberQuestions(1, [q(3), q(1)])

    expect(cachedQuestions(1).map((x) => x.id)).toEqual([3, 1, 2])
  })

  it('keeps at most 150 questions, the newest', () => {
    rememberQuestions(
      1,
      Array.from({ length: 120 }, (_, i) => q(i + 1)),
    )
    rememberQuestions(
      1,
      Array.from({ length: 60 }, (_, i) => q(1000 + i)),
    )

    const ids = cachedQuestions(1).map((x) => x.id)
    expect(ids).toHaveLength(150)
    expect(ids[0]).toBe(1000)
    expect(ids).not.toContain(120)
  })

  it('does nothing for an empty batch', () => {
    rememberQuestions(1, [])

    expect(localStorage.getItem('grammatica.offline.questions.1')).toBeNull()
  })

  it('keeps the newer half when the device is out of space', () => {
    const real = Storage.prototype.setItem
    let calls = 0
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(function (
      this: Storage,
      k: string,
      v: string,
    ) {
      if (calls++ === 0) throw new DOMException('full', 'QuotaExceededError')
      return real.call(this, k, v)
    })

    rememberQuestions(1, [q(1), q(2), q(3), q(4)])

    expect(cachedQuestions(1).map((x) => x.id)).toEqual([1, 2])
    vi.restoreAllMocks()
  })

  it('survives damaged storage', () => {
    localStorage.setItem('grammatica.offline.questions.1', '{oops')

    expect(cachedQuestions(1)).toEqual([])
    expect(() => rememberQuestions(1, [q(1)])).not.toThrow()
  })

  it('stores only what the API sent: no solutions', () => {
    rememberQuestions(1, [q(1)])

    const raw = localStorage.getItem('grammatica.offline.questions.1')!
    expect(raw).not.toMatch(/correct"|accepted|explanation|error_index|correction/)
  })
})

describe('picking questions offline', () => {
  beforeEach(() => {
    rememberQuestions(1, [
      q(1, 'vmbo', 'lidwoorden', 1),
      q(2, 'vmbo', 'hoofdletters', 2),
      q(3, 'havo', 'lidwoorden', 2),
      q(4, 'vmbo', 'lidwoorden', 2),
    ])
  })

  const ids = (qs: Question[]) => qs.map((x) => x.id).sort()

  it('filters like the server does: level, topic, difficulty', () => {
    expect(ids(pickOffline(1, { level: 'vmbo' }, [], 10))).toEqual([1, 2, 4])
    expect(ids(pickOffline(1, { level: 'vmbo', topic: 'lidwoorden' }, [], 10))).toEqual([1, 4])
    expect(ids(pickOffline(1, { topic: 'lidwoorden', difficulty: 2 }, [], 10))).toEqual([3, 4])
    expect(ids(pickOffline(1, {}, [], 10))).toEqual([1, 2, 3, 4])
  })

  it('skips questions that were already shown', () => {
    expect(ids(pickOffline(1, {}, [1, 3], 10))).toEqual([2, 4])
  })

  it('respects the limit', () => {
    expect(pickOffline(1, {}, [], 2)).toHaveLength(2)
  })

  it('returns nothing when nothing matches', () => {
    expect(pickOffline(1, { level: 'vwo' }, [], 10)).toEqual([])
    expect(pickOffline(2, {}, [], 10)).toEqual([])
  })

  it('shuffles', () => {
    rememberQuestions(
      1,
      Array.from({ length: 30 }, (_, i) => q(100 + i)),
    )

    const orders = new Set(
      Array.from({ length: 6 }, () =>
        pickOffline(1, {}, [], 30)
          .map((x) => x.id)
          .join(),
      ),
    )

    expect(orders.size).toBeGreaterThan(1)
  })
})
