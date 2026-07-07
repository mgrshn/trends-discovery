import type { RelatedQuery, TopQuery } from '../api/analysis'

interface Props {
  rising: RelatedQuery[]
  top: TopQuery[]
  onClickQuery: (q: string) => void
}

function RisingBadge({ formatted }: { formatted: string | null }) {
  if (!formatted) return null
  const isBreakout = formatted.toLowerCase() === 'breakout'
  return (
    <span
      className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${
        isBreakout
          ? 'bg-amber-100 text-amber-700'
          : 'bg-emerald-50 text-emerald-700'
      }`}
    >
      {isBreakout ? '🔥 Breakout' : formatted}
    </span>
  )
}

export default function RelatedQueries({ rising, top, onClickQuery }: Props) {
  if (rising.length === 0 && top.length === 0) return null

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
      {/* Rising */}
      <div>
        <h3 className="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">
          Rising
        </h3>
        <div className="space-y-1">
          {rising.slice(0, 10).map((r) => (
            <button
              key={r.query}
              onClick={() => onClickQuery(r.query)}
              className="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 text-left group transition-colors"
            >
              <span className="text-sm font-medium text-slate-700 group-hover:text-indigo-600 truncate mr-3">
                {r.query}
              </span>
              <RisingBadge formatted={r.formatted} />
            </button>
          ))}
          {rising.length === 0 && (
            <p className="text-sm text-slate-400 px-3 py-2">No rising queries found</p>
          )}
        </div>
      </div>

      {/* Top */}
      <div>
        <h3 className="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">
          Top Related
        </h3>
        <div className="space-y-1">
          {top.slice(0, 10).map((r) => (
            <button
              key={r.query}
              onClick={() => onClickQuery(r.query)}
              className="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 text-left group transition-colors"
            >
              <span className="text-sm font-medium text-slate-700 group-hover:text-indigo-600 truncate mr-3">
                {r.query}
              </span>
              <div className="flex items-center gap-2 shrink-0">
                <div className="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-indigo-400 rounded-full"
                    style={{ width: `${r.value}%` }}
                  />
                </div>
                <span className="text-xs text-slate-400 w-6 text-right">{r.value}</span>
              </div>
            </button>
          ))}
          {top.length === 0 && (
            <p className="text-sm text-slate-400 px-3 py-2">No top queries found</p>
          )}
        </div>
      </div>
    </div>
  )
}
