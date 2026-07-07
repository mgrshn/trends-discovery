import { useState, useEffect, useCallback, useRef } from 'react'
import { useSearchParams } from 'react-router-dom'
import { fetchAnalysis, type AnalysisResult } from '../api/analysis'
import { GEO_LIST, PERIOD_OPTIONS, ENGINE_OPTIONS } from '../constants/geos'
import TrendChart from '../components/TrendChart'
import RelatedQueries from '../components/RelatedQueries'
import RegionsList from '../components/RegionsList'

// ── Icons ────────────────────────────────────────────────────────────────────

function SearchIcon() {
  return (
    <svg className="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <circle cx="11" cy="11" r="8" />
      <path strokeLinecap="round" d="m21 21-4.35-4.35" />
    </svg>
  )
}

function GlobeIcon() {
  return (
    <svg className="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <circle cx="12" cy="12" r="10" />
      <path strokeLinecap="round" d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
    </svg>
  )
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function Skeleton({ className = '' }: { className?: string }) {
  return <div className={`animate-pulse bg-slate-100 rounded-lg ${className}`} />
}

function LoadingSkeleton() {
  return (
    <div className="space-y-6">
      <div className="bg-white rounded-2xl border border-slate-100 p-6 space-y-4">
        <div className="flex items-start justify-between">
          <div className="space-y-2">
            <Skeleton className="h-8 w-48" />
            <Skeleton className="h-4 w-32" />
          </div>
          <Skeleton className="h-8 w-24 rounded-full" />
        </div>
        <Skeleton className="h-64 w-full" />
      </div>
      <div className="grid grid-cols-2 gap-6">
        <div className="bg-white rounded-2xl border border-slate-100 p-6 space-y-3">
          <Skeleton className="h-4 w-20" />
          {[...Array(6)].map((_, i) => <Skeleton key={i} className="h-9 w-full" />)}
        </div>
        <div className="bg-white rounded-2xl border border-slate-100 p-6 space-y-3">
          <Skeleton className="h-4 w-20" />
          {[...Array(6)].map((_, i) => <Skeleton key={i} className="h-9 w-full" />)}
        </div>
      </div>
    </div>
  )
}

// ── Main ──────────────────────────────────────────────────────────────────────

export default function TrendAnalysisPage() {
  const [searchParams, setSearchParams] = useSearchParams()

  const [input, setInput] = useState(searchParams.get('keyword') ?? '')
  const [geo, setGeo] = useState(searchParams.get('geo') ?? '')
  const [period, setPeriod] = useState(searchParams.get('period') ?? '12m')
  const [engine, setEngine] = useState(searchParams.get('engine') ?? 'web')

  const [result, setResult] = useState<AnalysisResult | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const abortRef = useRef<AbortController | null>(null)

  const search = useCallback(
    async (keyword: string, geoCode: string, periodVal: string, engineVal: string) => {
      if (!keyword.trim()) return

      abortRef.current?.abort()
      abortRef.current = new AbortController()

      setLoading(true)
      setError(null)

      setSearchParams({ keyword, geo: geoCode, period: periodVal, engine: engineVal }, { replace: true })

      try {
        const data = await fetchAnalysis(keyword.trim(), geoCode, periodVal, engineVal)
        setResult(data)
      } catch (e: unknown) {
        if ((e as Error).name !== 'AbortError') {
          setError((e as Error).message ?? 'Something went wrong')
        }
      } finally {
        setLoading(false)
      }
    },
    [setSearchParams],
  )

  // Auto-search on mount if keyword in URL
  useEffect(() => {
    const kw = searchParams.get('keyword')
    if (kw) search(kw, geo, period, engine)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    search(input, geo, period, engine)
  }

  function handleGeoChange(newGeo: string) {
    setGeo(newGeo)
    if (result) search(result.keyword, newGeo, period, engine)
  }

  function handlePeriodChange(newPeriod: string) {
    setPeriod(newPeriod)
    if (result) search(result.keyword, geo, newPeriod, engine)
  }

  function handleEngineChange(newEngine: string) {
    setEngine(newEngine)
    if (result) search(result.keyword, geo, period, newEngine)
  }

  function handleRelatedClick(keyword: string) {
    setInput(keyword)
    search(keyword, geo, period, engine)
  }

  const geoName = GEO_LIST.find((g) => g.code === geo)?.name ?? 'Worldwide'

  return (
    <div className="min-h-full bg-slate-50">
      {/* ── Header ── */}
      <div className="bg-white border-b border-slate-100 px-8 py-6">
        <h1 className="text-2xl font-bold text-slate-900 tracking-tight mb-0.5">Trend Analysis</h1>
        <p className="text-sm text-slate-500">Get in-depth trend data on any topic</p>
      </div>

      <div className="px-8 py-6 max-w-5xl">
        {/* ── Search bar ── */}
        <form onSubmit={handleSubmit} className="flex gap-2 mb-6">
          <div className="relative flex-1">
            <span className="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none">
              <SearchIcon />
            </span>
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder="Search for any topic"
              className="w-full pl-10 pr-4 py-2.5 text-sm bg-white border border-slate-200 rounded-xl
                         text-slate-900 placeholder-slate-400
                         focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                         transition-shadow"
            />
          </div>

          {/* Geo selector */}
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
              <GlobeIcon />
            </span>
            <select
              value={geo}
              onChange={(e) => handleGeoChange(e.target.value)}
              className="pl-9 pr-8 py-2.5 text-sm bg-white border border-slate-200 rounded-xl
                         text-slate-700 appearance-none cursor-pointer
                         focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                         transition-shadow"
            >
              {GEO_LIST.map((g) => (
                <option key={g.code} value={g.code}>{g.name}</option>
              ))}
            </select>
          </div>

          {/* Engine selector */}
          <select
            value={engine}
            onChange={(e) => handleEngineChange(e.target.value)}
            className="px-3 py-2.5 text-sm bg-white border border-slate-200 rounded-xl
                       text-slate-700 appearance-none cursor-pointer
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                       transition-shadow"
          >
            {ENGINE_OPTIONS.map((e) => (
              <option key={e.value} value={e.value}>{e.label}</option>
            ))}
          </select>

          <button
            type="submit"
            disabled={!input.trim() || loading}
            className="px-5 py-2.5 text-sm font-semibold bg-indigo-600 text-white rounded-xl
                       hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                       transition-colors"
          >
            {loading ? 'Searching…' : 'Analyze'}
          </button>
        </form>

        {/* ── States ── */}
        {error && (
          <div className="mb-6 px-4 py-3 bg-red-50 border border-red-100 rounded-xl text-sm text-red-600">
            {error}
          </div>
        )}

        {loading && <LoadingSkeleton />}

        {!loading && !result && !error && <EmptyState />}

        {!loading && result && (
          <div className="space-y-4">
            {/* ── Chart card ── */}
            <div className="bg-white rounded-2xl border border-slate-100 p-6">
              {/* Topic header */}
              <div className="flex items-start justify-between mb-5">
                <div>
                  <h2 className="text-xl font-bold text-slate-900 tracking-tight capitalize">
                    {result.keyword}
                  </h2>
                  <p className="text-sm text-slate-400 mt-0.5">{geoName}</p>
                </div>
              </div>

              {/* Period tabs */}
              <div className="flex items-center gap-1 mb-5">
                {PERIOD_OPTIONS.map((p) => (
                  <button
                    key={p.value}
                    onClick={() => handlePeriodChange(p.value)}
                    className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
                      period === p.value
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700'
                    }`}
                  >
                    {p.label}
                  </button>
                ))}
              </div>

              {/* Chart */}
              <TrendChart points={result.points} period={period} />
            </div>

            {/* ── Related queries ── */}
            {(result.related_rising.length > 0 || result.related_top.length > 0) && (
              <div className="bg-white rounded-2xl border border-slate-100 p-6">
                <h3 className="text-sm font-semibold text-slate-900 mb-4">Related Queries</h3>
                <RelatedQueries
                  rising={result.related_rising}
                  top={result.related_top}
                  onClickQuery={handleRelatedClick}
                />
              </div>
            )}

            {/* ── Regions ── */}
            {result.regions.length > 0 && (
              <div className="bg-white rounded-2xl border border-slate-100 p-6">
                <RegionsList regions={result.regions} />
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  )
}

function EmptyState() {
  return (
    <div className="flex flex-col items-center justify-center py-24 text-center">
      <div className="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center mb-4">
        <svg className="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
          <path strokeLinecap="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
        </svg>
      </div>
      <p className="text-slate-500 text-sm max-w-xs">
        Search a topic above to see its trend chart, related queries, and regional interest.
      </p>
    </div>
  )
}
