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

export async function fetchDashboard(params: {
  category?: number
  geo?: string
  mode?: 'realtime' | 'longterm'
  page?: number
  per_page?: number
}): Promise<DashboardResult> {
  const p: Record<string, string> = {}
  if (params.category) p.category = String(params.category)
  if (params.geo) p.geo = params.geo
  if (params.mode) p.mode = params.mode
  if (params.page) p.page = String(params.page)
  if (params.per_page) p.per_page = String(params.per_page)
  return apiFetch<DashboardResult>('/dashboard', p)
}

export async function fetchCategories(): Promise<Category[]> {
  return apiFetch<Category[]>('/dashboard/categories')
}
