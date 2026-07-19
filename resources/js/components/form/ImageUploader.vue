<script setup lang="ts">
import { computed, ref } from 'vue'

import { errorMessage, validationErrors } from '@/api/client'
import { deleteTemporaryAdImage, uploadTemporaryAdImages } from '@/api/modules/v1/ads'
import type { AdImage, TemporaryAdUpload } from '@/types/api'

const props = defineProps<{
  uploads: TemporaryAdUpload[]
  existingImages: AdImage[]
  removedIds: number[]
  maxImages: number
  maxSizeMb: number
}>()

const emit = defineEmits<{
  'update:uploads': [uploads: TemporaryAdUpload[]]
  'remove-existing': [id: number]
}>()

const isDraggingFile = ref(false)
const draggedIndex = ref<number | null>(null)
const isUploading = ref(false)
const deletingToken = ref<string | null>(null)
const uploadError = ref<string | null>(null)

const keptExisting = computed(() =>
  props.existingImages.filter((image) => !props.removedIds.includes(image.id)),
)

const total = computed(() => keptExisting.value.length + props.uploads.length)
const remaining = computed(() => props.maxImages - total.value)

async function accept(incoming: FileList | null): Promise<void> {
  if (incoming === null || remaining.value <= 0 || isUploading.value) {
    return
  }

  uploadError.value = null
  isUploading.value = true

  try {
    const files = Array.from(incoming).slice(0, remaining.value)
    const uploaded = await uploadTemporaryAdImages(files)
    emit('update:uploads', [...props.uploads, ...uploaded])
  } catch (caught: unknown) {
    const fieldErrors = validationErrors(caught)
    uploadError.value =
      fieldErrors.images
      ?? fieldErrors['images.0']
      ?? errorMessage(caught, 'Nie udało się wgrać zdjęć.')
  } finally {
    isUploading.value = false
  }
}

async function onDrop(event: DragEvent): Promise<void> {
  isDraggingFile.value = false
  await accept(event.dataTransfer?.files ?? null)
}

async function onPick(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  await accept(input.files)
  input.value = ''
}

async function removeUpload(index: number): Promise<void> {
  const upload = props.uploads[index]

  if (upload === undefined) {
    return
  }

  uploadError.value = null
  deletingToken.value = upload.token

  try {
    await deleteTemporaryAdImage(upload.token)
    emit('update:uploads', props.uploads.filter((_, currentIndex) => currentIndex !== index))
  } catch (caught: unknown) {
    uploadError.value = errorMessage(caught, 'Nie udało się usunąć tymczasowego zdjęcia.')
  } finally {
    deletingToken.value = null
  }
}

/** Kolejność decyduje, które zdjęcie jest główne — stąd przeciąganie. */
function onReorderDrop(targetIndex: number): void {
  const from = draggedIndex.value
  draggedIndex.value = null

  if (from === null || from === targetIndex) {
    return
  }

  const next = [...props.uploads]
  const [moved] = next.splice(from, 1)
  if (moved !== undefined) {
    next.splice(targetIndex, 0, moved)
    emit('update:uploads', next)
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
        :disabled="remaining <= 0 || isUploading"
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
      <p
        v-if="isUploading"
        class="dropzone__hint"
      >
        Wgrywanie zdjęć…
      </p>
    </label>

    <p
      v-if="uploadError"
      class="uploader__error"
    >
      {{ uploadError }}
    </p>

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
        v-for="(upload, index) in uploads"
        :key="upload.token"
        class="thumb"
        :class="{ 'thumb--primary': keptExisting.length === 0 && index === 0 }"
        draggable="true"
        @dragstart="draggedIndex = index"
        @dragover.prevent
        @drop.prevent="onReorderDrop(index)"
      >
        <img
          :src="upload.preview_url"
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
          :aria-label="`Usuń ${upload.original_name}`"
          :disabled="deletingToken === upload.token"
          @click="removeUpload(index)"
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
  padding: 0.2rem 0.4rem;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.82);
  color: white;
  font-size: 0.625rem;
  font-weight: 600;
}

.thumb__index {
  position: absolute;
  left: 0.375rem;
  bottom: 0.375rem;
  min-width: 1.35rem;
  height: 1.35rem;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  font-size: 0.6875rem;
  font-weight: 700;
}

.thumb__remove {
  position: absolute;
  top: 0.3rem;
  right: 0.3rem;
  width: 1.7rem;
  height: 1.7rem;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.8);
  color: white;
  cursor: pointer;
}

.thumb--primary {
  outline: 2px solid color-mix(in srgb, var(--p-primary-color) 55%, white);
  outline-offset: 1px;
}

.uploader__hint {
  margin: 0;
  font-size: 0.75rem;
  color: var(--text-muted);
}

.uploader__error {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--p-red-500);
}
</style>
