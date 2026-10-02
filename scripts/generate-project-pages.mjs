import { mkdir, readFile, writeFile } from 'node:fs/promises'
import { resolve } from 'node:path'

const siteUrl = (process.env.VITE_SITE_URL || 'https://webportfoliozumo.vercel.app').replace(/\/$/, '')
const supabaseUrl = process.env.VITE_SUPABASE_URL || 'https://qywoebwqrtatnaakocav.supabase.co'
const anonKey = process.env.VITE_SUPABASE_ANON_KEY || 'sb_publishable_9SCptqAoBbg6STsvhLhU7Q_sIAgcLOz'
const distDirectory = resolve('dist')

const escapeHtml = (value) => String(value)
  .replaceAll('&', '&amp;')
  .replaceAll('"', '&quot;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')

const cleanDescription = (value) => String(value || '')
  .replace(/\*\*([^*\n]+)\*\*/g, '$1')
  .replace(/\*([^*\n]+)\*/g, '$1')
  .replace(/\s+/g, ' ')
  .trim()
  .slice(0, 160)

const imageUrl = (path) => {
  if (!path) return `${siteUrl}/assets/og-portfolio.png`
  if (/^https?:\/\//i.test(path)) return path
  if (path.startsWith('/') || path.startsWith('assets/') || path.startsWith('uploads/')) return `${siteUrl}/${path.replace(/^\//, '')}`
  return `${supabaseUrl}/storage/v1/object/public/project-images/${path.split('/').map(encodeURIComponent).join('/')}`
}

const replaceMeta = (html, matcher, replacement) => matcher.test(html) ? html.replace(matcher, replacement) : html.replace('</head>', `    ${replacement}\n  </head>`)

const renderProjectHtml = (baseHtml, project) => {
  const projectName = String(project.name || '').trim()
  const title = `${projectName} — Wutthipat Sriyangnok`
  const description = cleanDescription(project.description) || 'รายละเอียดผลงานของ Wutthipat Sriyangnok'
  const url = `${siteUrl}/projects/${project.slug}`
  const image = imageUrl(Array.isArray(project.images) ? project.images[0] : '')
  let html = baseHtml
  html = replaceMeta(html, /<title>.*?<\/title>/s, `<title>${escapeHtml(title)}</title>`)
  html = replaceMeta(html, /<link rel="canonical"[^>]*>/, `<link rel="canonical" href="${escapeHtml(url)}" />`)
  html = replaceMeta(html, /<meta name="description"[^>]*>/, `<meta name="description" content="${escapeHtml(description)}" />`)
  for (const [property, content] of [['og:type', 'article'], ['og:title', title], ['og:description', description], ['og:url', url], ['og:image', image]]) {
    html = replaceMeta(html, new RegExp(`<meta property="${property}"[^>]*>`), `<meta property="${property}" content="${escapeHtml(content)}" />`)
  }
  html = replaceMeta(html, /<meta property="og:image:alt"[^>]*>/, `<meta property="og:image:alt" content="${escapeHtml(projectName)}" />`)
  for (const [name, content] of [['twitter:title', title], ['twitter:description', description], ['twitter:image', image]]) {
    html = replaceMeta(html, new RegExp(`<meta name="${name}"[^>]*>`), `<meta name="${name}" content="${escapeHtml(content)}" />`)
  }
  return html
}

const baseHtml = await readFile(resolve(distDirectory, 'index.html'), 'utf8')
let projects = []
try {
  const response = await fetch(`${supabaseUrl}/rest/v1/projects?select=slug,name,description,images&slug=not.is.null&order=created_at.desc`, {
    headers: { apikey: anonKey, Authorization: `Bearer ${anonKey}` },
    signal: AbortSignal.timeout(12_000),
  })
  if (!response.ok) throw new Error(`Supabase returned ${response.status}`)
  projects = (await response.json()).filter((project) => /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(project.slug || ''))
} catch (error) {
  console.warn(`Could not pre-render project metadata: ${error instanceof Error ? error.message : error}`)
}

for (const project of projects) {
  const directory = resolve(distDirectory, 'projects', project.slug)
  await mkdir(directory, { recursive: true })
  await writeFile(resolve(directory, 'index.html'), renderProjectHtml(baseHtml, project), 'utf8')
}

const routes = ['/', '/about', '/projects', '/contact', ...projects.map((project) => `/projects/${project.slug}`)]
const sitemap = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${routes.map((route, index) => `  <url><loc>${siteUrl}${route}</loc><priority>${index === 0 ? '1.0' : route === '/projects' ? '0.9' : '0.7'}</priority></url>`).join('\n')}\n</urlset>\n`
await writeFile(resolve(distDirectory, 'sitemap.xml'), sitemap, 'utf8')
console.log(`Generated social metadata for ${projects.length} project page(s).`)
