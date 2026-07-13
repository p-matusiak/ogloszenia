<script setup lang="ts">
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tree from 'primevue/tree'
import type { TreeNode } from 'primevue/treenode'
import { useToast } from 'primevue/usetoast'
import { computed, onMounted, ref } from 'vue'

import { errorMessage } from '@/api/client'
import { createCategory, deleteCategory, fetchAdminCategories, updateCategory } from '@/api/modules/v1/admin'
import type { Category } from '@/types/api'

const toast = useToast()

const roots = ref<Category[]>([])
const isLoading = ref(false)
const isSaving = ref(false)
const name = ref('')
const parentId = ref<number | null>(null)
const isVisible = ref(true)
const editingSlug = ref<string | null>(null)
const treeQuery = ref('')

interface ParentOption {
  id: number
  label: string
}

const normalizedTreeQuery = computed(() => normalizeSearch(treeQuery.value))
const filteredRoots = computed(() =>
  normalizedTreeQuery.value === ''
    ? roots.value
    : filterTree(roots.value, normalizedTreeQuery.value),
)
const flatCategories = computed(() => flattenCategories(roots.value))
const parentOptions = computed<ParentOption[]>(() =>
  flatCategories.value.map((category) => ({
    id: category.id,
    label: categoryPath(category),
  })).filter((category) => category.id !== editingCategory.value?.id),
)

const treeNodes = computed<TreeNode[]>(() =>
  filteredRoots.value.map(toTreeNode),
)

const expandedKeys = computed<Record<string, boolean>>(() =>
  Object.fromEntries(flatCategories.value.map((category) => [String(category.id), true])),
)

const slugPreview = computed(() => toSlug(name.value))
const editingCategory = computed(() => findCategoryBySlug(roots.value, editingSlug.value))
const selectedParent = computed(() => flatCategories.value.find((category) => category.id === parentId.value) ?? null)
const visibleCount = computed(() => flatCategories.value.filter((category) => category.is_visible).length)
const hiddenCount = computed(() => flatCategories.value.length - visibleCount.value)
const submitLabel = computed(() => editingCategory.value ? 'Zapisz zmiany' : 'Dodaj')
const submitIcon = computed(() => editingCategory.value ? 'pi pi-check' : 'pi pi-plus')
const formTitle = computed(() => editingCategory.value ? `Edytujesz: ${editingCategory.value.name}` : 'Nowa kategoria')
const formHint = computed(() => {
  if (editingCategory.value) {
    return 'Zmieniasz nazwę, rodzica i widoczność wybranej kategorii.'
  }

  if (selectedParent.value) {
    return `Nowa podkategoria zostanie dodana pod: ${categoryPath(selectedParent.value)}`
  }

  return 'Dodajesz kategorię główną. Możesz też kliknąć "+" przy węźle drzewa, aby od razu tworzyć podkategorię.'
})

async function load(): Promise<void> {
  isLoading.value = true
  try {
    roots.value = await fetchAdminCategories()
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  } finally {
    isLoading.value = false
  }
}

async function onCreate(): Promise<void> {
  isSaving.value = true
  try {
    if (editingCategory.value) {
      await updateCategory(editingCategory.value.slug, {
        name: name.value,
        parent_id: parentId.value,
        is_visible: isVisible.value,
      })
      toast.add({ severity: 'success', summary: 'Kategoria zaktualizowana', life: 4000 })
    } else {
      await createCategory({
        name: name.value,
        parent_id: parentId.value,
        is_visible: isVisible.value,
      })
      toast.add({ severity: 'success', summary: 'Kategoria dodana', life: 4000 })
    }
    resetForm()
    await load()
  } catch (caught: unknown) {
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 5000 })
  } finally {
    isSaving.value = false
  }
}

async function onDelete(category: Category): Promise<void> {
  try {
    await deleteCategory(category.slug)
    toast.add({ severity: 'success', summary: 'Kategoria usunięta', life: 4000 })
    if (editingSlug.value === category.slug) {
      resetForm()
    }
    await load()
  } catch (caught: unknown) {
    // Surfaces CATEGORY_IN_USE with its ad count.
    toast.add({ severity: 'error', summary: errorMessage(caught), life: 6000 })
  }
}

onMounted(load)

function onEdit(category: Category): void {
  editingSlug.value = category.slug
  name.value = category.name
  parentId.value = category.parent_id
  isVisible.value = category.is_visible
}

function onAddChild(category: Category): void {
  editingSlug.value = null
  name.value = ''
  parentId.value = category.id
  isVisible.value = true
}

function resetForm(): void {
  editingSlug.value = null
  name.value = ''
  parentId.value = null
  isVisible.value = true
}

function toTreeNode(category: Category): TreeNode {
  return {
    key: String(category.id),
    label: category.name,
    data: category,
    children: (category.children ?? []).map(toTreeNode),
  }
}

