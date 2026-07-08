import { useEffect, useState, useCallback } from 'react'
import TrendCard from '../components/TrendCard'
import LiveCard from '../components/LiveCard'
import type { TrendTopic, Category, DashboardSort, LiveTopic, LiveSort } from '../api/dashboard'
import { fetchDashboard, fetchCategories, fetchLive } from '../api/dashboard'
import { GEO_LIST, GEO_LIST_COUNTRIES } from '../constants/geos'

type Mode = 'realtime' | 'longterm' | 'live'

const PER_PAGE = 20

function fmtAgo(iso: string | null): string {
  if (!iso) return ''
  const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000)
  if (diff < 60)   return 'just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  return `${Math.floor(diff / 3600)}h ago`
}

export default function DashboardPage() {
  // Shared state
  const [mode, setMode] = useState<Mode>('longterm')
  const [geo, setGeo] = useState<string>('')
  const [categories, setCategories] = useState<Category[]>([])

  // Realtime / Longterm state
  const [topics, setTopics] = useState<TrendTopic[]>([])
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null)
  const [sort, setSort] = useState<DashboardSort>('score')
  const [activeOnly, setActiveOnly] = useState(false)
  const [page, setPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // Live state
  const [liveTopics, setLiveTopics] = useState<LiveTopic[]>([])
  const [liveFetchedAt, setLiveFetchedAt] = useState<string | null>(null)
  const [liveCached, setLiveCached] = useState(false)
  const [liveLoading, setLiveLoading] = useState(false)
  const [liveError, setLiveError] = useState<string | null>(null)
  const [liveDisabled, setLiveDisabled] = useState(false)
  const [liveSort, setLiveSort] = useState<LiveSort>('volume')

  useEffect(() => {
    fetchCategories().then(setCategories).catch(() => {})
  }, [])

  const load = useCallback(
    async (cat: number | null, pg: number, m: Mode, g: string, s: DashboardSort, ao: boolean) => {
      setLoading(true)
      setError(null)
      try {
        const result = await fetchDashboard({
          category: cat ?? undefined,
          geo: g || undefined,
          mode: m as 'realtime' | 'longterm',
          sort: s,
          active_only: ao || undefined,
          page: pg,
          per_page: PER_PAGE,
        })
        setTopics(result.data)
        setTotal(result.total)
      } catch (e: unknown) {
        setError(e instanceof Error ? e.message : 'Failed to load')
      } finally {
        setLoading(false)
      }
    },
    [],
  )

  const loadLive = useCallback(async (g: string, s: LiveSort) => {
    setLiveLoading(true)
    setLiveError(null)
    setLiveDisabled(false)
    try {
      const result = await fetchLive({ geo: g || 'US', sort: s })
      setLiveTopics(result.data)
      setLiveFetchedAt(result.fetched_at)
      setLiveCached(result.cached)
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : 'Failed to load'
      if (msg.includes('403') || msg.includes('disabled')) {
        setLiveDisabled(true)
      } else {
        setLiveError(msg)
      }
    } finally {
      setLiveLoading(false)
    }
  }, [])

  useEffect(() => {
    if (mode === 'live') {
      loadLive(geo || 'US', liveSort)
    } else {
      load(selectedCategory, page, mode, geo, sort, activeOnly)
    }
  }, [selectedCategory, page, mode, geo, sort, activeOnly, liveSort, load, loadLive])

  function selectCategory(id: number | null) {
    setSelectedCategory(id)
    setPage(1)
  }

  function selectMode(m: Mode) {
    setMode(m)
    setPage(1)
    setSelectedCategory(null)
    setSort('score')
    setActiveOnly(false)
    if (m === 'live') {
      if (geo === '') setGeo('US')
    } else if (m === 'realtime') {
      if (geo === '') setGeo('US')
    } else {
      // longterm supports Worldwide
      setGeo('')
    }
  }

  function selectGeo(g: string) {
    setGeo(g)
    setPage(1)
  }

  const totalPages = Math.ceil(total / PER_PAGE)

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Recommended Trends</h1>
          <p className="text-gray-500 mt-1">Trending topics discovered from Google Trends</p>
        </div>

        {/* Mode toggle */}
        <div className="flex items-center gap-1 bg-gray-100 p-1 rounded-xl">
          <button
            onClick={() => selectMode('realtime')}
            className={`px-4 py-1.5 rounded-lg text-sm font-medium transition-colors ${
              mode === 'realtime'
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-500 hover:text-gray-700'
            }`}
          >
            Real-time
          </button>
          <button
            onClick={() => selectMode('longterm')}
            className={`px-4 py-1.5 rounded-lg text-sm font-medium transition-colors ${
              mode === 'longterm'
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-500 hover:text-gray-700'
            }`}
          >
            Long-term
          </button>
          <button
            onClick={() => selectMode('live')}
            className={`flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-sm font-medium transition-colors ${
              mode === 'live'
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-500 hover:text-gray-700'
            }`}
          >
            <span className="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse" />
            Live
          </button>
        </div>
      </div>

      {/* Filters row */}
      <div className="flex flex-wrap items-center gap-3 mb-4">
        <div className="flex items-center gap-2">
          <span className="text-sm text-gray-500 shrink-0">Country:</span>
          <select
            value={geo}
            onChange={(e) => selectGeo(e.target.value)}
            className="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
          >
            {(mode === 'longterm' ? GEO_LIST : GEO_LIST_COUNTRIES).map((g) => (
              <option key={g.code} value={g.code}>{g.name}</option>
            ))}
          </select>
        </div>

        {mode !== 'live' && (
          <>
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-500 shrink-0">Sort:</span>
              <select
                value={sort}
                onChange={(e) => { setSort(e.target.value as DashboardSort); setPage(1) }}
                className="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
              >
                <option value="score">Relevance</option>
                <option value="volume">Search Volume</option>
                <option value="growth">Growth</option>
                <option value="recency">Recency</option>
                <option value="title">Title</option>
              </select>
            </div>

            <button
              onClick={() => { setActiveOnly(!activeOnly); setPage(1) }}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors ${
                activeOnly
                  ? 'bg-emerald-50 border-emerald-300 text-emerald-700'
                  : 'bg-white border-gray-200 text-gray-500 hover:text-gray-700'
              }`}
            >
              <span className={`w-2 h-2 rounded-full ${activeOnly ? 'bg-emerald-500' : 'bg-gray-300'}`} />
              Active only
            </button>
          </>
        )}

        {mode === 'live' && (
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500 shrink-0">Sort:</span>
            <select
              value={liveSort}
              onChange={(e) => setLiveSort(e.target.value as LiveSort)}
              className="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700
                         focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
            >
              <option value="volume">Search Volume</option>
              <option value="growth">Growth</option>
              <option value="title">Title</option>
            </select>
          </div>
        )}

        {mode === 'live' && liveFetchedAt && (
          <span className="text-xs text-gray-400 flex items-center gap-1">
            {liveCached
              ? <>🕐 cached · fetched {fmtAgo(liveFetchedAt)}</>
              : <>🟢 fresh · fetched {fmtAgo(liveFetchedAt)}</>
            }
          </span>
        )}
      </div>

      {/* Category pills — not shown in live mode */}
      {mode !== 'live' && (
        <div className="flex flex-wrap gap-2 mb-6">
          <button
            onClick={() => selectCategory(null)}
            className={`px-4 py-1.5 rounded-full text-sm font-medium transition-colors ${
              selectedCategory === null
                ? 'bg-indigo-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            All
          </button>
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => selectCategory(cat.id)}
              className={`px-4 py-1.5 rounded-full text-sm font-medium transition-colors ${
                selectedCategory === cat.id
                  ? 'bg-indigo-600 text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              {cat.name}
            </button>
          ))}
        </div>
      )}

      {/* ── Live mode ──────────────────────────────────────────────────────────── */}
      {mode === 'live' && (
        <>
          {liveDisabled ? (
            <div className="flex flex-col items-center justify-center py-24 text-center">
              <div className="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                <svg className="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
              </div>
              <p className="text-gray-600 text-sm font-medium">Live mode is disabled</p>
              <p className="text-gray-400 text-xs mt-1">
                Enable it in Admin → Parser Settings → Live mode.
              </p>
            </div>
          ) : liveError ? (
            <div className="rounded-lg bg-red-50 border border-red-100 p-4 text-red-600 mb-6">
              {liveError}
            </div>
          ) : liveLoading ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              {Array.from({ length: 8 }).map((_, i) => (
                <div key={i} className="bg-white rounded-xl border border-gray-100 p-5 h-40 animate-pulse">
                  <div className="h-4 bg-gray-100 rounded w-3/4 mb-3" />
                  <div className="flex gap-1 mb-3">
                    {Array.from({ length: 3 }).map((_, j) => (
                      <div key={j} className="h-5 bg-gray-100 rounded w-16" />
                    ))}
                  </div>
                  <div className="flex gap-4 mt-auto">
                    <div className="h-8 bg-gray-100 rounded w-16" />
                    <div className="h-8 bg-gray-100 rounded w-16" />
                  </div>
                </div>
              ))}
            </div>
          ) : liveTopics.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-24 text-center">
              <p className="text-gray-500 text-sm">No live trends available for this country.</p>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                {liveTopics.map((topic, i) => (
                  <LiveCard key={i} topic={topic} />
                ))}
              </div>
              <div className="mt-4 text-center text-xs text-gray-400">
                {liveTopics.length} live trending topics
              </div>
            </>
          )}
        </>
      )}

      {/* ── Realtime / Longterm mode ──────────────────────────────────────────── */}
      {mode !== 'live' && (
        <>
          {error && (
            <div className="rounded-lg bg-red-50 border border-red-100 p-4 text-red-600 mb-6">
              {error}
            </div>
          )}

          {loading ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              {Array.from({ length: 8 }).map((_, i) => (
                <div key={i} className="bg-white rounded-xl border border-gray-100 p-5 h-48 animate-pulse">
                  <div className="h-4 bg-gray-100 rounded w-3/4 mb-3" />
                  <div className="h-15 bg-gray-100 rounded mb-3" />
                  <div className="flex gap-4">
                    <div className="h-8 bg-gray-100 rounded w-16" />
                    <div className="h-8 bg-gray-100 rounded w-16" />
                  </div>
                </div>
              ))}
            </div>
          ) : topics.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-24 text-center">
              <div className="w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                <svg className="w-8 h-8 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
              {mode === 'longterm' ? (
                <>
                  <p className="text-gray-600 text-sm font-medium">No scored trends yet</p>
                  <p className="text-gray-400 text-xs mt-1 max-w-xs">
                    Scoring runs hourly. Topics need a 12-month series to be evaluated.
                  </p>
                </>
              ) : (
                <>
                  <p className="text-gray-500 text-sm">No trending topics yet.</p>
                  <p className="text-gray-400 text-xs mt-1">The ingestion job runs every 30 minutes.</p>
                </>
              )}
            </div>
          ) : (
            <>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                {topics.map((topic) => (
                  <TrendCard key={topic.id} topic={topic} />
                ))}
              </div>

              {totalPages > 1 && (
                <div className="flex items-center justify-center gap-2 mt-8">
                  <button
                    disabled={page <= 1}
                    onClick={() => setPage(page - 1)}
                    className="px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                  >
                    Previous
                  </button>
                  <span className="text-sm text-gray-500">
                    Page {page} of {totalPages}
                  </span>
                  <button
                    disabled={page >= totalPages}
                    onClick={() => setPage(page + 1)}
                    className="px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                  >
                    Next
                  </button>
                </div>
              )}

              <div className="mt-4 text-center text-xs text-gray-400">
                {total} trending topics found
              </div>
            </>
          )}
        </>
      )}
    </div>
  )
}
