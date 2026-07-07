import type { Region } from '../api/analysis'

interface Props {
  regions: Region[]
}

export default function RegionsList({ regions }: Props) {
  if (regions.length === 0) return null

  const top = regions.slice(0, 15)
  const max = top[0]?.value ?? 100

  return (
    <div>
      <h3 className="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">
        Interest by Region
      </h3>
      <div className="space-y-2">
        {top.map((r) => (
          <div key={r.geo_code} className="flex items-center gap-3">
            <span className="text-sm text-slate-600 w-40 shrink-0 truncate">{r.name}</span>
            <div className="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div
                className="h-full bg-indigo-500 rounded-full transition-all duration-500"
                style={{ width: `${(r.value / max) * 100}%` }}
              />
            </div>
            <span className="text-xs text-slate-400 w-8 text-right shrink-0">{r.value}</span>
          </div>
        ))}
      </div>
    </div>
  )
}
