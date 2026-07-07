import { apiFetch } from './index'

export interface TrendTopic {
  id: number
  keyword: string
  geo: string
  volume: number | null
  volume_fmt: string | null
  growth_pct: string | null
  growth_fmt: string | null
  sparkline: number[] | null
  status: string | null
  category_id: number | null
  category_name: string | null
}

export interface DashboardResult {
  data: TrendTopic[]
  total: number
  page: number
  per_page: number
}

export interface Category {
  id: number
  name: string
}

export async function fetchDashboard(params: {
  category?: number
  geo?: string
  page?: number
  per_page?: number
}): Promise<DashboardResult> {
  const p: Record<string, string> = {}
  if (params.category) p.category = String(params.category)
  if (params.geo) p.geo = params.geo
  if (params.page) p.page = String(params.page)
  if (params.per_page) p.per_page = String(params.per_page)
  return apiFetch<DashboardResult>('/dashboard', p)
}

export async function fetchCategories(): Promise<Category[]> {
  return apiFetch<Category[]>('/dashboard/categories')
}
