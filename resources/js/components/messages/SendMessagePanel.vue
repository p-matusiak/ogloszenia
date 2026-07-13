<script setup lang="ts">
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { errorMessage } from '@/api/client'
import { useConversationsStore } from '@/stores/conversations'

const props = defineProps<{ slug: string }>()
const emit = defineEmits<{ sent: [conversationId: number] }>()

const router = useRouter()
const store = useConversationsStore()
const toast = useToast()
const body = ref('')
const isSending = ref(false)

async function send(): Promise<void> {
  const trimmed = body.value.trim()
  if (trimmed === '') {
    return
  }

  isSending.value = true
  try {
    const conversation = await store.startFromAd(props.slug, trimmed)
    if (!conversation) {
      toast.add({
        severity: 'error',
        summary: store.error ?? errorMessage(null, 'Nie udało się wysłać wiadomości.'),
        life: 5000,
      })
      return
    }

    body.value = ''
    toast.add({ severity: 'success', summary: 'Wiadomość wysłana', life: 4000 })
    emit('sent', conversation.id)
    await router.push({ name: 'messages.show', params: { id: conversation.id } }).catch(() => undefined)
  } finally {
    isSending.value = false
  }
}
</script>

<template>
  <div class="send">
    <p class="send__hint">
      Napisz do sprzedającego. Odpowiedź pojawi się w Twoich wiadomościach.
    </p>

    <Textarea
      v-model="body"
      rows="5"
      auto-resize
      placeholder="Treść wiadomości"
      aria-label="Treść wiadomości"
      fluid
    />

    <Button
      label="Wyślij wiadomość"
      icon="pi pi-send"
      fluid
      :disabled="body.trim() === ''"
      :loading="isSending"
      @click="send"
    />
  </div>
</template>

<style scoped>
.send {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.send__hint {
  margin: 0;
  color: var(--text-muted);
  font-size: 0.875rem;
}
</style>