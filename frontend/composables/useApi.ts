export const useApi = () => {
    const config = useRuntimeConfig()
    const base = config.public.apiBase
    const token = process.client ? localStorage.getItem('token') : null
    const headers: Record<string, string> = { 'Content-Type': 'application/json' }
    if (token) headers['Authorization'] = `Bearer ${token}`
    return {
        get: <T>(path: string) => $fetch<T>(`${base}${path}`, { headers }),
        post: <T>(path: string, body: any, opts: any = {}) => $fetch<T>(`${base}${path}`, { method: 'POST', body, headers, ...opts }),
        patch: <T>(path: string, body: any) => $fetch<T>(`${base}${path}`, { method: 'PATCH', body, headers }),
        del: <T>(path: string) => $fetch<T>(`${base}${path}`, { method: 'DELETE', headers }),
    }
}

