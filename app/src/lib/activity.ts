// Counts what the app is currently waiting for (API requests, page loads) so a loader can show it.
type Listener = (count: number) => void

let count = 0
const listeners = new Set<Listener>()

const notify = () => listeners.forEach((listener) => listener(count))

/** Mark something as started. Call the returned function when it is done (calling it twice is harmless). */
export function beginActivity(): () => void {
  count++
  notify()

  let finished = false
  return () => {
    if (finished) return
    finished = true
    count = Math.max(0, count - 1)
    notify()
  }
}

/** Listen to the number of running activities. Called once right away with the current number. */
export function subscribeActivity(listener: Listener): () => void {
  listeners.add(listener)
  listener(count)
  return () => listeners.delete(listener)
}

export const activityCount = () => count
