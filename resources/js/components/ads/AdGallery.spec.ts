import { type VueWrapper, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'

import AdGallery from '@/components/ads/AdGallery.vue'
import type { AdImage } from '@/types/api'

const mountedWrappers: VueWrapper[] = []

function images(count: number): AdImage[] {
  return Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    url: `https://example.test/${i + 1}.jpg`,
    position: i,
    is_primary: i === 0,
  }))
}

function mountGallery(list: AdImage[]) {
  const wrapper = mount(AdGallery, {
    props: { images: list, title: 'iPhone 13' },
    attachTo: document.body,
    global: {
      stubs: {
        Button: { template: '<button v-bind="$attrs"><slot /></button>' },
      },
    },
  })
  mountedWrappers.push(wrapper)

  return wrapper
}

function clickInBody(selector: string): void {
  const element = document.body.querySelector(selector)
  if (!(element instanceof HTMLElement)) {
    throw new Error(`Missing element: ${selector}`)
  }

  element.click()
}

afterEach(() => {
  for (const wrapper of mountedWrappers.splice(0)) {
    wrapper.unmount()
  }

  document.body.style.overflow = ''
})

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

  it('opens a fullscreen gallery when the main photo is clicked', async () => {
    const wrapper = mountGallery(images(2))

    await wrapper.find('[data-testid="gallery-image-trigger"]').trigger('click')

    const lightbox = document.body.querySelector('[data-testid="gallery-lightbox"]')
    expect(lightbox).not.toBeNull()
    expect(lightbox?.querySelector('[data-testid="gallery-lightbox-image"]')?.getAttribute('src'))
      .toBe('https://example.test/1.jpg')
    expect(document.body.style.overflow).toBe('hidden')
  })

  it('closes the fullscreen gallery from the close button', async () => {
    const wrapper = mountGallery(images(2))

    await wrapper.find('[data-testid="gallery-image-trigger"]').trigger('click')
    clickInBody('[data-testid="gallery-lightbox-close"]')
    await wrapper.vm.$nextTick()

    expect(document.body.querySelector('[data-testid="gallery-lightbox"]')).toBeNull()
    expect(document.body.style.overflow).toBe('')
  })

  it('closes the fullscreen gallery on Escape', async () => {
    const wrapper = mountGallery(images(2))

    await wrapper.find('[data-testid="gallery-image-trigger"]').trigger('click')
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await wrapper.vm.$nextTick()

    expect(document.body.querySelector('[data-testid="gallery-lightbox"]')).toBeNull()
  })

  it('navigates photos inside the fullscreen gallery', async () => {
    const wrapper = mountGallery(images(3))

    await wrapper.find('[data-testid="gallery-image-trigger"]').trigger('click')
    clickInBody('[data-testid="gallery-lightbox-next"]')
    await wrapper.vm.$nextTick()

    const lightboxImage = document.body.querySelector('[data-testid="gallery-lightbox-image"]')
    expect(lightboxImage?.getAttribute('src')).toBe('https://example.test/2.jpg')
    expect(wrapper.text()).toContain('2 / 3')
  })
})