import { useEffect } from 'react'

const SITE_URL = (import.meta.env.VITE_SITE_URL as string | undefined) || 'https://webportfoliozumo.vercel.app'

const setMeta = (selector: string, attribute: 'name' | 'property', key: string, content: string) => {
  let element = document.head.querySelector<HTMLMetaElement>(selector)
  if (!element) {
    element = document.createElement('meta')
    element.setAttribute(attribute, key)
    document.head.appendChild(element)
  }
  element.content = content
}

export function Seo({
  title,
  description,
  path = '/',
  image = '/assets/og-portfolio.png',
  type = 'website',
}: {
  title: string
  description: string
  path?: string
  image?: string
  type?: 'website' | 'article'
}) {
  useEffect(() => {
    const canonicalUrl = new URL(path, SITE_URL).toString()
    const imageUrl = new URL(image, SITE_URL).toString()
    document.title = title

    let canonical = document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]')
    if (!canonical) {
      canonical = document.createElement('link')
      canonical.rel = 'canonical'
      document.head.appendChild(canonical)
    }
    canonical.href = canonicalUrl

    setMeta('meta[name="description"]', 'name', 'description', description)
    setMeta('meta[property="og:title"]', 'property', 'og:title', title)
    setMeta('meta[property="og:description"]', 'property', 'og:description', description)
    setMeta('meta[property="og:type"]', 'property', 'og:type', type)
    setMeta('meta[property="og:url"]', 'property', 'og:url', canonicalUrl)
    setMeta('meta[property="og:image"]', 'property', 'og:image', imageUrl)
    setMeta('meta[property="og:image:alt"]', 'property', 'og:image:alt', title)
    setMeta('meta[property="og:image:width"]', 'property', 'og:image:width', '1200')
    setMeta('meta[property="og:image:height"]', 'property', 'og:image:height', '630')
    setMeta('meta[property="og:locale"]', 'property', 'og:locale', 'th_TH')
    setMeta('meta[name="twitter:card"]', 'name', 'twitter:card', 'summary_large_image')
    setMeta('meta[name="twitter:title"]', 'name', 'twitter:title', title)
    setMeta('meta[name="twitter:description"]', 'name', 'twitter:description', description)
    setMeta('meta[name="twitter:image"]', 'name', 'twitter:image', imageUrl)
    setMeta('meta[name="twitter:image:alt"]', 'name', 'twitter:image:alt', title)
  }, [description, image, path, title, type])

  return null
}
