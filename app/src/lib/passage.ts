import { reactive } from 'vue'

/**
 * Split the plain text of a story into paragraphs: a blank line starts a new one.
 * A single line break inside a paragraph is kept (the card shows it with `white-space: pre-line`).
 */
export function paragraphs(body: string): string[] {
  return body
    .replace(/\r\n?/g, '\n')
    .split(/\n[ \t]*\n/)
    .map((part) => part.trim())
    .filter(Boolean)
}

// Which stories the student has folded away. Kept in memory (not stored): the question component is
// recreated for every question, and a story that stays hidden should stay hidden for its next question.
const collapsed = reactive(new Set<number>())

export function isCollapsed(id: number): boolean {
  return collapsed.has(id)
}

export function toggleCollapsed(id: number): void {
  if (!collapsed.delete(id)) collapsed.add(id)
}

export function resetCollapsed(): void {
  collapsed.clear()
}
