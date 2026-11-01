import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import AppButton from './AppButton.vue'

describe('AppButton', () => {
  it('is a normal button', () => {
    const wrapper = mount(AppButton, { slots: { default: 'Opslaan' } })

    expect(wrapper.text()).toBe('Opslaan')
    expect(wrapper.attributes('type')).toBe('button')
    expect(wrapper.attributes('disabled')).toBeUndefined()
    expect(wrapper.find('svg').exists()).toBe(false)
  })

  it('shows a spinner, keeps its label, and ignores clicks while loading', async () => {
    const wrapper = mount(AppButton, { props: { loading: true }, slots: { default: 'Opslaan' } })

    expect(wrapper.find('svg').exists()).toBe(true)
    expect(wrapper.text()).toBe('Opslaan')
    expect(wrapper.attributes('disabled')).toBeDefined()
    expect(wrapper.attributes('aria-busy')).toBe('true')

    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeUndefined()
  })

  it('can still be disabled without loading', () => {
    const wrapper = mount(AppButton, { props: { disabled: true } })

    expect(wrapper.attributes('disabled')).toBeDefined()
    expect(wrapper.attributes('aria-busy')).toBeUndefined()
    expect(wrapper.find('svg').exists()).toBe(false)
  })
})
