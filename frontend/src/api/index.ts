// All requests go through Vite proxy → backend.
// No base URL needed: proxy rewrites /api/* → backend:8000/api/*

export async function apiFetch<T>(path: string, params?: Record<string, string>): Promise<T> {
  const url = new URL(`/api/v1${path}`, window.location.origin)
  if (params) {
    Object.entries(params).forEach(([k, v]) => v !== '' && url.searchParams.set(k, v))
  }
  const res = await fetch(url.toString())
  if (!res.ok) {
    const body = await res.json().catch(() => ({}))
    throw new Error(body?.message ?? `HTTP ${res.status}`)
  }
  return res.json()
}