function flattenCategories(categories: Category[], ancestors: Category[] = []): Array<Category & { ancestors: Category[] }> {
  return categories.flatMap((category) => {
    const current = { ...category, ancestors }

    return [current, ...flattenCategories(category.children ?? [], [...ancestors, category])]
  })
}

function categoryPath(category: Pick<Category, 'name'> & { ancestors?: Pick<Category, 'name'>[] }): string {
  return [...(category.ancestors ?? []).map((ancestor) => ancestor.name), category.name].join(' > ')
}

function toSlug(value: string): string {
  return value
    .trim()
    .toLocaleLowerCase('pl-PL')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function normalizeSearch(value: string): string {
  return value
    .trim()
    .toLocaleLowerCase('pl-PL')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

function filterTree(categories: Category[], query: string, ancestors: Category[] = []): Category[] {
  return categories.flatMap((category) => {
    const nextAncestors = [...ancestors, category]
    const matchesSelf = matchesCategory(category, query, ancestors)
    const matchingChildren = filterTree(category.children ?? [], query, nextAncestors)

    if (!matchesSelf && matchingChildren.length === 0) {
      return []
    }

    return [{
      ...category,
      children: matchingChildren,
    }]
  })
}

function matchesCategory(category: Category, query: string, ancestors: Category[]): boolean {
  const haystack = normalizeSearch([
    category.name,
    category.slug,
    categoryPath({ name: category.name, ancestors }),
  ].join(' '))

  return haystack.includes(query)
}

function findCategoryBySlug(categories: Category[], slug: string | null): Category | null {
  if (slug === null) {
    return null
  }

  for (const category of categories) {
    if (category.slug === slug) {
      return category
    }

    const child = findCategoryBySlug(category.children ?? [], slug)
    if (child) {
      return child
    }
  }

  return null
}
</script>

<template>
  <div class="panel">
    <section class="panel__card panel__card--tree">
      <div class="panel__section-heading">
        <div>
          <h2>Drzewo kategorii</h2>
          <p>Z tego widoku wyszukasz kategorię, edytujesz ją albo od razu dodasz podkategorię.</p>
        </div>
      </div>

      <div class="stats">
        <div class="stats__item">
          <span class="stats__label">Łącznie</span>
          <strong>{{ flatCategories.length }}</strong>
        </div>
        <div class="stats__item">
          <span class="stats__label">Główne</span>
          <strong>{{ roots.length }}</strong>
        </div>
        <div class="stats__item">
          <span class="stats__label">Widoczne</span>
          <strong>{{ visibleCount }}</strong>
        </div>
        <div class="stats__item">
          <span class="stats__label">Ukryte</span>
          <strong>{{ hiddenCount }}</strong>
        </div>
      </div>

      <div class="tree-tools">
        <InputText
          v-model="treeQuery"
          placeholder="Szukaj po nazwie, slugu albo ścieżce"
        />
        <span class="tree-tools__hint">
          Kliknij `+`, aby od razu przygotować nową podkategorię pod wybranym węzłem.
        </span>
      </div>

      <div class="tree-wrap">
        <Tree
          :value="treeNodes"
          :expanded-keys="expandedKeys"
          :loading="isLoading"
          class="tree"
        >
          <template #default="{ node }">
            <div class="node">
              <div class="node__content">
                <strong>{{ (node.data as Category).name }}</strong>
                <span class="node__meta">
                  <code>{{ (node.data as Category).slug }}</code>
                  <span
                    class="node__badge"
                    :class="{ 'node__badge--hidden': !(node.data as Category).is_visible }"
                  >
                    {{ (node.data as Category).is_visible ? 'Widoczna' : 'Ukryta' }}
                  </span>
                  <span>{{ categoryPath(node.data as Category) }}</span>
                </span>
              </div>
              <div class="node__actions">
                <Button
                  icon="pi pi-plus"
                  severity="success"
                  text
                  size="small"
                  :aria-label="`Dodaj podkategorię pod ${node.label}`"
                  @click="onAddChild(node.data as Category)"
                />
                <Button
                  icon="pi pi-pencil"
                  severity="secondary"
                  text
                  size="small"
                  :aria-label="`Edytuj ${node.label}`"
                  @click="onEdit(node.data as Category)"
                />
                <Button
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  size="small"
                  :aria-label="`Usuń ${node.label}`"
                  @click="onDelete(node.data as Category)"
                />
              </div>
            </div>
          </template>
        </Tree>
      </div>
    </section>

    <section class="panel__card panel__card--form">
      <form
        class="create"
        @submit.prevent="onCreate"
      >
        <div class="panel__section-heading">
          <div>
            <h2>{{ formTitle }}</h2>
            <p>{{ formHint }}</p>
          </div>
          <Button
            v-if="editingCategory || selectedParent"
            type="button"
            label="Wyczyść"
            severity="secondary"
            text
            size="small"
            @click="resetForm"
          />
        </div>

        <div class="context">
          <div class="context__item">
            <span class="context__label">Rodzaj</span>
            <strong>{{ editingCategory ? 'Edycja kategorii' : selectedParent ? 'Nowa podkategoria' : 'Nowa kategoria główna' }}</strong>
          </div>
          <div class="context__item">
            <span class="context__label">Rodzic</span>
            <strong>{{ selectedParent ? categoryPath(selectedParent) : 'Brak' }}</strong>
          </div>
          <div class="context__item">
            <span class="context__label">Slug</span>
            <code>{{ slugPreview || 'kategoria' }}</code>
          </div>
        </div>

        <div class="create__fields">
          <label class="field">
            <span class="field__label">Nazwa kategorii</span>
            <InputText
              v-model="name"
              placeholder="Np. Laptopy gamingowe"
              required
            />
          </label>

          <label class="field">
            <span class="field__label">Kategoria nadrzędna</span>
            <Select
              v-model="parentId"
              :options="parentOptions"
              option-label="label"
              option-value="id"
              placeholder="Kategoria nadrzędna (opcjonalnie)"
              show-clear
            />
          </label>

          <label class="field field--checkbox">
            <Checkbox
              v-model="isVisible"
              input-id="is_visible"
              binary
            />
            <span>
              <span class="field__label">Widoczna</span>
              <span class="field__help">Ukryte kategorie nie pojawią się publicznie.</span>
            </span>
          </label>
        </div>

        <div class="create__actions">
          <Button
            type="submit"
            :label="submitLabel"
            :icon="submitIcon"
            :loading="isSaving"
          />
          <Button
            v-if="selectedParent && !editingCategory"
            type="button"
            label="Ustaw jako główną"
            severity="secondary"
            outlined
            @click="parentId = null"
          />
        </div>
      </form>
    </section>
  </div>
</template>

<style scoped>
.panel {
  display: grid;
  gap: 1.25rem;
  padding-top: 0.5rem;
}

.panel__card {
  border: 1px solid var(--surface-border);
  border-radius: 1rem;
  background: color-mix(in srgb, var(--surface-card) 92%, white 8%);
  box-shadow: 0 20px 40px rgb(15 23 42 / 0.06);
  padding: 1rem;
}

.panel__section-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.panel__section-heading h2 {
  margin: 0;
  font-size: 1.05rem;
}

.panel__section-heading p {
  margin: 0.25rem 0 0;
  color: var(--text-muted);
  font-size: 0.9rem;
}

.stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.stats__item {
  padding: 0.85rem 0.9rem;
  border-radius: 0.85rem;
  background: color-mix(in srgb, var(--surface-ground) 70%, var(--surface-card) 30%);
  border: 1px solid var(--surface-border);
}

.stats__label,
.tree-tools__hint,
.context__label,
.field__help {
  color: var(--text-muted);
}

.stats__item strong {
  display: block;
  margin-top: 0.2rem;
  font-size: 1.15rem;
}

.tree-tools {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1rem;
}

.tree-tools__hint {
  font-size: 0.8rem;
}

.tree-wrap {
  border: 1px solid var(--surface-border);
  border-radius: 0.85rem;
  overflow: hidden;
  background: var(--surface-ground);
}

.create {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.context {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

.context__item {
  padding: 0.85rem 0.9rem;
  border: 1px solid var(--surface-border);
  border-radius: 0.85rem;
  background: var(--surface-ground);
}

.context__item strong,
.context__item code {
  display: block;
  margin-top: 0.2rem;
}

.create__fields {
  display: grid;
  gap: 0.9rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.field__label {
  font-size: 0.9rem;
  font-weight: 600;
}

.field--checkbox {
  flex-direction: row;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.85rem 0.9rem;
  border: 1px solid var(--surface-border);
  border-radius: 0.85rem;
  background: var(--surface-ground);
}

.field__help {
  display: block;
  margin-top: 0.2rem;
  font-size: 0.8rem;
}

.create__actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.node {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  justify-content: space-between;
  width: 100%;
  padding: 0.25rem 0;
}

.node :deep(.p-button) {
  flex-shrink: 0;
}

.node__content {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.node__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: var(--text-muted);
}

.node__actions {
  display: flex;
  align-items: center;
  gap: 0.15rem;
}

.node__badge {
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
  color: var(--p-primary-color);
}

.node__badge--hidden {
  background: color-mix(in srgb, #ef4444 12%, transparent);
  color: #b91c1c;
}

@media (width >= 72rem) {
  .panel {
    grid-template-columns: minmax(0, 1.35fr) minmax(22rem, 0.95fr);
    align-items: start;
  }

  .panel__card--form {
    position: sticky;
    top: 1rem;
  }

  .stats,
  .context {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
