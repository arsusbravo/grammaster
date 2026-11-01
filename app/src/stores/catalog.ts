import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as endpoints from '@/api/endpoints'
import type { Level, Topic } from '@/types/api'

/** Levels and topics (public data), cached so the screens open instantly after the first load. */
export const useCatalogStore = defineStore('catalog', () => {
  const levels = ref<Level[]>([])
  const topicsByLevel = ref<Record<string, Topic[]>>({})

  async function loadLevels() {
    if (levels.value.length === 0) levels.value = await endpoints.levels()
  }

  /** Topics with the number of published questions for one level. */
  async function loadTopics(levelSlug: string, force = false) {
    if (force || !topicsByLevel.value[levelSlug]) {
      topicsByLevel.value[levelSlug] = await endpoints.topics(levelSlug)
    }
  }

  return { levels, topicsByLevel, loadLevels, loadTopics }
})
