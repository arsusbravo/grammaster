<script setup lang="ts">
import { computed, useId } from 'vue'
import { isCollapsed, paragraphs, toggleCollapsed } from '@/lib/passage'
import type { Passage } from '@/types/api'

const props = defineProps<{ passage: Passage }>()

const titleId = useId()
const bodyId = useId()

const hidden = computed(() => isCollapsed(props.passage.id))
const parts = computed(() => paragraphs(props.passage.body))
</script>

<template>
  <section
    :aria-labelledby="titleId"
    class="mb-6 rounded-card border border-l-8 border-border border-l-accent bg-surface p-5 shadow-card"
  >
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-sm font-semibold tracking-wide text-accent-ink uppercase">
          {{ $t('passage.label') }}
        </p>
        <p :id="titleId" class="mt-1 font-display text-xl font-semibold">{{ passage.title }}</p>
      </div>
      <button
        type="button"
        class="-my-1 min-h-12 shrink-0 rounded-control px-3 font-semibold text-secondary hover:underline"
        :aria-expanded="!hidden"
        :aria-controls="bodyId"
        @click="toggleCollapsed(passage.id)"
      >
        {{ hidden ? $t('passage.show') : $t('passage.hide') }}
      </button>
    </div>

    <div v-show="!hidden" :id="bodyId" class="mt-3 max-w-prose space-y-4 text-lg leading-relaxed">
      <p v-for="(part, index) in parts" :key="index" class="whitespace-pre-line">{{ part }}</p>
    </div>
  </section>
</template>
