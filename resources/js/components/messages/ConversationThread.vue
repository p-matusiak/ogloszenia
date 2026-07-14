<script setup lang="ts">
import Avatar from 'primevue/avatar'
import Divider from 'primevue/divider'
import { computed } from 'vue'

import { formatMessageWhen, messageDayLabel, participantInitials } from '@/composables/useMessageFormatting'
import type { Message } from '@/types/api'

const props = defineProps<{
  messages: Message[]
  otherPartyName: string
  otherPartyAvatarUrl?: string | null
}>()

interface MessageDayGroup {
  key: string
  label: string
  messages: Message[]
}

const groups = computed<MessageDayGroup[]>(() => {
  const result: MessageDayGroup[] = []

  for (const message of props.messages) {
    const dayKey = message.created_at?.slice(0, 10) ?? `id-${message.id}`
    const label = messageDayLabel(message.created_at)
    const last = result[result.length - 1]

    if (last?.key === dayKey) {
      last.messages.push(message)
      continue
    }

    result.push({ key: dayKey, label, messages: [message] })
  }

  return result
})

function showAvatar(index: number, group: MessageDayGroup): boolean {
  if (group.messages[index]?.is_mine) {
    return false
  }

  const previous = group.messages[index - 1]
  return previous === undefined || previous.is_mine
}
</script>

<template>
  <div class="thread">
    <p
      v-if="messages.length === 0"
      class="thread__empty"
    >
      <i
        class="pi pi-comments"
        aria-hidden="true"
      />
      Rozpocznij rozmowę — napisz pierwszą wiadomość poniżej.
    </p>

    <section
      v-for="group in groups"
      :key="group.key"
      class="thread__day"
    >
      <Divider
        align="center"
        type="dashed"
        class="thread__divider"
      >
        <span class="thread__day-label">{{ group.label }}</span>
      </Divider>

      <TransitionGroup
        name="thread-msg"
        tag="div"
        class="thread__messages"
      >
        <article
          v-for="(message, index) in group.messages"
          :key="message.id"
          class="thread__row"
          :class="{ 'thread__row--mine': message.is_mine }"
        >
          <Avatar
            v-if="showAvatar(index, group) && otherPartyAvatarUrl"
            :image="otherPartyAvatarUrl"
            :alt="otherPartyName"
            shape="circle"
            size="normal"
            class="thread__avatar"
          />
          <Avatar
            v-else-if="showAvatar(index, group)"
            :label="participantInitials(otherPartyName)"
            shape="circle"
            size="normal"
            class="thread__avatar thread__avatar--fallback"
          />
          <span
            v-else-if="!message.is_mine"
            class="thread__avatar-spacer"
            aria-hidden="true"
          />

          <div
            class="thread__bubble"
            :class="message.is_mine ? 'thread__bubble--out' : 'thread__bubble--in'"
          >
            <p class="thread__body">
              {{ message.body }}
            </p>
            <time
              v-if="message.created_at"
              class="thread__time"
              :datetime="message.created_at"
            >
              {{ formatMessageWhen(message.created_at) }}
            </time>
          </div>
        </article>
      </TransitionGroup>
    </section>
  </div>
</template>

<style scoped>
.thread {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.75rem 1rem 1rem;
}

.thread__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  margin: 2.5rem 0;
  text-align: center;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.thread__empty .pi {
  font-size: 1.75rem;
  opacity: 0.45;
}

.thread__divider {
  margin: 0.5rem 0 0.75rem;
}

.thread__day-label {
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--text-muted);
}

.thread__messages {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.thread__row {
  display: flex;
  align-items: flex-end;
  gap: 0.5rem;
  max-width: 100%;
}

.thread__row--mine {
  justify-content: flex-end;
}

.thread__avatar {
  flex-shrink: 0;
}

.thread__avatar--fallback {
  background: color-mix(in srgb, var(--p-primary-color) 14%, transparent);
  color: var(--p-primary-color);
  font-weight: 700;
}

.thread__avatar-spacer {
  width: 2.5rem;
  flex-shrink: 0;
}

.thread__bubble {
  max-width: min(78%, 22rem);
  padding: 0.6rem 0.8rem;
  box-shadow: 0 1px 2px rgb(15 20 25 / 6%);
}

.thread__bubble--in {
  border-radius: 1rem 1rem 1rem 0.3rem;
  background: var(--surface-muted);
  border: 1px solid var(--surface-border);
}

.thread__bubble--out {
  border-radius: 1rem 1rem 0.3rem 1rem;
  background: var(--p-primary-color);
  color: #fff;
}

.thread__body {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.5;
  white-space: pre-line;
}

.thread__time {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.625rem;
  opacity: 0.72;
  text-align: right;
}

.thread__bubble--out .thread__time {
  color: rgb(255 255 255 / 85%);
}

.thread-msg-enter-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.thread-msg-enter-from {
  opacity: 0;
  transform: translateY(0.35rem);
}

@media (prefers-reduced-motion: reduce) {
  .thread-msg-enter-active {
    transition: none;
  }
}
</style>