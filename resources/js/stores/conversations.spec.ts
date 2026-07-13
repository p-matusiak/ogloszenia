import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import * as conversationsApi from '@/api/modules/v1/conversations'
import { useConversationsStore } from '@/stores/conversations'

vi.mock('@/api/modules/v1/conversations', () => ({
  fetchConversations: vi.fn(),
  fetchUnreadCount: vi.fn(),
  fetchConversation: vi.fn(),
  fetchMessages: vi.fn(),
  sendAdMessage: vi.fn(),
  replyToConversation: vi.fn(),
}))

describe('useConversationsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('loads conversations into state', async () => {
    vi.mocked(conversationsApi.fetchConversations).mockResolvedValue({
      data: [
        {
          id: 1,
          last_message_preview: 'Cześć',
          last_message_at: '2026-07-13T10:00:00+00:00',
          is_unread: true,
        },
      ],
      meta: {
        path: '/api/v1/my/conversations',
        per_page: 20,
        next_cursor: null,
        prev_cursor: null,
      },
    })

    const store = useConversationsStore()
    await store.loadConversations()

    expect(store.conversations).toHaveLength(1)
    expect(store.hasMoreConversations).toBe(false)
  })

  it('appends older conversations when loading more', async () => {
    vi.mocked(conversationsApi.fetchConversations)
      .mockResolvedValueOnce({
        data: [{ id: 1, last_message_preview: 'A', last_message_at: null, is_unread: false }],
        meta: {
          path: '/api/v1/my/conversations',
          per_page: 20,
          next_cursor: 'cursor-abc',
          prev_cursor: null,
        },
      })
      .mockResolvedValueOnce({
        data: [{ id: 2, last_message_preview: 'B', last_message_at: null, is_unread: false }],
        meta: {
          path: '/api/v1/my/conversations',
          per_page: 20,
          next_cursor: null,
          prev_cursor: 'cursor-abc',
        },
      })

    const store = useConversationsStore()
    await store.loadConversations()
    await store.loadMoreConversations()

    expect(conversationsApi.fetchConversations).toHaveBeenLastCalledWith('cursor-abc')
    expect(store.conversations.map((item) => item.id)).toEqual([1, 2])
    expect(store.hasMoreConversations).toBe(false)
  })

  it('reverses messages chronologically when opening a thread', async () => {
    vi.mocked(conversationsApi.fetchConversation).mockResolvedValue({
      id: 2,
      last_message_at: '2026-07-13T11:00:00+00:00',
      is_unread: false,
    })
    vi.mocked(conversationsApi.fetchMessages).mockResolvedValue({
      data: [
        {
          id: 10,
          body: 'Nowsza',
          created_at: '2026-07-13T11:00:00+00:00',
          is_mine: false,
        },
        {
          id: 9,
          body: 'Starsza',
          created_at: '2026-07-13T10:00:00+00:00',
          is_mine: true,
        },
      ],
      meta: { current_page: 1, last_page: 1, per_page: 30, total: 2 },
    })
    vi.mocked(conversationsApi.fetchUnreadCount).mockResolvedValue(0)

    const store = useConversationsStore()
    await store.openConversation(2)

    expect(store.messages.map((message) => message.body)).toEqual(['Starsza', 'Nowsza'])
  })
})