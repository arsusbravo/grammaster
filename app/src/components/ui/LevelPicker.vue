<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { Level } from '@/types/api'

const model = defineModel<number | null>({ default: null })

defineProps<{ levels: Level[]; label?: string }>()

const { t } = useI18n()
</script>

<template>
  <div role="radiogroup" :aria-label="label ?? t('levelPicker.label')" class="flex flex-wrap gap-2">
    <button
      v-for="level in levels"
      :key="level.id"
      type="button"
      role="radio"
      :aria-checked="model === level.id"
      class="min-h-12 rounded-control border-2 px-5 text-base font-semibold transition-colors"
      :class="
        model === level.id
          ? 'border-primary bg-primary text-on-primary'
          : 'border-border-strong bg-surface text-ink hover:bg-surface-muted'
      "
      @click="model = level.id"
    >
      {{ level.name }}
    </button>
  </div>
</template>
