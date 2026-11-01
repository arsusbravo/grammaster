/** 754 -> "12:34", 3725 -> "1:02:05". */
export function formatClock(totalSeconds: number): string {
  const seconds = Math.max(0, Math.floor(totalSeconds))
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  const pad = (n: number) => String(n).padStart(2, '0')

  return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`
}

/** Whole minutes, at least 1: 20 seconds -> 1, 18 minutes 40 seconds -> 19. */
export function minutesOf(totalSeconds: number): number {
  return Math.max(1, Math.round(totalSeconds / 60))
}

const timeOf = (d: Date) => d.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })

/** "Vandaag 14:05", "Gisteren 09:30", "31 okt 14:05" (with the year when it is not this year). */
export function formatWhen(iso: string, now: Date = new Date()): string {
  const date = new Date(iso)
  const startOfDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime()
  const days = Math.round((startOfDay(now) - startOfDay(date)) / 86_400_000)

  if (days === 0) return `Vandaag ${timeOf(date)}`
  if (days === 1) return `Gisteren ${timeOf(date)}`

  const day = date.toLocaleDateString('nl-NL', {
    day: 'numeric',
    month: 'short',
    ...(date.getFullYear() !== now.getFullYear() && { year: 'numeric' }),
  })
  return `${day.replace('.', '')} ${timeOf(date)}`
}
