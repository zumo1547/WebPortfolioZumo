import { readFile } from 'node:fs/promises'
import pg from 'pg'

const loadEnv = async () => {
  const content = await readFile(new URL('../.env.local', import.meta.url), 'utf8')
  return Object.fromEntries(content.split(/\r?\n/).filter((line) => line && !line.startsWith('#')).map((line) => {
    const index = line.indexOf('=')
    return [line.slice(0, index), line.slice(index + 1).replace(/^['"]|['"]$/g, '')]
  }))
}

const env = { ...process.env, ...await loadEnv() }
if (!env.POSTGRES_URL) throw new Error('POSTGRES_URL is missing from .env.local')

const sql = await readFile(new URL('../supabase/schema.sql', import.meta.url), 'utf8')
const connectionUrl = new URL(env.POSTGRES_URL)
connectionUrl.searchParams.delete('sslmode')
const client = new pg.Client({ connectionString: connectionUrl.toString(), ssl: { rejectUnauthorized: false } })
await client.connect()
try {
  await client.query(sql)
  const { rows } = await client.query(`
    select
      (select count(*) from public.projects) as projects,
      (select count(*) from public.profiles) as profiles,
      (select count(*) from public.profiles where role = 'admin') as admins,
      (select count(*) from public.admin_activity) as audit_events,
      (select count(*) from pg_proc join pg_namespace on pg_namespace.oid = pg_proc.pronamespace where pg_namespace.nspname = 'public' and pg_proc.proname in ('admin_delete_project', 'admin_set_user_role')) as admin_functions,
      (select relrowsecurity from pg_class join pg_namespace on pg_namespace.oid = pg_class.relnamespace where pg_namespace.nspname = 'public' and pg_class.relname = 'projects') as projects_rls
  `)
  console.log('Supabase schema is ready:', rows[0])
} finally {
  await client.end()
}
