import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import SparklineChart from '../components/SparklineChart'
import type { Project, ProjectTopic } from '../api/projects'
import {
  createProject,
  deleteProject,
  fetchProjectTopics,
  fetchProjects,
  projectExportUrl,
  removeTopicFromProject,
} from '../api/projects'

const STATUS_BADGE: Record<string, string> = {
  exploding: 'bg-amber-50 text-amber-700 border-amber-200',
  regular:   'bg-emerald-50 text-emerald-700 border-emerald-200',
  peaked:    'bg-gray-100 text-gray-500 border-gray-200',
}

// ── Create Project Modal ───────────────────────────────────────────────────

function CreateProjectModal({ onClose, onCreate }: {
  onClose: () => void
  onCreate: (name: string) => Promise<void>
}) {
  const [name, setName] = useState('')
  const [loading, setLoading] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)

  useEffect(() => { inputRef.current?.focus() }, [])

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    if (!name.trim() || loading) return
    setLoading(true)
    await onCreate(name.trim()).catch(() => setLoading(false))
  }

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50" onClick={onClose}>
      <div className="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4" onClick={(e) => e.stopPropagation()}>
        <h2 className="text-lg font-bold text-gray-900 mb-4">Create New Project</h2>
        <form onSubmit={submit}>
          <input
            ref={inputRef}
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Project name..."
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-4"
          />
          <div className="flex gap-2 justify-end">
            <button type="button" onClick={onClose}
              className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
              Cancel
            </button>
            <button type="submit" disabled={!name.trim() || loading}
              className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50">
              {loading ? 'Creating…' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// ── Topic Card ─────────────────────────────────────────────────────────────

function TrackedTopicCard({ topic, projectId, onRemoved }: {
  topic: ProjectTopic
  projectId: number
  onRemoved: (topicId: number) => void
}) {
  const navigate = useNavigate()
  const [removing, setRemoving] = useState(false)
  const growthColor = topic.growth_fmt?.startsWith('-') ? 'text-red-500' : 'text-emerald-600'

  async function handleRemove(e: React.MouseEvent) {
    e.stopPropagation()
    setRemoving(true)
    await removeTopicFromProject(projectId, topic.id).catch(() => {})
    onRemoved(topic.id)
  }

  return (
    <div
      className="bg-white rounded-xl border border-gray-100 p-5 flex flex-col gap-3 hover:shadow-md transition-shadow cursor-pointer relative group"
      onClick={() => navigate(`/analysis?keyword=${encodeURIComponent(topic.keyword)}&geo=${topic.geo}`)}
    >
      <button
        onClick={handleRemove}
        disabled={removing}
        className="absolute top-3 right-3 w-6 h-6 rounded-full bg-gray-100 text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100 flex items-center justify-center text-sm font-bold"
        title="Remove from project"
      >
        ×
      </button>

      <div className="flex items-start gap-2 pr-6">
        <span className="font-semibold text-gray-900 leading-tight line-clamp-2 text-sm flex-1">{topic.keyword}</span>
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
    </div>
  )
}

// ── Project Card ───────────────────────────────────────────────────────────

function ProjectCard({ project, active, onClick, onDelete }: {
  project: Project
  active: boolean
  onClick: () => void
  onDelete: () => void
}) {
  return (
    <div
      onClick={onClick}
      className={`rounded-xl border p-4 cursor-pointer transition-all ${
        active
          ? 'border-indigo-300 bg-indigo-50'
          : 'border-gray-100 bg-white hover:shadow-md hover:border-indigo-200'
      }`}
    >
      <div className="flex items-start justify-between gap-2">
        <div>
          <div className="font-semibold text-gray-900 text-sm">{project.name}</div>
          <div className="text-xs text-gray-400 mt-0.5">{project.topic_count} topics</div>
        </div>
        <button
          onClick={(e) => { e.stopPropagation(); onDelete() }}
          className="text-gray-300 hover:text-red-400 transition-colors text-lg leading-none"
          title="Delete project"
        >
          ×
        </button>
      </div>
    </div>
  )
}

// ── Main Page ──────────────────────────────────────────────────────────────

export default function TrendTrackingPage() {
  const [projects, setProjects]           = useState<Project[]>([])
  const [activeProjectId, setActiveId]    = useState<number | null>(null)
  const [topics, setTopics]               = useState<ProjectTopic[]>([])
  const [topicsLoading, setTopicsLoading] = useState(false)
  const [showCreate, setShowCreate]       = useState(false)
  const [loading, setLoading]             = useState(true)

  const activeProject = projects.find((p) => p.id === activeProjectId) ?? null

  useEffect(() => {
    fetchProjects()
      .then((list) => {
        setProjects(list)
        if (list.length > 0) setActiveId(list[0].id)
      })
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [])

  const loadTopics = useCallback(async (projectId: number) => {
    setTopicsLoading(true)
    setTopics([])
    try {
      const data = await fetchProjectTopics(projectId)
      setTopics(data)
    } finally {
      setTopicsLoading(false)
    }
  }, [])

  useEffect(() => {
    if (activeProjectId !== null) {
      loadTopics(activeProjectId)
    }
  }, [activeProjectId, loadTopics])

  async function handleCreate(name: string) {
    const project = await createProject(name)
    setProjects((prev) => [project, ...prev])
    setActiveId(project.id)
    setShowCreate(false)
  }

  async function handleDelete(id: number) {
    await deleteProject(id).catch(() => {})
    const remaining = projects.filter((p) => p.id !== id)
    setProjects(remaining)
    if (activeProjectId === id) {
      setActiveId(remaining.length > 0 ? remaining[0].id : null)
      setTopics([])
    }
  }

  function handleTopicRemoved(topicId: number) {
    setTopics((prev) => prev.filter((t) => t.id !== topicId))
    setProjects((prev) =>
      prev.map((p) =>
        p.id === activeProjectId
          ? { ...p, topic_count: Math.max(0, p.topic_count - 1) }
          : p,
      ),
    )
  }

  function handleExport() {
    if (!activeProjectId) return
    window.open(projectExportUrl(activeProjectId), '_blank')
  }

  if (loading) {
    return (
      <div className="p-8">
        <div className="h-8 bg-gray-100 rounded w-48 mb-4 animate-pulse" />
        <div className="h-4 bg-gray-100 rounded w-72 animate-pulse" />
      </div>
    )
  }

  return (
    <div className="p-8">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Trend Tracking</h1>
          <p className="text-gray-500 mt-1">Track topics across projects and monitor their growth</p>
        </div>
        <div className="flex gap-2">
          {activeProjectId && (
            <button
              onClick={handleExport}
              className="px-4 py-2 border border-gray-200 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Export CSV
            </button>
          )}
          <button
            onClick={() => setShowCreate(true)}
            className="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors"
          >
            + Create New Project
          </button>
        </div>
      </div>

      {projects.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-32 text-center">
          <div className="w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
            <svg className="w-8 h-8 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
          </div>
          <p className="text-gray-600 font-medium mb-1">No projects yet</p>
          <p className="text-gray-400 text-sm mb-6">Create a project and start tracking trends</p>
          <button
            onClick={() => setShowCreate(true)}
            className="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700"
          >
            Create First Project
          </button>
        </div>
      ) : (
        <>
          {/* Project selector row */}
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-8">
            {projects.map((p) => (
              <ProjectCard
                key={p.id}
                project={p}
                active={p.id === activeProjectId}
                onClick={() => setActiveId(p.id)}
                onDelete={() => handleDelete(p.id)}
              />
            ))}
          </div>

          {activeProject && (
            <div>
              <div className="flex items-center justify-between mb-4">
                <h2 className="font-semibold text-gray-800">{activeProject.name}</h2>
                <span className="text-sm text-gray-400">{topics.length} topics tracked</span>
              </div>

              {topicsLoading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                  {Array.from({ length: 4 }).map((_, i) => (
                    <div key={i} className="bg-white rounded-xl border border-gray-100 p-5 h-40 animate-pulse">
                      <div className="h-4 bg-gray-100 rounded w-3/4 mb-3" />
                      <div className="h-12 bg-gray-100 rounded mb-3" />
                    </div>
                  ))}
                </div>
              ) : topics.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-20 text-center border-2 border-dashed border-gray-200 rounded-2xl">
                  <p className="text-gray-500 font-medium mb-1">No topics tracked</p>
                  <p className="text-gray-400 text-sm">
                    Click <strong>+ TRACK TOPIC</strong> on any trend card to add it here
                  </p>
                </div>
              ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                  {topics.map((t) => (
                    <TrackedTopicCard
                      key={t.id}
                      topic={t}
                      projectId={activeProjectId!}
                      onRemoved={handleTopicRemoved}
                    />
                  ))}
                </div>
              )}
            </div>
          )}
        </>
      )}

      {showCreate && (
        <CreateProjectModal
          onClose={() => setShowCreate(false)}
          onCreate={handleCreate}
        />
      )}
    </div>
  )
}
