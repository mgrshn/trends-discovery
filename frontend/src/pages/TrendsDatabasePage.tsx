import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import SparklineChart from '../components/SparklineChart'
import type { CatalogSort, CatalogTopic, CategoryStat } from '../api/catalog'
import { fetchCatalog, fetchCatalogCategories } from '../api/catalog'
import { useTracking } from '../context/TrackingContext'

const PER_PAGE = 24

const STATUS_OPTIONS: { label: string; value: string }[] = [
  { label: 'All statuses', value: '' },
  { label: 'Exploding',    value: 'exploding' },
  { label: 'Regular',      value: 'regular' },
  { label: 'Peaked',       value: 'peaked' },
]

const SORT_OPTIONS: { label: string; value: CatalogSort }[] = [
  { label: 'Growth',   value: 'growth' },
  { label: 'Volume',   value: 'volume' },
  { label: 'Newest',   value: 'newest' },
]

const STATUS_BADGE: Record<string, string> = {
  exploding: 'bg-amber-50 text-amber-700 border-amber-200',
  regular:   'bg-emerald-50 text-emerald-700 border-emerald-200',
  peaked:    'bg-gray-100 text-gray-500 border-gray-200',
}

function CatalogCard({ topic }: { topic: CatalogTopic }) {
  const navigate = useNavigate()
  const { trackTopic } = useTracking()
  const [tracking, setTracking] = useState(false)
  const [tracked, setTracked]   = useState(false)

  function goAnalyze() {
    navigate(`/analysis?keyword=${encodeURIComponent(topic.keyword)}&geo=${topic.geo}`)
  }

  async function handleTrack(e: React.MouseEvent) {
    e.stopPropagation()
    if (tracking || tracked) return
    setTracking(true)
    await trackTopic(topic.id).catch(() => {})
    setTracking(false)
    setTracked(true)
  }

  const growthColor = topic.growth_fmt?.startsWith('-') ? 'text-red-500' : 'text-emerald-600'

  return (
    <div className="bg-white rounded-xl border border-gray-100 p-5 flex flex-col gap-3 hover:shadow-md transition-shadow cursor-pointer" onClick={goAnalyze}>
      <div className="flex items-start justify-between gap-2">
        <span className="font-semibold text-gray-900 leading-tight line-clamp-2 text-sm">{topic.keyword}</span>
        {topic.status && topic.status !== 'noise' && (
          <span className={`shrink-0 text-xs font-medium border rounded-full px-2 py-0.5 ${STATUS_BADGE[topic.status] ?? ''}`}>
            {topic.status.charAt(0).toUpperCase() + topic.status.slice(1)}
          </span>
        )}
      </div>

      <div className="h-12">
        {topic.sparkline && topic.sparkline.length > 0 ? (
          <SparklineChart data={topic.sparkline} />
        ) : (
          <div className="h-12 bg-gray-50 rounded" />
        )}
      </div>

      <div className="flex items-center justify-between">
        <div className="flex gap-3">
          {topic.volume_fmt && (
            <div>
              <div className="text-xs text-gray-400 uppercase tracking-wide leading-none mb-0.5">Volume</div>
              <div className="text-sm font-bold text-gray-900">{topic.volume_fmt}</div>
            </div>
          )}
          {topic.growth_fmt && (
            <div>
              <div className="text-xs text-gray-400 uppercase tracking-wide leading-none mb-0.5">Growth</div>
              <div className={`text-sm font-bold ${growthColor}`}>{topic.growth_fmt}</div>
            </div>
          )}
        </div>
        <button
          onClick={handleTrack}
          className={`shrink-0 text-xs font-semibold border rounded-lg px-2 py-1 transition-colors ${
            tracked
              ? 'text-emerald-600 border-emerald-200 bg-emerald-50'
              : 'text-indigo-600 border-indigo-200 hover:bg-indigo-50'
          }`}
        >
          {tracked ? '✓' : tracking ? '…' : '+'}
        </button>
      </div>
    </div>
  )
}

function CategoryCard({ cat, onClick }: { cat: CategoryStat; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className="bg-white rounded-xl border border-gray-100 p-5 text-left hover:shadow-md hover:border-indigo-200 transition-all"
    >
      <div className="font-semibold text-gray-900 text-sm mb-1">{cat.name}</div>
      <div className="text-2xl font-bold text-indigo-600">{cat.total.toLocaleString()}</div>
      <div className="text-xs text-gray-400 mt-0.5">topics</div>
    </button>
  )
}

