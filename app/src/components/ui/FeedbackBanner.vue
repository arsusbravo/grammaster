<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from './AppIcon.vue'

const props = defineProps<{
  kind: 'correct' | 'incorrect' | 'info' | 'warning'
  /** Replaces the standard heading of the kind. */
  title?: string
}>()

const { t } = useI18n()

const config = computed(() => {
  switch (props.kind) {
    case 'correct':
      return {
        box: 'border-success bg-success-soft text-success-ink',
        icon: 'check' as const,
        title: t('question.correct'),
      }
    case 'incorrect':
      return {
        box: 'border-error bg-error-soft text-error-ink',
        icon: 'x' as const,
        title: t('question.incorrect'),
      }
    case 'warning':
      return {
        box: 'border-warning bg-warning-soft text-warning-ink',
        icon: 'alert' as const,
        title: t('question.attention'),
      }
    default:
      return {
        box: 'border-secondary bg-surface-muted text-ink',
        icon: 'info' as const,
        title: t('question.explanation'),
      }
  }
})
</script>

<template>
  <section class="rise-in rounded-card border-2 p-5" :class="config.box" role="status">
    <h3 class="flex items-center gap-2 font-sans text-lg font-bold text-current">
      <AppIcon :name="config.icon" class="size-6" />
      {{ title ?? config.title }}
    </h3>
    <div class="explanation mt-2 text-current">
      <slot />
    </div>
  </section>
</template>
