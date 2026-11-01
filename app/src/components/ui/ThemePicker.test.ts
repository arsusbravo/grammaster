import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { i18n } from '@/i18n'
import ThemePicker from './ThemePicker.vue'

const plugins = { global: { plugins: [i18n] } }

describe('ThemePicker', () => {
  it('offers automatic, light and dark as a radio group, with words and icons', () => {
    const wrapper = mount(ThemePicker, { props: { modelValue: 'system' }, ...plugins })
    const options = wrapper.findAll('[role=radio]')

    expect(wrapper.find('[role=radiogroup]').attributes('aria-label')).toBe('Uiterlijk')
    expect(options.map((o) => o.text())).toEqual(['Automatisch', 'Licht', 'Donker'])
    expect(options.every((o) => o.find('svg').exists())).toBe(true)
  })

  it('marks the current choice', () => {
    const wrapper = mount(ThemePicker, { props: { modelValue: 'dark' }, ...plugins })

    expect(wrapper.findAll('[role=radio]').map((o) => o.attributes('aria-checked'))).toEqual([
      'false',
      'false',
      'true',
    ])
  })

  it('reports a new choice', async () => {
    const wrapper = mount(ThemePicker, { props: { modelValue: 'system' }, ...plugins })

    await wrapper.findAll('[role=radio]')[1].trigger('click')

    expect(wrapper.emitted('update:modelValue')).toEqual([['light']])
  })
})
