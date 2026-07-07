import { useNavigate } from 'react-router-dom'
import SparklineChart from './SparklineChart'
import type { TrendTopic } from '../api/dashboard'

interface Props {
  topic: TrendTopic
}

export default function TrendCard({ topic }: Props) {
  const navigate = useNavigate()

  function handleAnalyze() {
    navigate(`/analysis?keyword=${encodeURIComponent(topic.keyword)}&geo=${topic.geo}`)
  }

  return (
    <div className="bg-white rounded-xl border border-gray-100 p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between gap-2">
        <button
          onClick={handleAnalyze}
          className="text-left font-semibold text-gray-900 hover:text-indigo-600 transition-colors leading-tight line-clamp-2"
        >
          {topic.keyword}
        </button>
        {topic.category_name && (
          <span className="shrink-0 text-xs text-gray-400 bg-gray-50 rounded px-2 py-0.5 border border-gray-100">
            {topic.category_name}
          </span>
        )}
      </div>

      <div className="h-15">
        {topic.sparkline && topic.sparkline.length > 0 ? (
          <SparklineChart data={topic.sparkline} />
        ) : (
          <div className="h-15 bg-gray-50 rounded animate-pulse" />
        )}
      </div>

      <div className="flex items-end justify-between">
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
              <div className="text-base font-bold text-emerald-600">{topic.growth_fmt}</div>
            </div>
          )}
        </div>

        <button
          onClick={handleAnalyze}
          className="text-xs font-semibold text-indigo-600 border border-indigo-200 rounded-lg px-3 py-1.5 hover:bg-indigo-50 transition-colors whitespace-nowrap"
        >
          + TRACK TOPIC
        </button>
      </div>
    </div>
  )
}
