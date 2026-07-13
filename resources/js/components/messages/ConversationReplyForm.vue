<script setup lang="ts">
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'
import { ref } from 'vue'

import { errorMessage } from '@/api/client'
import { useConversationsStore } from '@/stores/conversations'

const store = useConversationsStore()
const toast = useToast()
const body = ref('')

async function send(): Promise<void> {
  const trimmed = body.value.trim()
  if (trimmed === '') {
    return
  }

  const sent = await store.sendReply(trimmed)
  if (!sent) {
    toast.add({
      severity: 'error',
      summary: store.error ?? errorMessage(null, 'Nie udało się wysłać wiadomości.'),
      life: 5000,
    })
    return
  }

  body.value = ''
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    void send()
  }
}
</script>

<template>
  <form
    class="composer"
    @submit.prevent="send"
  >
    <div class="composer__bar">
      <Textarea
        v-model="body"
        rows="1"
        auto-resize
        placeholder="Napisz wiadomość…"
        aria-label="Treść wiadomości"
        class="composer__input"
        @keydown="onKeydown"
      />
      <Button
        type="submit"
        icon="pi pi-send"
        aria-label="Wyślij wiadomość"
        size="small"
        rounded
        :disabled="body.trim() === ''"
        :loading="store.isSending"
        class="composer__send"
      />
    </div>
    <span class="composer__hint">Enter — wyślij · Shift+Enter — nowa linia</span>
  </form>
</template>

<style scoped>
.composer {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  width: 100%;
}

/* Bez dodatkowej ramki — stopka karty już oddziela composer od wątku. */
.composer__bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  min-height: 2.5rem;
}

.composer__input {
  flex: 1 1 auto;
  min-width: 0;
}

.composer__input:deep(textarea) {
  display: block;
  width: 100%;
  min-height: 1.5rem;
  max-height: 7rem;
  margin: 0;
  padding: 0.45rem 0;
  border: 0 !important;
  outline: none !important;
  background: transparent !important;
  box-shadow: none !important;
  font: inherit;
  font-size: 0.9375rem;
  line-height: 1.45;
  resize: none;
  color: inherit;
}

.composer__input:deep(textarea:focus),
.composer__input:deep(textarea:focus-visible),
.composer__input:deep(textarea:hover) {
  border: 0 !important;
  outline: none !important;
  box-shadow: none !important;
}

.composer__input:deep(textarea::placeholder) {
  color: var(--text-muted);
}

.composer__send {
  flex: 0 0 auto;
}

.composer__send:deep(.p-button) {
  width: 2.125rem;
  height: 2.125rem;
  min-width: 2.125rem;
  padding: 0;
}

.composer__send:deep(.p-button .p-button-icon) {
  font-size: 0.8125rem;
}

.composer__hint {
  padding-inline: 0.15rem;
  font-size: 0.6875rem;
  color: var(--text-muted);
}

@media (width < 30rem) {
  .composer__hint {
    display: none;
  }
}
</style>