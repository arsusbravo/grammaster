import { onBeforeUnmount, onMounted, ref } from 'vue'

/** Whether the browser thinks it has a connection (updates when that changes). */
export function useOnline() {
  const online = ref(navigator.onLine)
  const update = () => (online.value = navigator.onLine)

  onMounted(() => {
    update()
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
  })
  onBeforeUnmount(() => {
    window.removeEventListener('online', update)
    window.removeEventListener('offline', update)
  })

  return online
}
