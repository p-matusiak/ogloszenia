/**
 * Mirrors the Laravel JsonResource payloads exactly. Any change to a Resource
 * in app/Http/Resources must be reflected here.
 */

/** Mirrors App\Enums\AdStatus. */
export type AdStatus = 'pending' | 'active' | 'rejected' | 'expired' | 'deleted'

/** Mirrors App\Enums\ReportStatus. */
export type ReportStatus = 'pending' | 'reviewed' | 'dismissed'

export type ReportReason = 'spam' | 'scam' | 'offensive' | 'wrong_category' | 'other'

export interface User {
  id: number
  name: string
  email: string
  bio: string | null
  avatar_url: string | null
  is_admin: boolean
  is_email_verified: boolean
  phone: string | null
  default_location: string | null
  default_latitude: number | null
  default_longitude: number | null
}

/** Mirrors App\Enums\EmailVerificationStatus; arrives as the `?status=` query. */
export type EmailVerificationStatus = 'verified' | 'already-verified' | 'invalid' | 'expired'

/** A node in the closure-table category tree. */
export interface Category {
  id: number
  parent_id: number | null
  name: string
  slug: string
  position: number
  is_visible: boolean
  children?: Category[]
  /** Nearest ancestor first, so the breadcrumb reads root-last. */
  ancestors?: Category[]
}

export interface AdImage {
  id: number
  url: string
  position: number
  is_primary: boolean
}

/** AdSummaryResource: the listing payload. */
export interface AdSummary {
  id: number
  title: string
  slug: string
  excerpt: string
  price: number | null
  is_negotiable: boolean
  condition: AdCondition | null
  delivery_methods: DeliveryMethod[]
  location: string | null
  latitude: number | null
  longitude: number | null
  status: AdStatus
  published_at: string | null
  expires_at: string | null
  views_count: number
  primary_image_url?: string | null
  category?: Category
}

/** AdResource: the detail payload. */
export interface Ad {
  id: number
  title: string
  slug: string
  description: string
  price: number | null
  is_negotiable: boolean
  condition: AdCondition | null
  delivery_methods: DeliveryMethod[]
  delivery_prices: Partial<Record<DeliveryMethod, string>>
  location: string | null
  latitude: number | null
  longitude: number | null
  status: AdStatus
  rejection_reason: string | null
  /** Nadpisanie numeru z profilu — widzi tylko autor i administrator. */
  contact_phone?: string
  uses_profile_phone?: boolean
  has_phone: boolean
  contact_phone_masked: string | null
  views_count: number
  is_own: boolean
  is_refreshable: boolean
  published_at: string | null
  expires_at: string | null
  created_at: string | null
  category?: Category
  seller?: Seller
  images?: AdImage[]
}

export interface AdReport {
  id: number
  reason: string
  message: string | null
  status: ReportStatus
  created_at: string | null
  ad?: AdSummary
  reporter?: User
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}

/** Laravel cursor paginator meta — brak total i last_page. */
export interface CursorPaginationMeta {
  path: string
  per_page: number
  next_cursor: string | null
  prev_cursor: string | null
}

export interface CursorPaginated<T> {
  data: T[]
  meta: CursorPaginationMeta
}

export interface ResourceEnvelope<T> {
  data: T
}

/** The shared error envelope produced by App\Exceptions\Domain\DomainException. */
export interface ApiErrorBody {
  code: string
  message: string
  details: Record<string, unknown>
}

/** Laravel's 422 shape, which differs from the domain-error envelope. */
export interface ValidationErrorBody {
  message: string
  errors: Record<string, string[]>
}

/** Mirrors App\Enums\AdSort. */
export type AdSort = 'relevance' | 'newest' | 'price_asc' | 'price_desc'

/** Mirrors App\Enums\AdCondition. */
export type AdCondition = 'new' | 'used' | 'damaged'

/** Mirrors App\Enums\DeliveryMethod. */
export type DeliveryMethod = 'personal' | 'courier' | 'parcel_locker' | 'post' | 'local'

export interface AdFilters {
  q?: string
  /**
   * Slug wybranego węzła drzewa, dowolnej głębokości — backend rozwija go na
   * całe poddrzewo przez closure table. Nie jedzie w query stringu: kategoria
   * ma własny adres `/kategoria/{slug}`.
   */
  category?: string
  location?: string
  /** Promień od punktu — wymaga lat, lng i radius_km razem. */
  lat?: number
  lng?: number
  radius_km?: number
  price_min?: number
  price_max?: number
  negotiable?: boolean
  free?: boolean
  /** Listy rozdzielone przecinkami, np. "new,used". */
  condition?: string
  delivery?: string
  sort?: AdSort
  /** Tylko z linku (strona sprzedawcy), nie z panelu filtrów. */
  seller?: string
  page?: number
}

/** SellerResource: publiczna wizytówka, bez adresu e-mail. */
export interface Seller {
  id: number
  slug: string
  name: string
  avatar_url: string | null
  member_since: number | null
}

/** SellerProfileResource: profil na stronie /sprzedawca/{id}. */
export interface SellerProfile {
  id: number
  slug: string
  name: string
  avatar_url: string | null
  bio: string | null
  member_since: number | null
  active_ads_count: number
}

/** MessageParticipantResource: publiczna wizytówka uczestnika wątku. */
export interface MessageParticipant {
  id: number
  name: string
  avatar_url: string | null
}

export interface Message {
  id: number
  body: string
  created_at: string | null
  is_mine: boolean
  sender?: MessageParticipant
}

export interface ConversationSummary {
  id: number
  ad?: AdSummary
  other_party?: MessageParticipant
  last_message_preview: string | null
  last_message_at: string | null
  is_unread: boolean
}

export interface Conversation {
  id: number
  ad?: AdSummary
  other_party?: MessageParticipant
  last_message_at: string | null
  is_unread: boolean
}

export interface AdCategorySuggestion {
  category_id: number | null
  available: boolean
}

export interface AdFormValues {
  title: string
  description: string
  category_id: number | null
  price: number | null
  is_negotiable: boolean
  condition: AdCondition | null
  delivery_methods: DeliveryMethod[]
  delivery_prices: Partial<Record<DeliveryMethod, string>>
  location: string
  latitude: number | null
  longitude: number | null
  use_custom_phone: boolean
  contact_phone: string
  accept_terms: boolean
  images: File[]
  removed_image_ids: number[]
}
