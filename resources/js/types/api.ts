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
  district: string | null
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
  district: string | null
  status: AdStatus
  rejection_reason: string | null
  contact_email: string | null
  /** Pełny numer widzi tylko autor i administrator (formularz edycji). */
  contact_phone?: string
  has_phone: boolean
  contact_phone_masked: string | null
  views_count: number
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
  price_min?: number
  price_max?: number
  negotiable?: boolean
  free?: boolean
  /** Listy rozdzielone przecinkami, np. "new,used". */
  condition?: string
  delivery?: string
  sort?: AdSort
  page?: number
}

/** SellerResource: publiczna wizytówka, bez adresu e-mail. */
export interface Seller {
  name: string
  member_since: number | null
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
  district: string
  contact_email: string
  contact_phone: string
  accept_terms: boolean
  images: File[]
  removed_image_ids: number[]
}
