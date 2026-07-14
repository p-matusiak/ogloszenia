<script setup lang="ts">
import Button from 'primevue/button'
import { computed, onUnmounted, ref, watch } from 'vue'

import type { AdImage } from '@/types/api'

const props = defineProps<{ images: AdImage[]; title: string }>()

const index = ref(0)
const isFullscreen = ref(false)

const current = computed<AdImage | undefined>(() => props.images[index.value])
const hasMany = computed(() => props.images.length > 1)

/** Zawijanie w obie strony: z pierwszego zdjęcia „wstecz” wraca na ostatnie. */
function step(delta: number): void {
  const count = props.images.length
  if (count === 0) {
    return
  }

  index.value = (index.value + delta + count) % count
}

function openFullscreen(): void {
  if (!current.value) {
    return
  }

  isFullscreen.value = true
}

function closeFullscreen(): void {
  isFullscreen.value = false
}

function onFullscreenKeydown(event: KeyboardEvent): void {
  if (!isFullscreen.value) {
    return
  }

  if (event.key === 'Escape') {
    closeFullscreen()
    return
  }

  if (event.key === 'ArrowLeft') {
    step(-1)
  }

  if (event.key === 'ArrowRight') {
    step(1)
  }
}

watch(isFullscreen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

window.addEventListener('keydown', onFullscreenKeydown)

onUnmounted(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onFullscreenKeydown)
})
</script>

<template>
  <div class="gallery">
    <div class="gallery__stage">
      <button
        v-if="current"
        type="button"
        class="gallery__image-btn"
        data-testid="gallery-image-trigger"
        aria-label="Otwórz galerię na pełnym ekranie"
        @click="openFullscreen"
      >
        <img
          :src="current.url"
          :alt="title"
          class="gallery__image"
        >
      </button>
      <div
        v-else
        data-testid="gallery-placeholder"
        class="gallery__image gallery__image--empty"
      >
        <i class="pi pi-image" />
      </div>

      <template v-if="hasMany">
        <Button
          icon="pi pi-chevron-left"
          rounded
          aria-label="Poprzednie zdjęcie"
          class="gallery__nav gallery__nav--prev"
          @click="step(-1)"
        />
        <Button
          icon="pi pi-chevron-right"
          rounded
          aria-label="Następne zdjęcie"
          class="gallery__nav gallery__nav--next"
          @click="step(1)"
        />
      </template>

      <p
        v-if="images.length > 0"
        class="gallery__counter"
      >
        {{ index + 1 }} / {{ images.length }}
      </p>
    </div>

    <div
      v-if="hasMany"
      class="gallery__thumbs"
    >
      <button
        v-for="(image, i) in images"
        :key="image.id"
        type="button"
        class="gallery__thumb"
        :class="{ 'gallery__thumb--active': i === index }"
        :aria-label="`Pokaż zdjęcie ${i + 1}`"
        :aria-current="i === index"
        @click="index = i"
      >
        <img
          :src="image.url"
          alt=""
          loading="lazy"
        >
      </button>
    </div>
  </div>

  <Teleport to="body">
    <div
      v-if="isFullscreen && current"
      class="gallery-lightbox"
      data-testid="gallery-lightbox"
      role="dialog"
      aria-modal="true"
      :aria-label="`Galeria: ${title}`"
      @click.self="closeFullscreen"
    >
      <Button
        icon="pi pi-times"
        rounded
        text
        severity="secondary"
        aria-label="Zamknij galerię"
        class="gallery-lightbox__close"
        data-testid="gallery-lightbox-close"
        @click="closeFullscreen"
      />

      <div class="gallery-lightbox__stage">
        <img
          :src="current.url"
          :alt="title"
          class="gallery-lightbox__image"
          data-testid="gallery-lightbox-image"
        >

        <template v-if="hasMany">
          <Button
            icon="pi pi-chevron-left"
            rounded
            aria-label="Poprzednie zdjęcie"
            class="gallery-lightbox__nav gallery-lightbox__nav--prev"
            data-testid="gallery-lightbox-prev"
            @click="step(-1)"
          />
          <Button
            icon="pi pi-chevron-right"
            rounded
            aria-label="Następne zdjęcie"
            class="gallery-lightbox__nav gallery-lightbox__nav--next"
            data-testid="gallery-lightbox-next"
            @click="step(1)"
          />
        </template>

        <p class="gallery-lightbox__counter">
          {{ index + 1 }} / {{ images.length }}
        </p>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.gallery {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  min-width: 0;
  max-width: 100%;
}

.gallery__stage {
  position: relative;
  border-radius: 0.75rem;
  overflow: hidden;
  background: var(--surface-muted);
}

.gallery__image-btn {
  display: block;
  width: 100%;
  padding: 0;
  border: 0;
  background: none;
  cursor: zoom-in;
}

/* Makieta: scena galerii ma ok. 894 x 400 px, czyli proporcje 2,2:1. */
.gallery__image {
  display: block;
  width: 100%;
  aspect-ratio: 2.2 / 1;
  object-fit: cover;
}

.gallery__image--empty {
  display: grid;
  place-items: center;
  font-size: 3rem;
  color: var(--text-muted);
  opacity: 0.4;
}

.gallery__nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: var(--surface-card) !important;
  color: var(--text-strong) !important;
  border: 0 !important;
  box-shadow: 0 2px 10px rgb(0 0 0 / 20%);
}

.gallery__nav--prev {
  left: 0.75rem;
}

.gallery__nav--next {
  right: 0.75rem;
}

.gallery__counter {
  position: absolute;
  right: 0.75rem;
  bottom: 0.75rem;
  margin: 0;
  padding: 0.25rem 0.6rem;
  border-radius: 0.375rem;
  background: rgb(0 0 0 / 60%);
  color: #fff;
  font-size: 0.75rem;
}

.gallery__thumbs {
  display: flex;
  gap: 0.5rem;
  max-width: 100%;
  overflow-x: auto;
  overscroll-behavior-x: contain;
  scrollbar-width: thin;
}

.gallery__thumb {
  flex-shrink: 0;
  width: 7.5rem;
  aspect-ratio: 4 / 3;
  padding: 0;
  border: 2px solid transparent;
  border-radius: 0.5rem;
  overflow: hidden;
  background: none;
  cursor: pointer;
}

.gallery__thumb--active {
  border-color: var(--p-primary-color);
}

.gallery__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.gallery-lightbox {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  flex-direction: column;
  background: rgb(0 0 0 / 92%);
}

.gallery-lightbox__close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  z-index: 2;
  color: #fff !important;
}

.gallery-lightbox__stage {
  position: relative;
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: center;
  min-height: 0;
  padding: 3.5rem 1rem 2rem;
}

.gallery-lightbox__image {
  max-width: min(100%, 72rem);
  max-height: 100%;
  object-fit: contain;
}

.gallery-lightbox__nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgb(255 255 255 / 92%) !important;
  color: var(--text-strong) !important;
  border: 0 !important;
  box-shadow: 0 2px 10px rgb(0 0 0 / 35%);
}

.gallery-lightbox__nav--prev {
  left: 1rem;
}

.gallery-lightbox__nav--next {
  right: 1rem;
}

.gallery-lightbox__counter {
  position: absolute;
  left: 50%;
  bottom: 1rem;
  transform: translateX(-50%);
  margin: 0;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  background: rgb(0 0 0 / 55%);
  color: #fff;
  font-size: 0.875rem;
}
</style>