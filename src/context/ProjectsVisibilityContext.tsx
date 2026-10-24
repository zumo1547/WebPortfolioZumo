import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { supabase } from '../lib/supabase'

interface ProjectsVisibilityValue {
  maintenance: boolean
  loading: boolean
  error: string | null
  refresh: () => Promise<void>
  setMaintenance: (enabled: boolean) => Promise<string | null>
}

const ProjectsVisibilityContext = createContext<ProjectsVisibilityValue | undefined>(undefined)

export function ProjectsVisibilityProvider({ children }: { children: ReactNode }) {
  // Fail closed if the settings request fails; the admin can still access projects.
  const [maintenance, setMaintenanceState] = useState(true)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const refresh = useCallback(async () => {
    const { data, error: fetchError } = await supabase
      .from('site_settings')
      .select('maintenance')
      .eq('id', 'projects')
      .single()

    if (fetchError || !data) {
      setMaintenanceState(true)
      setError(fetchError?.message || 'ไม่พบสถานะหน้าโปรเจกต์')
    } else {
      setMaintenanceState(data.maintenance === true)
      setError(null)
    }
    setLoading(false)
  }, [])

  useEffect(() => {
    void refresh()
    const channel = supabase
      .channel('projects-visibility')
      .on('postgres_changes', { event: 'UPDATE', schema: 'public', table: 'site_settings', filter: 'id=eq.projects' }, (payload) => {
        const updated = payload.new as { maintenance?: boolean }
        if (typeof updated.maintenance === 'boolean') {
          setMaintenanceState(updated.maintenance)
          setError(null)
          setLoading(false)
        }
      })
      .subscribe()
    const onFocus = () => { void refresh() }
    window.addEventListener('focus', onFocus)
    const interval = window.setInterval(() => { void refresh() }, 30_000)
    return () => {
      window.removeEventListener('focus', onFocus)
      window.clearInterval(interval)
      void supabase.removeChannel(channel)
    }
  }, [refresh])

  const setMaintenance = useCallback(async (enabled: boolean) => {
    const { data, error: updateError } = await supabase
      .from('site_settings')
      .update({ maintenance: enabled })
      .eq('id', 'projects')
      .select('maintenance')
      .single()

    if (updateError || !data) {
      const message = updateError?.message || 'บันทึกสถานะไม่สำเร็จ'
      setError(message)
      return message
    }
    setMaintenanceState(data.maintenance === true)
    setError(null)
    return null
  }, [])

  const value = useMemo(() => ({ maintenance, loading, error, refresh, setMaintenance }), [maintenance, loading, error, refresh, setMaintenance])
  return <ProjectsVisibilityContext.Provider value={value}>{children}</ProjectsVisibilityContext.Provider>
}

// The hook shares this provider's single subscription and status state.
// eslint-disable-next-line react-refresh/only-export-components
export function useProjectsVisibility() {
  const value = useContext(ProjectsVisibilityContext)
  if (!value) throw new Error('useProjectsVisibility must be used inside ProjectsVisibilityProvider')
  return value
}
