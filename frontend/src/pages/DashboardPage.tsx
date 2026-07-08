import { useEffect, useState, useCallback } from 'react'
import TrendCard from '../components/TrendCard'
import type { TrendTopic, Category, DashboardSort } from '../api/dashboard'
import { fetchDashboard, fetchCategories } from '../api/dashboard'
import { GEO_LIST, GEO_LIST_COUNTRIES } from '../constants/geos'

type Mode = 'realtime' | 'longterm'

const PER_PAGE = 20

export default function DashboardPage() {
  const [topics, setTopics] = useState<TrendTopic[]>([])
  const [categories, setCategories] = useState<Category[]>([])
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null)
  const [mode, setMode] = useState<Mode>('longterm')
  const [geo, setGeo] = useState<string>('')
  const [sort, setSort] = useState<DashboardSort>('score')
  const [activeOnly, setActiveOnly] = useState(false)
  const [page, setPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

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
          mode: m,
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

  useEffect(() => {
    load(selectedCategory, page, mode, geo, sort, activeOnly)
  }, [selectedCategory, page, mode, geo, sort, activeOnly, load])

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
    // Realtime doesn't support Worldwide — reset to US when switching
    if (m === 'realtime' && geo === '') setGeo('US')
    if (m === 'longterm' && geo !== '') setGeo('')
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
      </div>

      {/* Category filter pills */}
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
    </div>
  )
}
