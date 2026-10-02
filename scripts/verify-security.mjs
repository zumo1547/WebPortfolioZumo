import { readFile } from 'node:fs/promises'
import { createClient } from '@supabase/supabase-js'

const loadEnv = async () => {
  const content = await readFile(new URL('../.env.local', import.meta.url), 'utf8')
  return Object.fromEntries(content.split(/\r?\n/).filter((line) => line && !line.startsWith('#')).map((line) => {
    const index = line.indexOf('=')
    return [line.slice(0, index), line.slice(index + 1).replace(/^['"]|['"]$/g, '')]
  }))
}

const env = { ...process.env, ...await loadEnv() }
const url = env.VITE_SUPABASE_URL || env.SUPABASE_URL
const key = env.VITE_SUPABASE_ANON_KEY || env.SUPABASE_PUBLISHABLE_KEY || env.SUPABASE_ANON_KEY
if (!url || !key) throw new Error('Supabase public credentials are missing')

const anonymous = createClient(url, key, { auth: { persistSession: false, autoRefreshToken: false } })
const publicProjects = await anonymous.from('projects').select('id', { count: 'exact', head: true })
if (publicProjects.error) throw publicProjects.error

const [deleteAttempt, activityAttempt, messagesAttempt] = await Promise.all([
  anonymous.rpc('admin_delete_project', { target_project_id: 0 }),
  anonymous.from('admin_activity').select('id').limit(1),
  anonymous.from('contact_messages').select('id').limit(1),
])

if (!deleteAttempt.error) throw new Error('Anonymous project deletion was not blocked')
if (!activityAttempt.error) throw new Error('Anonymous audit log access was not blocked')
if (!messagesAttempt.error) throw new Error('Anonymous contact message access was not blocked')

console.log('Security verification passed:', {
  publicProjectsReadable: true,
  anonymousProjectDeleteBlocked: true,
  anonymousAuditReadBlocked: true,
  anonymousMessageReadBlocked: true,
})
