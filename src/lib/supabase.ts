import { createClient } from '@supabase/supabase-js'

const supabaseUrl = (import.meta.env.VITE_SUPABASE_URL as string | undefined) || 'https://qywoebwqrtatnaakocav.supabase.co'
const supabaseAnonKey = (import.meta.env.VITE_SUPABASE_ANON_KEY as string | undefined) || 'sb_publishable_9SCptqAoBbg6STsvhLhU7Q_sIAgcLOz'

export const isSupabaseConfigured = Boolean(supabaseUrl && supabaseAnonKey)

export const supabase = createClient(
  supabaseUrl,
  supabaseAnonKey,
  {
    auth: {
      persistSession: true,
      autoRefreshToken: true,
      detectSessionInUrl: true,
    },
  },
)

export const assetUrl = (path: string) => `${import.meta.env.BASE_URL}${path.replace(/^\//, '')}`

export const projectImageUrl = (path: string) => {
  if (!path) return assetUrl('assets/Icon portfolio.png')
  if (/^https?:\/\//i.test(path)) return path
  if (path.startsWith('/') || path.startsWith('assets/') || path.startsWith('uploads/')) return assetUrl(path)
  return supabase.storage.from('project-images').getPublicUrl(path).data.publicUrl
}
