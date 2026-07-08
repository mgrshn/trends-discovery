import { useNavigate } from 'react-router-dom'
import type { LiveTopic } from '../api/dashboard'

interface Props {
  topic: LiveTopic
}

export default function LiveCard({ topic }: Props) {
  const navigate = useNavigate()

  function handleAnalyze() {
    navigate(`/analysis?keyword=${encodeURIComponent(topic.keyword)}&geo=${topic.geo}`)
  }

  const growthColor = topic.growth_fmt?.startsWith('-') ? 'text-red-500' : 'text-emerald-600'

  return (
    <div className="bg-white rounded-xl border border-gray-100 p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between gap-2">
        <button
          onClick={handleAnalyze}
          className="text-left font-semibold text-gray-900 hover:text-indigo-600 transition-colors leading-tight line-clamp-2"
        >
          {topic.keyword}
        </button>
        <span className="shrink-0 flex items-center gap-1 text-xs font-medium text-rose-600 bg-rose-50 border border-rose-200 rounded-full px-2 py-0.5">
          <span className="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse" />
          Live
        </span>
      </div>

      {topic.breakdown.length > 0 && (
        <div className="flex flex-wrap gap-1">
          {topic.breakdown.map((bk, i) => (
            <button
              key={i}
              onClick={() => navigate(`/analysis?keyword=${encodeURIComponent(bk)}&geo=${topic.geo}`)}
              className="text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded px-2 py-0.5 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-colors"
            >
              {bk}
            </button>
          ))}
        </div>
      )}

      <div className="flex items-end justify-between mt-auto">
        <div className="flex gap-4">
          {topic.volume_fmt && (
            <div>
              <div className="text-xs text-gray-400 uppercase tracking-wide">Volume</div>
              <div className="text-base font-bold text-gray-900">{topic.volume_fmt}</div>
            </div>
          )}
          {topic.growth_fmt && (
            <div>
              <div className="text-xs text-gray-400 uppercase tracking-wide">Growth</div>
              <div className={`text-base font-bold ${growthColor}`}>{topic.growth_fmt}</div>
            </div>
          )}
        </div>

        <button
          onClick={handleAnalyze}
          className="text-xs font-semibold border border-indigo-200 text-indigo-600 rounded-lg px-3 py-1.5 hover:bg-indigo-50 transition-colors whitespace-nowrap"
        >
          Analyze →
        </button>
      </div>
    </div>
  )
}
