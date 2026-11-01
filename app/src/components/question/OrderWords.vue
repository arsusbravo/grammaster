<script setup lang="ts">
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { VueDraggable } from 'vue-draggable-plus'
import TokenChip from '@/components/ui/TokenChip.vue'
import { orderStates } from '@/lib/answers'
import type { AnswerResult, OrderWordsAnswer, OrderWordsQuestion } from '@/types/api'

interface Item {
  id: number
  text: string
}

const model = defineModel<OrderWordsAnswer | null>({ default: null })

const props = defineProps<{
  question: OrderWordsQuestion
  disabled?: boolean
  result?: AnswerResult | null
}>()

const { t } = useI18n()

// Words can repeat ("de kat ... de bank"), so every blokje gets its own id.
let nextId = 0
const toItems = (words: string[]): Item[] => words.map((text) => ({ id: nextId++, text }))

const items = ref<Item[]>(toItems(model.value?.order ?? props.question.data.words))
const container = useTemplateRef<HTMLElement>('container')
const announcement = ref('')

const texts = (list: Item[]) => list.map((i) => i.text)
const sameOrder = (a: string[], b: string[]) =>
  a.length === b.length && a.every((w, i) => w === b[i])

// Dragging changes `items`; tell the parent. Report the start order too so "complete" is known.
watch(
  items,
  (list) => {
    if (!sameOrder(texts(list), model.value?.order ?? [])) model.value = { order: texts(list) }
  },
  { deep: true, immediate: true },
)

// The parent may replace the answer (new question, reset).
watch(
  () => model.value?.order,
  (order) => {
    if (order && !sameOrder(order, texts(items.value))) items.value = toItems(order)
  },
)

const states = computed(() =>
  props.result
    ? orderStates(texts(items.value), props.result)
    : texts(items.value).map(() => 'idle' as const),
)

// Keyboard alternative to dragging: arrow keys move the focused word.
async function onKeydown(event: KeyboardEvent, index: number) {
  if (props.disabled) return
  const delta =
    event.key === 'ArrowLeft' || event.key === 'ArrowUp'
      ? -1
      : event.key === 'ArrowRight' || event.key === 'ArrowDown'
        ? 1
        : 0
  const target = index + delta
  if (!delta || target < 0 || target >= items.value.length) return

  event.preventDefault()
  const next = [...items.value]
  const [moved] = next.splice(index, 1)
  next.splice(target, 0, moved)
  items.value = next

  announcement.value = t('question.orderMoved', {
    word: moved.text,
    position: target + 1,
    total: next.length,
  })
  await nextTick()
  container.value?.querySelector<HTMLElement>(`[data-id="${moved.id}"]`)?.focus()
}

const correctOrder = computed(() => props.result?.correct_answer.order?.join(' '))
</script>

<template>
  <div>
    <p v-if="!result && !disabled" id="order-hint" class="mb-3 text-ink-muted">
      {{ $t('question.orderHint') }}
    </p>

    <div ref="container">
      <VueDraggable
        v-model="items"
        :animation="150"
        :disabled="disabled"
        :delay="120"
        :delay-on-touch-only="true"
        :touch-start-threshold="4"
        ghost-class="opacity-40"
        role="group"
        :aria-label="question.prompt"
        class="flex min-h-16 flex-wrap gap-2 rounded-card border-2 border-dashed p-3"
        :class="result ? 'border-border' : 'border-border-strong'"
      >
        <TokenChip
          v-for="(item, index) in items"
          :key="item.id"
          :data-id="item.id"
          :label="item.text"
          :state="states[index]"
          :draggable="!disabled"
          :disabled="disabled"
          aria-describedby="order-hint"
          @keydown="onKeydown($event, index)"
        />
      </VueDraggable>
    </div>

    <p v-if="result && !result.is_correct && correctOrder" class="mt-3 text-success-ink">
      <span class="font-semibold">{{ $t('question.correctOrder') }}</span>
      {{ correctOrder }}
    </p>

    <p class="sr-only" aria-live="polite">{{ announcement }}</p>
  </div>
</template>
