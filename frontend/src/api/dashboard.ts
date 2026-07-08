import { apiFetch } from './index'

export type TopicStatus = 'exploding' | 'regular' | 'peaked' | 'noise' | null

export interface TrendTopic {
  id: number
  keyword: string
  geo: string
  volume: number | null
  volume_fmt: string | null
  growth_pct: string | null
  growth_fmt: string | null
  growth_3m: number | null
  growth_6m: number | null
  growth_12m: number | null
  sparkline: number[] | null
  status: TopicStatus
  score: number | null
  category_id: number | null
  category_name: string | null
  updated_at: string | null
  last_scored_at: string | null
}

export interface DashboardResult {
  data: TrendTopic[]
  total: number
  page: number
  per_page: number
  mode: 'realtime' | 'longterm'
}

export interface Category {
  id: number
  name: string
}

export type DashboardSort = 'score' | 'volume' | 'growth' | 'recency' | 'title'

export async function fetchDashboard(params: {
  category?: number
  geo?: string
  mode?: 'realtime' | 'longterm'
  sort?: DashboardSort
  active_only?: boolean
  page?: number
  per_page?: number
}): Promise<DashboardResult> {
  const p: Record<string, string> = {}
  if (params.category) p.category = String(params.category)
  if (params.geo) p.geo = params.geo
  if (params.mode) p.mode = params.mode
  if (params.sort) p.sort = params.sort
  if (params.active_only) p.active_only = '1'
  if (params.page) p.page = String(params.page)
  if (params.per_page) p.per_page = String(params.per_page)
  return apiFetch<DashboardResult>('/dashboard', p)
}

export async function fetchCategories(): Promise<Category[]> {
  return apiFetch<Category[]>('/dashboard/categories')
}

export interface LiveTopic {
  keyword: string
  geo: string
  volume: number | null
  volume_fmt: string | null
  growth_pct: number | null
  growth_fmt: string | null
  breakdown: string[]
}

export interface LiveResult {
  data: LiveTopic[]
  total: number
  geo: string
  mode: 'live'
  fetched_at: string
  cached: boolean
  error?: string
  message?: string
}

export type LiveSort = 'volume' | 'growth' | 'title'

export async function fetchLive(params: { geo: string; sort?: LiveSort }): Promise<LiveResult> {
  const p: Record<string, string> = { geo: params.geo }
  if (params.sort) p.sort = params.sort
  return apiFetch<LiveResult>('/dashboard/live', p)
}
