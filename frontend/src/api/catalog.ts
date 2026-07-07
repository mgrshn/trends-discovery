import { apiFetch } from './index'
import type { TopicStatus } from './dashboard'

export interface CatalogTopic {
  id: number
  keyword: string
  geo: string
  source: string
  volume: number | null
  volume_fmt: string | null
  growth_pct: number | null
  growth_12m: number | null
  growth_fmt: string
  sparkline: number[] | null
  status: TopicStatus
  score: number | null
  category_id: number | null
  category_name: string | null
}

export interface CatalogResult {
  data: CatalogTopic[]
  total: number
  page: number
  per_page: number
}

export interface CategoryStat {
  id: number
  name: string
  total: number
}

export type CatalogSort = 'growth' | 'volume' | 'newest'

export async function fetchCatalog(params: {
  q?: string
  category?: number
  status?: string
  sort?: CatalogSort
  page?: number
  per_page?: number
}): Promise<CatalogResult> {
  const p: Record<string, string> = {}
  if (params.q)        p.q        = params.q
  if (params.category) p.category = String(params.category)
  if (params.status)   p.status   = params.status
  if (params.sort)     p.sort     = params.sort
  if (params.page)     p.page     = String(params.page)
  if (params.per_page) p.per_page = String(params.per_page)
  return apiFetch<CatalogResult>('/catalog', p)
}

export async function fetchCatalogCategories(): Promise<CategoryStat[]> {
  return apiFetch<CategoryStat[]>('/catalog/categories')
}
