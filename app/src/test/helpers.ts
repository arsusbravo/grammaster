import {
  flushPromises,
  mount,
  type ComponentMountingOptions,
  type VueWrapper,
} from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import type { Component } from 'vue'
import { createMemoryHistory } from 'vue-router'
import { i18n } from '@/i18n'
import { createAppRouter } from '@/router'

export { flushPromises }

/** Mount a component with Pinia, the Dutch i18n and (optionally) a memory router. */
export async function mountApp(
  component: Component,
  options: ComponentMountingOptions<never> & { route?: string } = {},
) {
  const pinia = createPinia()
  setActivePinia(pinia)
  const router = createAppRouter(createMemoryHistory())
  const { route, ...rest } = options

  if (route) {
    await router.push(route)
    await router.isReady()
  }

  const wrapper = mount(component as never, {
    ...(rest as object),
    global: { plugins: [pinia, i18n, router], stubs: { RouterLink: false } },
  })

  return { wrapper, router, pinia }
}

/** Mount a single question component the way a parent with v-model would. */
export function mountQuestion(component: Component, props: Record<string, unknown>) {
  const parent: { wrapper?: VueWrapper } = {}
  let early: { value: unknown } | undefined // a component may report its answer while it is being set up

  const mounted = mount(component as never, {
    props: {
      ...props,
      'onUpdate:modelValue': (value: unknown) => {
        if (parent.wrapper) void parent.wrapper.setProps({ modelValue: value } as never)
        else early = { value }
      },
    } as never,
    global: { plugins: [i18n] },
  })
  parent.wrapper = mounted
  if (early) void mounted.setProps({ modelValue: early.value } as never)

  return mounted
}

/** The last value a component emitted for v-model. */
export function lastModel(wrapper: { emitted: (name: string) => unknown[][] | undefined }) {
  const events = wrapper.emitted('update:modelValue') ?? []
  return events.at(-1)?.[0]
}
