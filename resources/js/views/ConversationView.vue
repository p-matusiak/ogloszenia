<script setup lang="ts">
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Chip from 'primevue/chip'
import Message from 'primevue/message'
import ScrollPanel from 'primevue/scrollpanel'
import Skeleton from 'primevue/skeleton'
import { storeToRefs } from 'pinia'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import ConversationReplyForm from '@/components/messages/ConversationReplyForm.vue'
import ConversationThread from '@/components/messages/ConversationThread.vue'
import { formatPrice } from '@/composables/useFormatting'
import { participantInitials } from '@/composables/useMessageFormatting'
import { useConversationsStore } from '@/stores/conversations'

const props = defineProps<{ id: string }>()

const router = useRouter()
const store = useConversationsStore()
const { activeConversation, messages, isLoadingThread, error } = storeToRefs(store)
const transcriptRef = ref<HTMLElement | null>(null)

const conversationId = computed(() => Number(props.id))

async function scrollTranscriptToBottom(smooth = false): Promise<void> {
  await nextTick()

  const content = transcriptRef.value?.parentElement

  if (!content) {
    return
  }

  content.scrollTo({
    top: content.scrollHeight,
    behavior: smooth ? 'smooth' : 'auto',
  })
}

onMounted(async () => {
  await store.openConversation(conversationId.value)
  await scrollTranscriptToBottom()
})

watch(conversationId, async (next) => {
  await store.openConversation(next)
  await scrollTranscriptToBottom()
})

watch(
  () => messages.value.length,
  async () => {
    await scrollTranscriptToBottom(true)
  },
)

function goBack(): void {
  void router.push({ name: 'messages' }).catch(() => undefined)
}
</script>

<template>
  <section class="shell chat-page">
    <div
      v-if="isLoadingThread"
      class="chat-card surface"
    >
      <div class="chat-card__toolbar">
        <Skeleton
          shape="circle"
          size="2.5rem"
        />
        <div class="chat-card__toolbar-meta">
          <Skeleton
            width="9rem"
            height="1rem"
          />
          <Skeleton
            width="12rem"
            height="0.75rem"
            class="chat-card__toolbar-gap"
          />
        </div>
      </div>
      <div class="chat-card__loading">
        <Skeleton
          width="68%"
          height="3rem"
          border-radius="1rem"
        />
        <Skeleton
          width="52%"
          height="2.5rem"
          border-radius="1rem"
          class="chat-card__loading-out"
        />
        <Skeleton
          width="60%"
          height="3rem"
          border-radius="1rem"
        />
      </div>
    </div>

    <Message
      v-else-if="error || !activeConversation"
      severity="error"
      class="chat-page__error"
    >
      {{ error ?? 'Nie znaleziono rozmowy.' }}
    </Message>

    <div
      v-else
      class="chat-card surface"
    >
      <header class="chat-card__toolbar">
        <Button
          icon="pi pi-arrow-left"
          aria-label="Wróć do listy wiadomości"
          severity="secondary"
          text
          rounded
          class="chat-card__back"
          @click="goBack"
        />

        <Avatar
          :label="participantInitials(activeConversation.other_party?.name ?? '?')"
          shape="circle"
          size="large"
          class="chat-card__avatar"
        />

        <div class="chat-card__identity">
          <h1 class="chat-card__title">
            {{ activeConversation.other_party?.name }}
          </h1>
          <div
            v-if="activeConversation.ad"
            class="chat-card__context"
          >
            <RouterLink
              :to="{ name: 'ads.show', params: { slug: activeConversation.ad.slug } }"
              class="chat-card__ad-link"
            >
              <Chip
                :label="activeConversation.ad.title"
                icon="pi pi-tag"
                class="chat-card__chip"
              />
            </RouterLink>
            <span
              v-if="activeConversation.ad.price !== undefined"
              class="chat-card__price"
            >
              {{ formatPrice(activeConversation.ad.price) }}
            </span>
          </div>
        </div>
      </header>

      <ScrollPanel class="chat-card__scroll">
        <div ref="transcriptRef">
          <ConversationThread
            :messages="messages"
            :other-party-name="activeConversation.other_party?.name ?? ''"
          />
        </div>
      </ScrollPanel>

      <footer class="chat-card__composer">
        <ConversationReplyForm />
      </footer>
    </div>
  </section>
</template>

<style scoped>
.chat-page {
  padding-block: 1.25rem 2rem;
}

.chat-page__error {
  margin: 0;
}

.chat-card {
  display: flex;
  flex-direction: column;
  height: min(34rem, calc(100dvh - var(--header-height) - 3.5rem));
  min-height: 22rem;
  overflow: hidden;
}

.chat-card__toolbar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0.875rem;
  border-bottom: 1px solid var(--surface-border);
  background: var(--surface-muted);
  flex-shrink: 0;
}

.chat-card__back {
  flex-shrink: 0;
}

.chat-card__avatar {
  flex-shrink: 0;
  background: color-mix(in srgb, var(--p-primary-color) 14%, transparent);
  color: var(--p-primary-color);
  font-weight: 700;
}

.chat-card__identity {
  min-width: 0;
  flex: 1;
}

.chat-card__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.25;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chat-card__context {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.2rem;
  min-width: 0;
}

.chat-card__ad-link {
  min-width: 0;
  text-decoration: none;
}

.chat-card__chip {
  max-width: 100%;
}

.chat-card__chip :deep(.p-chip-label) {
  max-width: min(36rem, 55vw);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-card__price {
  flex-shrink: 0;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-muted);
}

.chat-card__scroll {
  flex: 1;
  min-height: 0;
}

.chat-card__scroll :deep(.p-scrollpanel) {
  height: 100%;
}

.chat-card__scroll :deep([data-pc-section='content']) {
  height: 100%;
}

.chat-card__scroll :deep([data-pc-section='barx']) {
  display: none;
}

.chat-card__composer {
  flex-shrink: 0;
  padding: 0.65rem 1rem 0.75rem;
  border-top: 1px solid var(--surface-border);
  background: var(--surface-card);
}

.chat-card__toolbar-meta {
  flex: 1;
}

.chat-card__toolbar-gap {
  margin-top: 0.35rem;
}

.chat-card__loading {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1.25rem 1rem;
}

.chat-card__loading-out {
  align-self: flex-end;
}

@media (width >= 48rem) {
  .chat-card {
    height: min(36rem, calc(100dvh - var(--header-height) - 4rem));
  }
}
</style>