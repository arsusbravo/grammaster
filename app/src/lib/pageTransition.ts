import { ref } from 'vue'
import type { RouteLocationNormalized } from 'vue-router'

export type PageTransition = 'page-fade' | 'page-forward' | 'page-back'

type Where = Pick<RouteLocationNormalized, 'meta'>

/**
 * Which animation to use when going from one page to another:
 *  - between the main tabs: slide in the direction of the tab bar (left to right = forward);
 *  - going deeper into a section (home -> practice) slides forward, coming back out slides back;
 *  - anything else (login, exam -> its result, ...) fades.
 */
export function transitionFor(from: Where, to: Where): PageTransition {
  const { tab: fromTab, depth: fromDepth } = from.meta
  const { tab: toTab, depth: toDepth } = to.meta

  if (fromTab !== undefined && toTab !== undefined && fromTab !== toTab) {
    return toTab > fromTab ? 'page-forward' : 'page-back'
  }
  if (fromDepth !== undefined && toDepth !== undefined && fromDepth !== toDepth) {
    return toDepth > fromDepth ? 'page-forward' : 'page-back'
  }
  return 'page-fade'
}

/** The animation the page view uses right now (set before each navigation renders). */
export const pageTransition = ref<PageTransition>('page-fade')
