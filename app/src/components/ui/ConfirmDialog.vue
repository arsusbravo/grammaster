<script setup lang="ts">
import { onMounted, useId, useTemplateRef, watch } from 'vue'
import AppButton from './AppButton.vue'

// A modal built on the native <dialog>: focus is trapped, Escape closes it, the page behind is inert.
const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  title: string
  confirmLabel: string
  cancelLabel: string
  /** Disables the buttons and the ways of dismissing while something is happening. */
  busy?: boolean
}>()

const emit = defineEmits<{ confirm: []; cancel: [] }>()

const dialog = useTemplateRef<HTMLDialogElement>('dialog')
const titleId = useId()

function sync(isOpen: boolean) {
  const el = dialog.value
  if (!el) return
  if (isOpen && !el.open) el.showModal()
  if (!isOpen && el.open) el.close()
}

onMounted(() => sync(open.value))
watch(open, sync)

function cancel() {
  if (props.busy) return
  open.value = false
  emit('cancel')
}
</script>

<template>
  <dialog
    ref="dialog"
    :aria-labelledby="titleId"
    class="m-auto w-[calc(100%-2rem)] max-w-md rounded-card border border-border bg-surface p-0 text-ink shadow-card backdrop:bg-black/60"
    @cancel.prevent="cancel"
    @click="$event.target === dialog && cancel()"
  >
    <div class="p-6">
      <h2 :id="titleId" class="text-2xl">{{ title }}</h2>
      <div class="mt-3 space-y-2 text-ink-muted"><slot /></div>
      <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <AppButton variant="ghost" :disabled="busy" @click="cancel">{{ cancelLabel }}</AppButton>
        <AppButton :loading="busy" @click="emit('confirm')">{{ confirmLabel }}</AppButton>
      </div>
    </div>
  </dialog>
</template>
