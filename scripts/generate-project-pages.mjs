import { mkdir, readFile, writeFile } from 'node:fs/promises'
import { resolve } from 'node:path'

const siteUrl = (process.env.VITE_SITE_URL || 'https://webportfoliozumo.vercel.app').replace(/\/$/, '')
const distDirectory = resolve('dist')
const baseHtml = await readFile(resolve(distDirectory, 'index.html'), 'utf8')

// Visibility changes without a deployment. Static project-specific HTML would
// keep exposing project details after the admin closes the archive again.
const title = 'ผลงาน — Wutthipat Sriyangnok'
const description = 'รวมผลงาน Game, IoT, Robotics, AI และ Web Development ของ Wutthipat Sriyangnok'
let projectsHtml = baseHtml
  .replace(/<title>.*?<\/title>/s, `<title>${title}</title>`)
  .replace(/<meta name="description"[^>]*>/, `<meta name="description" content="${description}" />`)
  .replace(/<link rel="canonical"[^>]*>/, `<link rel="canonical" href="${siteUrl}/projects" />`)
for (const [property, content] of [['og:title', title], ['og:description', description], ['og:url', `${siteUrl}/projects`]]) {
  projectsHtml = projectsHtml.replace(new RegExp(`<meta property="${property}"[^>]*>`), `<meta property="${property}" content="${content}" />`)
}
for (const [name, content] of [['twitter:title', title], ['twitter:description', description]]) {
  projectsHtml = projectsHtml.replace(new RegExp(`<meta name="${name}"[^>]*>`), `<meta name="${name}" content="${content}" />`)
}

await mkdir(resolve(distDirectory, 'projects'), { recursive: true })
await writeFile(resolve(distDirectory, 'projects', 'index.html'), projectsHtml, 'utf8')

const routes = ['/', '/about', '/projects', '/contact']
const sitemap = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${routes.map((route, index) => `  <url><loc>${siteUrl}${route}</loc><priority>${index === 0 ? '1.0' : route === '/projects' ? '0.9' : '0.7'}</priority></url>`).join('\n')}\n</urlset>\n`
await writeFile(resolve(distDirectory, 'sitemap.xml'), sitemap, 'utf8')
console.log('Generated sitemap and generic project archive metadata.')
