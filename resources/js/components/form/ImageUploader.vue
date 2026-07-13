<script setup lang="ts">
import { computed, ref } from 'vue'

import type { AdImage } from '@/types/api'

const props = defineProps<{
  files: File[]
  existingImages: AdImage[]
  removedIds: number[]
  maxImages: number
  maxSizeMb: number
}>()

const emit = defineEmits<{
  'update:files': [files: File[]]
  'remove-existing': [id: number]
}>()

const isDraggingFile = ref(false)
const draggedIndex = ref<number | null>(null)

const keptExisting = computed(() =>
  props.existingImages.filter((image) => !props.removedIds.includes(image.id)),
)

const total = computed(() => keptExisting.value.length + props.files.length)
const remaining = computed(() => props.maxImages - total.value)

/** Podgląd nowych plików; URL zwalniamy dopiero przy usunięciu miniatury. */
const previews = new Map<File, string>()

function previewOf(file: File): string {
  let url = previews.get(file)
  if (url === undefined) {
    url = URL.createObjectURL(file)
    previews.set(file, url)
  }

  return url
}

function accept(incoming: FileList | null): void {
  if (incoming === null) {
    return
  }

  emit('update:files', [...props.files, ...Array.from(incoming)].slice(0, remaining.value + props.files.length))
}

function onDrop(event: DragEvent): void {
  isDraggingFile.value = false
  accept(event.dataTransfer?.files ?? null)
}

function onPick(event: Event): void {
  const input = event.target as HTMLInputElement
  accept(input.files)
  input.value = ''
}

function removeFile(index: number): void {
  const file = props.files[index]
  if (file) {
    URL.revokeObjectURL(previews.get(file) ?? '')
    previews.delete(file)
  }

  emit('update:files', props.files.filter((_, i) => i !== index))
}

/** Kolejność decyduje, które zdjęcie jest główne — stąd przeciąganie. */
function onReorderDrop(targetIndex: number): void {
  const from = draggedIndex.value
  draggedIndex.value = null

  if (from === null || from === targetIndex) {
    return
  }

  const next = [...props.files]
  const [moved] = next.splice(from, 1)
  if (moved) {
    next.splice(targetIndex, 0, moved)
    emit('update:files', next)
  }
}
</script>

<template>
  <div class="uploader">
    <label
      class="dropzone"
      :class="{ 'dropzone--active': isDraggingFile }"
      @dragover.prevent="isDraggingFile = true"
      @dragleave="isDraggingFile = false"
      @drop.prevent="onDrop"
    >
      <input
        type="file"
        accept="image/jpeg,image/png,image/webp"
        multiple
        class="dropzone__input"
        :disabled="remaining <= 0"
        @change="onPick"
      >
      <i class="pi pi-cloud-upload dropzone__icon" />
      <p class="dropzone__title">
        Przeciągnij zdjęcia tutaj
      </p>
      <p class="dropzone__link">
        lub kliknij, aby dodać
      </p>
      <p class="dropzone__hint">
        Dodaj do {{ maxImages }} zdjęć (JPG, PNG, WebP do {{ maxSizeMb }} MB)
      </p>
    </label>

    <ul
      v-if="total > 0"
      class="thumbs"
    >
      <li
        v-for="image in keptExisting"
        :key="`existing-${image.id}`"
        class="thumb"
      >
        <img
          :src="image.url"
          alt=""
        >
        <button
          type="button"
          class="thumb__remove"
          :aria-label="`Usuń zdjęcie ${image.id}`"
          @click="emit('remove-existing', image.id)"
        >
          <i class="pi pi-times" />
        </button>
      </li>

      <li
        v-for="(file, index) in files"
        :key="file.name + index"
        class="thumb"
        :class="{ 'thumb--primary': keptExisting.length === 0 && index === 0 }"
        draggable="true"
        @dragstart="draggedIndex = index"
        @dragover.prevent
        @drop.prevent="onReorderDrop(index)"
      >
        <img
          :src="previewOf(file)"
          alt=""
        >
        <span
          v-if="keptExisting.length === 0 && index === 0"
          class="thumb__badge"
        >Zdjęcie główne</span>
        <span class="thumb__index">{{ keptExisting.length + index + 1 }}</span>
        <button
          type="button"
          class="thumb__remove"
          :aria-label="`Usuń ${file.name}`"
          @click="removeFile(index)"
        >
          <i class="pi pi-times" />
        </button>
      </li>
    </ul>

    <p class="uploader__hint">
      <i class="pi pi-arrows-v" />
      Przeciągnij, aby zmienić kolejność zdjęć. Pozostało miejsc: {{ remaining }}.
    </p>
  </div>
</template>

<style scoped>
.uploader {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.dropzone {
  position: relative;
  display: grid;
  place-items: center;
  gap: 0.15rem;
  padding: 1.75rem 1rem;
  border: 1px dashed var(--surface-border);
  border-radius: 0.625rem;
  background: var(--surface-muted);
  text-align: center;
  cursor: pointer;
}

.dropzone--active {
  border-color: var(--p-primary-color);
  background: color-mix(in srgb, var(--p-primary-color) 6%, transparent);
}

.dropzone__input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.dropzone__icon {
  font-size: 1.5rem;
  color: var(--text-muted);
}

.dropzone__title {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  font-weight: 600;
}

.dropzone__link {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--p-primary-color);
}

.dropzone__hint {
  margin: 0.35rem 0 0;
  font-size: 0.75rem;
  color: var(--text-muted);
}

.thumbs {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.625rem;
}

.thumb {
  position: relative;
  width: 7rem;
  aspect-ratio: 1 / 1;
  border-radius: 0.5rem;
  overflow: hidden;
  cursor: grab;
}

.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.thumb__badge {
  position: absolute;
  top: 0.375rem;
  left: 0.375rem;
  padding: 0.15rem 0.4rem;
  border-radius: 0.25rem;
  background: var(--p-primary-color);
  color: #fff;
  font-size: 0.625rem;
  font-weight: 600;
}

.thumb__index {
  position: absolute;
  bottom: 0.375rem;
  left: 0.375rem;
  display: grid;
  place-items: center;
  width: 1.35rem;
  height: 1.35rem;
  border-radius: 50%;
  background: #fff;
  color: #111;
  font-size: 0.7rem;
  font-weight: 600;
}

.thumb__remove {
  position: absolute;
  top: 0.375rem;
  right: 0.375rem;
  display: grid;
  place-items: center;
  width: 1.5rem;
  height: 1.5rem;
  border: 0;
  border-radius: 50%;
  background: rgb(0 0 0 / 60%);
  color: #fff;
  font-size: 0.65rem;
  cursor: pointer;
}

.uploader__hint {
  margin: 0;
  text-align: right;
  font-size: 0.75rem;
  color: var(--text-muted);
}
</style>
