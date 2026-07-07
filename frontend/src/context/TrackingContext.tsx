import { createContext, useCallback, useContext, useEffect, useState } from 'react'
import type { Project } from '../api/projects'
import { addTopicToProject, createProject, fetchProjects } from '../api/projects'

interface TrackingCtx {
  projects: Project[]
  trackTopic: (topicId: number) => Promise<void>
  refresh: () => void
}

const Ctx = createContext<TrackingCtx>({
  projects: [],
  trackTopic: async () => {},
  refresh: () => {},
})

export function TrackingProvider({ children }: { children: React.ReactNode }) {
  const [projects, setProjects] = useState<Project[]>([])

  const refresh = useCallback(() => {
    fetchProjects().then(setProjects).catch(() => {})
  }, [])

  useEffect(() => { refresh() }, [refresh])

  const trackTopic = useCallback(async (topicId: number) => {
    let list = projects

    // Auto-create "My Project" if no projects exist
    if (list.length === 0) {
      const created = await createProject('My Project')
      list = [created]
      setProjects(list)
    }

    // Add to the first project (simplest UX for MVP)
    const target = list[0]
    await addTopicToProject(target.id, topicId)

    // Update count
    setProjects((prev) =>
      prev.map((p) =>
        p.id === target.id ? { ...p, topic_count: p.topic_count + 1 } : p,
      ),
    )
  }, [projects])

  return <Ctx.Provider value={{ projects, trackTopic, refresh }}>{children}</Ctx.Provider>
}

export function useTracking() {
  return useContext(Ctx)
}
