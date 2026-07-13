<script setup lang="ts">
import Button from 'primevue/button'
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { useFavoritesStore } from '@/stores/favorites'

const props = defineProps<{ adId: number; adSlug: string }>()

const auth = useAuthStore()
const favorites = useFavoritesStore()
const router = useRouter()
const route = useRoute()
const isBusy = ref(false)

onMounted(() => {
  if (auth.isAuthenticated) {
    void favorites.ensureIds()
  }
})

async function toggle(): Promise<void> {
  if (!auth.isAuthenticated) {
    await router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }

  if (isBusy.value) {
    return
  }
  isBusy.value = true
  try {
    await favorites.toggle({ id: props.adId, slug: props.adSlug })
  } finally {
    isBusy.value = false
  }
}
</script>

<template>
  <Button
    :icon="favorites.isFavorited(adId) ? 'pi pi-heart-fill' : 'pi pi-heart'"
    :aria-label="favorites.isFavorited(adId) ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'"
    :aria-pressed="favorites.isFavorited(adId)"
    :loading="isBusy"
    severity="secondary"
    rounded
    @click="toggle"
  />
</template>
