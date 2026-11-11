/**
 * A random id for a practice session or a queued answer.
 *
 * `crypto.randomUUID` only exists in a secure context (https or localhost). The app is also opened over plain
 * http during development (for example http://gramaster.test), where calling it throws and breaks practice.
 * `crypto.getRandomValues` is available everywhere, so use it as the fallback.
 */
export function newId(): string {
  if (typeof crypto.randomUUID === 'function') return crypto.randomUUID()

  const bytes = crypto.getRandomValues(new Uint8Array(16))
  bytes[6] = (bytes[6] & 0x0f) | 0x40 // version 4
  bytes[8] = (bytes[8] & 0x3f) | 0x80 // variant
  const hex = [...bytes].map((b) => b.toString(16).padStart(2, '0'))

  return `${hex.slice(0, 4).join('')}-${hex.slice(4, 6).join('')}-${hex.slice(6, 8).join('')}-${hex.slice(8, 10).join('')}-${hex.slice(10).join('')}`
}
