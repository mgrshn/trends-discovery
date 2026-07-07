import { apiFetch } from '.'

export interface TrendPoint {
  date: number   // ms timestamp
  value: number  // 0–100
}

export interface RelatedQuery {
  query: string
  formatted: string | null  // "+250%" | "Breakout" | null
  value: number | null
}

export interface TopQuery {
  query: string
  value: number  // 0–100 index
}

export interface Region {
  geo_code: string
  name: string
  value: number  // 0–100
}

export interface AnalysisResult {
  keyword: string
  geo: string
  period: string
  engine: string
  cached: boolean
  points: TrendPoint[]
  related_rising: RelatedQuery[]
  related_top: TopQuery[]
  regions: Region[]
}

export function fetchAnalysis(
  keyword: string,
  geo: string,
  period: string,
  engine: string = 'web',
): Promise<AnalysisResult> {
  return apiFetch<AnalysisResult>('/analysis', { keyword, geo, period, engine })
}