export default function TrendsDatabasePage() {
  const [query, setQuery]               = useState('')
  const [debouncedQuery, setDebounced]  = useState('')
  const [categories, setCategories]     = useState<CategoryStat[]>([])
  const [selectedCategory, setCategory] = useState<number | null>(null)
  const [status, setStatus]             = useState('')
  const [sort, setSort]                 = useState<CatalogSort>('growth')
  const [topics, setTopics]             = useState<CatalogTopic[]>([])
  const [total, setTotal]               = useState(0)
  const [page, setPage]                 = useState(1)
  const [loading, setLoading]           = useState(false)
  const [error, setError]               = useState<string | null>(null)
  const debounceRef                     = useRef<ReturnType<typeof setTimeout> | null>(null)

  useEffect(() => {
    fetchCatalogCategories().then(setCategories).catch(() => {})
  }, [])

  // Debounce search input
  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current)
    debounceRef.current = setTimeout(() => setDebounced(query), 350)
    return () => { if (debounceRef.current) clearTimeout(debounceRef.current) }
  }, [query])

  const load = useCallback(async (q: string, cat: number | null, st: string, sr: CatalogSort, pg: number) => {
    setLoading(true)
    setError(null)
    try {
      const result = await fetchCatalog({
        q:        q || undefined,
        category: cat ?? undefined,
        status:   st || undefined,
        sort:     sr,
        page:     pg,
        per_page: PER_PAGE,
      })
      setTopics(result.data)
      setTotal(result.total)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Failed to load')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load(debouncedQuery, selectedCategory, status, sort, page)
  }, [debouncedQuery, selectedCategory, status, sort, page, load])

  function selectCategory(id: number | null) {
    setCategory(id)
    setPage(1)
  }

  function changeSort(s: CatalogSort) {
    setSort(s)
    setPage(1)
  }

  function changeStatus(s: string) {
    setStatus(s)
    setPage(1)
  }

  function handleSearch(e: React.ChangeEvent<HTMLInputElement>) {
    setQuery(e.target.value)
    setPage(1)
  }

  const totalPages = Math.ceil(total / PER_PAGE)
  const showCategoryGrid = !debouncedQuery && selectedCategory === null && categories.length > 0

  return (
    <div className="p-8">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Trends Database</h1>
        <p className="text-gray-500 mt-1">
          {total > 0 ? `${total.toLocaleString()} trends discovered` : 'Searchable catalog of all discovered trends'}
        </p>
      </div>

      {/* Search bar */}
      <div className="relative mb-6">
        <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          value={query}
          onChange={handleSearch}
          placeholder="Search Trends Database..."
          className="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
        />
        {query && (
          <button
            onClick={() => { setQuery(''); setPage(1) }}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        )}
      </div>

      {/* Category grid (root view, no search/filter) */}
      {showCategoryGrid && (
        <div className="mb-8">
          <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Browse by Category</h2>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            {categories.map((cat) => (
              <CategoryCard key={cat.id} cat={cat} onClick={() => selectCategory(cat.id)} />
            ))}
          </div>
        </div>
      )}

      {/* Filter bar */}
      <div className="flex flex-wrap items-center gap-3 mb-6">
        {/* Category pills */}
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
            {cat.name} <span className="opacity-60">{cat.total.toLocaleString()}</span>
          </button>
        ))}

        <div className="flex-1" />

        {/* Status filter */}
        <select
          value={status}
          onChange={(e) => changeStatus(e.target.value)}
          className="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
        >
          {STATUS_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>

        {/* Sort */}
        <select
          value={sort}
          onChange={(e) => changeSort(e.target.value as CatalogSort)}
          className="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
        >
          {SORT_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>
      </div>

      {error && (
        <div className="rounded-lg bg-red-50 border border-red-100 p-4 text-red-600 mb-6 text-sm">{error}</div>
      )}

      {/* Topic grid */}
      {loading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <div key={i} className="bg-white rounded-xl border border-gray-100 p-5 h-40 animate-pulse">
              <div className="h-4 bg-gray-100 rounded w-3/4 mb-3" />
              <div className="h-12 bg-gray-100 rounded mb-3" />
              <div className="flex gap-3">
                <div className="h-7 bg-gray-100 rounded w-14" />
                <div className="h-7 bg-gray-100 rounded w-14" />
              </div>
            </div>
          ))}
        </div>
      ) : topics.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-24 text-center">
          <div className="w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
            <svg className="w-8 h-8 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <p className="text-gray-600 text-sm font-medium">No trends found</p>
          <p className="text-gray-400 text-xs mt-1">
            {debouncedQuery ? `No results for "${debouncedQuery}"` : 'Try adjusting your filters'}
          </p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {topics.map((topic) => (
              <CatalogCard key={topic.id} topic={topic} />
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
              <span className="text-sm text-gray-500">Page {page} of {totalPages}</span>
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
            {total.toLocaleString()} topics total
          </div>
        </>
      )}
    </div>
  )
}
