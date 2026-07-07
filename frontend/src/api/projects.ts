import { apiFetch, BASE } from './index'
import type { TopicStatus } from './dashboard'

export interface Project {
  id: number
  name: string
  topic_count: number
  created_at: string
}

export interface ProjectTopic {
  id: number
  keyword: string
  geo: string
  status: TopicStatus
  score: number | null
  volume: number | null
  volume_fmt: string | null
  growth_pct: number | null
  growth_12m: number | null
  growth_fmt: string
  sparkline: number[] | null
  category_name: string | null
  added_at: string
}

export async function fetchProjects(): Promise<Project[]> {
  return apiFetch<Project[]>('/projects')
}

export async function createProject(name: string): Promise<Project> {
  const res = await fetch(`${BASE}/projects`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name }),
  })
  if (!res.ok) throw new Error(await res.text())
  return res.json()
}

export async function deleteProject(id: number): Promise<void> {
  const res = await fetch(`${BASE}/projects/${id}`, { method: 'DELETE' })
  if (!res.ok) throw new Error(await res.text())
}

export async function fetchProjectTopics(projectId: number): Promise<ProjectTopic[]> {
  return apiFetch<ProjectTopic[]>(`/projects/${projectId}/topics`)
}

export async function addTopicToProject(projectId: number, topicId: number): Promise<void> {
  const res = await fetch(`${BASE}/projects/${projectId}/topics`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ topic_id: topicId }),
  })
  if (!res.ok) throw new Error(await res.text())
}

export async function removeTopicFromProject(projectId: number, topicId: number): Promise<void> {
  const res = await fetch(`${BASE}/projects/${projectId}/topics/${topicId}`, { method: 'DELETE' })
  if (!res.ok) throw new Error(await res.text())
}

export function projectExportUrl(projectId: number): string {
  return `${BASE}/projects/${projectId}/export`
}
