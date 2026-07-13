<script setup lang="ts">
import Button from 'primevue/button'
import { storeToRefs } from 'pinia'
import { onMounted } from 'vue'

import EmptyState from '@/components/EmptyState.vue'
import { formatPrice } from '@/composables/useFormatting'
import { formatMessageWhen, participantInitials } from '@/composables/useMessageFormatting'
import { useConversationsStore } from '@/stores/conversations'

const store = useConversationsStore()
const { conversations, hasMoreConversations, isLoadingList, isLoadingMore, error } =
  storeToRefs(store)

onMounted(() => void store.loadConversations())
</script>

<template>
  <section class="shell inbox">
    <header class="inbox__header">
      <h1 class="inbox__title">
        Wiadomości
      </h1>
      <p class="inbox__subtitle">
        Twoje rozmowy ze sprzedającymi i kupującymi
      </p>
    </header>

    <p
      v-if="isLoadingList"
      class="inbox__status"
    >
      Wczytywanie…
    </p>
    <p
      v-else-if="error"
      class="inbox__status inbox__status--error"
    >
      {{ error }}
    </p>
    <EmptyState
      v-else-if="conversations.length === 0"
      icon="pi pi-comments"
      title="Nie masz jeszcze żadnych rozmów"
      description="Napisz do sprzedającego z poziomu ogłoszenia, aby rozpocząć kontakt."
    />

    <div
      v-else
      class="inbox__panel surface"
    >
      <ul class="inbox__list">
        <li
          v-for="conversation in conversations"
          :key="conversation.id"
        >
          <RouterLink
            :to="{ name: 'messages.show', params: { id: conversation.id } }"
            class="inbox__row"
            :class="{ 'inbox__row--unread': conversation.is_unread }"
          >
            <div
              class="inbox__avatar"
              aria-hidden="true"
            >
              {{ participantInitials(conversation.other_party?.name ?? '?') }}
            </div>

            <div class="inbox__body">
              <div class="inbox__top">
                <span class="inbox__name">{{ conversation.other_party?.name }}</span>
                <time
                  v-if="conversation.last_message_at"
                  class="inbox__time"
                  :datetime="conversation.last_message_at"
                >
                  {{ formatMessageWhen(conversation.last_message_at) }}
                </time>
              </div>

              <p class="inbox__ad">
                <span class="inbox__ad-title">{{ conversation.ad?.title }}</span>
                <span
                  v-if="conversation.ad?.price !== undefined"
                  class="inbox__ad-price"
                >
                  {{ formatPrice(conversation.ad.price) }}
                </span>
              </p>

              <p class="inbox__preview">
                {{ conversation.last_message_preview }}
              </p>
            </div>

            <span
              v-if="conversation.is_unread"
              class="inbox__dot"
              aria-label="Nieprzeczytane"
            />
          </RouterLink>
        </li>
      </ul>

      <div
        v-if="hasMoreConversations"
        class="inbox__more"
      >
        <Button
          label="Wczytaj starsze rozmowy"
          icon="pi pi-chevron-down"
          severity="secondary"
          text
          :loading="isLoadingMore"
          @click="store.loadMoreConversations()"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
.inbox {
  padding-block: 1.25rem 2rem;
}

.inbox__header {
  margin-bottom: 1rem;
}

.inbox__title {
  margin: 0 0 0.25rem;
  font-size: 1.35rem;
  font-weight: 700;
}

.inbox__subtitle {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.inbox__panel {
  overflow: hidden;
}

.inbox__status {
  margin: 0;
  padding: 1.25rem;
  color: var(--text-muted);
  font-size: 0.875rem;
}

.inbox__status--error {
  color: var(--p-red-500);
}

.inbox__list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.inbox__row {
  position: relative;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.75rem;
  align-items: start;
  padding: 0.875rem 1rem;
  text-decoration: none;
  color: inherit;
  border-bottom: 1px solid var(--surface-border);
  transition: background 0.15s ease;
}

.inbox__row:last-child {
  border-bottom: 0;
}

.inbox__row:hover {
  background: var(--surface-muted);
}

.inbox__row--unread .inbox__name {
  font-weight: 700;
}

.inbox__avatar {
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  flex-shrink: 0;
  border-radius: 50%;
  background: color-mix(in srgb, var(--p-primary-color) 14%, transparent);
  color: var(--p-primary-color);
  font-size: 0.8125rem;
  font-weight: 700;
}

.inbox__body {
  min-width: 0;
}

.inbox__top {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.15rem;
}

.inbox__name {
  font-size: 0.9375rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.inbox__time {
  flex-shrink: 0;
  font-size: 0.75rem;
  color: var(--text-muted);
  white-space: nowrap;
}

.inbox__ad {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0 0 0.2rem;
  font-size: 0.75rem;
  color: var(--text-muted);
  min-width: 0;
}

.inbox__ad-title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.inbox__ad-price {
  flex-shrink: 0;
  font-weight: 600;
}

.inbox__preview {
  margin: 0;
  font-size: 0.8125rem;
  line-height: 1.45;
  color: var(--text-muted);
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.inbox__row--unread .inbox__preview {
  color: var(--text-strong);
}

.inbox__dot {
  width: 0.5rem;
  height: 0.5rem;
  margin-top: 0.35rem;
  border-radius: 50%;
  background: var(--p-primary-color);
}

.inbox__more {
  display: flex;
  justify-content: center;
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--surface-border);
}
</style>