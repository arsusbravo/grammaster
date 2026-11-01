<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AppIcon, { type IconName } from './AppIcon.vue'
import { THEMES, type Theme } from '@/lib/theme'

const model = defineModel<Theme>({ default: 'system' })

defineProps<{ label?: string }>()

const { t } = useI18n()
const icons: Record<Theme, IconName> = { system: 'monitor', light: 'sun', dark: 'moon' }
</script>

<template>
  <div role="radiogroup" :aria-label="label ?? t('account.theme')" class="grid grid-cols-3 gap-2">
    <button
      v-for="theme in THEMES"
      :key="theme"
      type="button"
      role="radio"
      :aria-checked="model === theme"
      class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-control border-2 px-2 text-sm font-semibold transition-colors"
      :class="
        model === theme
          ? 'border-primary bg-primary text-on-primary'
          : 'border-border-strong bg-surface text-ink hover:bg-surface-muted'
      "
      @click="model = theme"
    >
      <AppIcon :name="icons[theme]" class="size-6" />
      {{ t(`account.themes.${theme}`) }}
    </button>
  </div>
</template>
