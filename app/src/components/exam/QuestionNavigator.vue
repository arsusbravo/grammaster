<script setup lang="ts">
import { nextTick, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/components/ui/AppIcon.vue'
import type { AnswerProgress } from '@/lib/answers'

const props = defineProps<{
  items: { id: number; state: AnswerProgress }[]
  current: number
}>()

defineEmits<{ select: [index: number] }>()

const { t } = useI18n()
const list = useTemplateRef<HTMLElement>('list')

const label = (n: number, state: AnswerProgress) => t(`examRun.nav.${state}`, { n })

// Keep the current question in view when there are many.
watch(
  () => props.current,
  async () => {
    await nextTick()
    list.value
      ?.querySelector('[aria-current=step]')
      ?.scrollIntoView({ inline: 'center', block: 'nearest' })
  },
)
</script>

<template>
  <nav :aria-label="t('examRun.navigator')">
    <ol ref="list" class="flex gap-2 overflow-x-auto px-2 py-2">
      <li v-for="(item, i) in items" :key="item.id" class="shrink-0">
        <button
          type="button"
          :aria-current="i === current ? 'step' : undefined"
          :aria-label="label(i + 1, item.state)"
          class="relative flex size-11 items-center justify-center rounded-control border-2 text-base font-semibold transition-colors"
          :class="[
            item.state === 'complete' && 'border-primary bg-primary text-on-primary',
            item.state === 'partial' && 'border-dashed border-accent-ink bg-surface text-ink',
            item.state === 'none' && 'border-border-strong bg-surface text-ink',
            i === current && 'ring-2 ring-focus ring-offset-2 ring-offset-surface',
          ]"
          @click="$emit('select', i)"
        >
          {{ i + 1 }}
          <AppIcon
            v-if="item.state === 'complete'"
            name="check"
            class="absolute -top-1.5 -right-1.5 size-4 rounded-full bg-success p-0.5 text-on-primary"
          />
          <span
            v-else-if="item.state === 'partial'"
            class="absolute -top-1 -right-1 size-3 rounded-full border-2 border-surface bg-accent"
            aria-hidden="true"
          />
        </button>
      </li>
    </ol>
  </nav>
</template>
