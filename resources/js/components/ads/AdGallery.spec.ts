import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import AdGallery from '@/components/ads/AdGallery.vue'
import type { AdImage } from '@/types/api'

function images(count: number): AdImage[] {
  return Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    url: `https://example.test/${i + 1}.jpg`,
    position: i,
    is_primary: i === 0,
  }))
}

function mountGallery(list: AdImage[]) {
  return mount(AdGallery, {
    props: { images: list, title: 'iPhone 13' },
    global: { stubs: { Button: { template: '<button><slot /></button>' } } },
  })
}

describe('AdGallery', () => {
  it('shows a placeholder and no counter when there are no photos', () => {
    const wrapper = mountGallery([])

    expect(wrapper.find('[data-testid="gallery-placeholder"]').exists()).toBe(true)
    expect(wrapper.text()).not.toContain('/')
  })

  it('shows the counter starting at one', () => {
    expect(mountGallery(images(6)).text()).toContain('1 / 6')
  })

  it('hides navigation and thumbnails for a single photo', () => {
    const wrapper = mountGallery(images(1))

    expect(wrapper.find('.gallery__thumbs').exists()).toBe(false)
    expect(wrapper.text()).toContain('1 / 1')
  })

  it('advances to the chosen thumbnail', async () => {
    const wrapper = mountGallery(images(3))

    await wrapper.findAll('.gallery__thumb')[2]?.trigger('click')

    expect(wrapper.text()).toContain('3 / 3')
    expect(wrapper.find('.gallery__image').attributes('src')).toBe('https://example.test/3.jpg')
  })

  it('wraps around when stepping back from the first photo', async () => {
    const wrapper = mountGallery(images(3))

    await wrapper.find('.gallery__nav--prev').trigger('click')

    expect(wrapper.text()).toContain('3 / 3')
  })
})
