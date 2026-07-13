import { client } from '@/api/client'
import type {
  Conversation,
  ConversationSummary,
  CursorPaginated,
  Message,
  Paginated,
  ResourceEnvelope,
} from '@/types/api'

const BASE = '/api/v1'

export async function fetchConversations(
  cursor?: string | null,
): Promise<CursorPaginated<ConversationSummary>> {
  const { data } = await client.get<CursorPaginated<ConversationSummary>>(`${BASE}/my/conversations`, {
    params: cursor ? { cursor } : undefined,
  })

  return data
}

export async function fetchUnreadCount(): Promise<number> {
  const { data } = await client.get<ResourceEnvelope<{ count: number }>>(
    `${BASE}/my/conversations/unread-count`,
  )

  return data.data.count
}

export async function fetchConversation(id: number): Promise<Conversation> {
  const { data } = await client.get<ResourceEnvelope<Conversation>>(`${BASE}/conversations/${id}`)

  return data.data
}

export async function fetchMessages(conversationId: number, page = 1): Promise<Paginated<Message>> {
  const { data } = await client.get<Paginated<Message>>(
    `${BASE}/conversations/${conversationId}/messages`,
    { params: { page } },
  )

  return data
}

export async function sendAdMessage(slug: string, body: string): Promise<Conversation> {
  const { data } = await client.post<ResourceEnvelope<Conversation>>(`${BASE}/ads/${slug}/messages`, {
    body,
  })

  return data.data
}

export async function replyToConversation(
  conversationId: number,
  body: string,
): Promise<Message> {
  const { data } = await client.post<ResourceEnvelope<Message>>(
    `${BASE}/conversations/${conversationId}/messages`,
    { body },
  )

  return data.data
}