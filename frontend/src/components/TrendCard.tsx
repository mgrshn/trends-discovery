import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import SparklineChart from './SparklineChart'
import type { TrendTopic, TopicStatus } from '../api/dashboard'
import { useTracking } from '../context/TrackingContext'

interface Props {
  topic: TrendTopic
}

function StatusBadge({ status }: { status: TopicStatus }) {
  if (!status || status === 'noise') return null

  const styles: Record<string, string> = {
    exploding: 'bg-amber-50 text-amber-700 border-amber-200',
    regular:   'bg-emerald-50 text-emerald-700 border-emerald-200',
    peaked:    'bg-gray-100 text-gray-500 border-gray-200',
  }
  const labels: Record<string, string> = {
    exploding: 'Exploding',
    regular:   'Regular',
    peaked:    'Peaked',
  }

  return (
    <span className={`shrink-0 text-xs font-medium border rounded-full px-2 py-0.5 ${styles[status] ?? ''}`}>
      {labels[status] ?? status}
    </span>
  )
}

export default function TrendCard({ topic }: Props) {
  const navigate = useNavigate()
  const { trackTopic } = useTracking()
  const [tracking, setTracking] = useState(false)
  const [tracked, setTracked]   = useState(false)

  function handleAnalyze() {
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

  const growthColor = topic.growth_fmt?.startsWith('-')
    ? 'text-red-500'
    : 'text-emerald-600'

  return (
    <div className="bg-white rounded-xl border border-gray-100 p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between gap-2">
        <button
          onClick={handleAnalyze}
          className="text-left font-semibold text-gray-900 hover:text-indigo-600 transition-colors leading-tight line-clamp-2"
        >
          {topic.keyword}
        </button>
        <div className="flex flex-col items-end gap-1 shrink-0">
          <StatusBadge status={topic.status} />
          {!topic.status && topic.category_name && (
            <span className="text-xs text-gray-400 bg-gray-50 rounded px-2 py-0.5 border border-gray-100">
              {topic.category_name}
            </span>
          )}
        </div>
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
              <div className={`text-base font-bold ${growthColor}`}>{topic.growth_fmt}</div>
            </div>
          )}
        </div>

        <button
          onClick={handleTrack}
          className={`text-xs font-semibold border rounded-lg px-3 py-1.5 transition-colors whitespace-nowrap ${
            tracked
              ? 'text-emerald-600 border-emerald-200 bg-emerald-50'
              : 'text-indigo-600 border-indigo-200 hover:bg-indigo-50'
          }`}
        >
          {tracked ? '✓ TRACKED' : tracking ? '…' : '+ TRACK TOPIC'}
        </button>
      </div>
    </div>
  )
}
