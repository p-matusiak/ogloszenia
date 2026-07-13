import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { errorMessage } from '@/api/client'
import * as conversationsApi from '@/api/modules/v1/conversations'
import type {
  Conversation,
  ConversationSummary,
  CursorPaginationMeta,
  Message,
  PaginationMeta,
} from '@/types/api'

export const useConversationsStore = defineStore('conversations', () => {
  const conversations = ref<ConversationSummary[]>([])
  const listMeta = ref<CursorPaginationMeta | null>(null)
  const unreadCount = ref(0)
  const activeConversation = ref<Conversation | null>(null)
  const messages = ref<Message[]>([])
  const messagesMeta = ref<PaginationMeta | null>(null)
  const isLoadingList = ref(false)
  const isLoadingMore = ref(false)
  const isLoadingThread = ref(false)
  const isSending = ref(false)
  const error = ref<string | null>(null)

  const hasMoreConversations = computed(() => listMeta.value?.next_cursor !== null)

  async function loadConversations(): Promise<void> {
    isLoadingList.value = true
    error.value = null
    try {
      const result = await conversationsApi.fetchConversations()
      conversations.value = result.data
      listMeta.value = result.meta
    } catch (caught: unknown) {
      error.value = errorMessage(caught, 'Nie udało się pobrać wiadomości.')
      conversations.value = []
      listMeta.value = null
    } finally {
      isLoadingList.value = false
    }
  }

  async function loadMoreConversations(): Promise<void> {
    const cursor = listMeta.value?.next_cursor
    if (!cursor || isLoadingMore.value) {
      return
    }

    isLoadingMore.value = true
    try {
      const result = await conversationsApi.fetchConversations(cursor)
      conversations.value = [...conversations.value, ...result.data]
      listMeta.value = result.meta
    } catch (caught: unknown) {
      error.value = errorMessage(caught, 'Nie udało się pobrać kolejnych rozmów.')
    } finally {
      isLoadingMore.value = false
    }
  }

  async function refreshUnreadCount(): Promise<void> {
    try {
      unreadCount.value = await conversationsApi.fetchUnreadCount()
    } catch {
      unreadCount.value = 0
    }
  }

  async function openConversation(id: number, page = 1): Promise<void> {
    isLoadingThread.value = true
    error.value = null
    try {
      const [conversation, pageResult] = await Promise.all([
        conversationsApi.fetchConversation(id),
        conversationsApi.fetchMessages(id, page),
      ])
      activeConversation.value = conversation
      messages.value = [...pageResult.data].reverse()
      messagesMeta.value = pageResult.meta
      await refreshUnreadCount()
      const summary = conversations.value.find((item) => item.id === id)
      if (summary) {
        summary.is_unread = false
      }
    } catch (caught: unknown) {
      error.value = errorMessage(caught, 'Nie udało się otworzyć rozmowy.')
      activeConversation.value = null
      messages.value = []
      messagesMeta.value = null
    } finally {
      isLoadingThread.value = false
    }
  }

  async function sendReply(body: string): Promise<boolean> {
    const conversation = activeConversation.value
    if (!conversation) {
      return false
    }

    isSending.value = true
    try {
      const message = await conversationsApi.replyToConversation(conversation.id, body)
      messages.value = [...messages.value, message]
      return true
    } catch (caught: unknown) {
      error.value = errorMessage(caught, 'Nie udało się wysłać wiadomości.')
      return false
    } finally {
      isSending.value = false
    }
  }

  async function startFromAd(slug: string, body: string): Promise<Conversation | null> {
    isSending.value = true
    error.value = null
    try {
      return await conversationsApi.sendAdMessage(slug, body)
    } catch (caught: unknown) {
      error.value = errorMessage(caught, 'Nie udało się wysłać wiadomości.')
      return null
    } finally {
      isSending.value = false
    }
  }

  function reset(): void {
    conversations.value = []
    listMeta.value = null
    unreadCount.value = 0
    activeConversation.value = null
    messages.value = []
    messagesMeta.value = null
    error.value = null
  }

  return {
    conversations,
    listMeta,
    hasMoreConversations,
    unreadCount,
    activeConversation,
    messages,
    messagesMeta,
    isLoadingList,
    isLoadingMore,
    isLoadingThread,
    isSending,
    error,
    loadConversations,
    loadMoreConversations,
    refreshUnreadCount,
    openConversation,
    sendReply,
    startFromAd,
    reset,
  }
})