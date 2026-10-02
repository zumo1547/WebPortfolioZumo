import { createHmac } from 'node:crypto'
import { createClient } from '@supabase/supabase-js'

type ApiRequest = {
  method?: string
  body?: unknown
  headers: Record<string, string | string[] | undefined>
  socket?: { remoteAddress?: string }
}

type ApiResponse = {
  status: (code: number) => ApiResponse
  setHeader: (name: string, value: string) => void
  json: (body: unknown) => void
  end: () => void
}

type ContactPayload = {
  name?: unknown
  email?: unknown
  subject?: unknown
  message?: unknown
  website?: unknown
  startedAt?: unknown
}

const text = (value: unknown) => typeof value === 'string' ? value.trim() : ''

const parseBody = (body: unknown): ContactPayload => {
  if (typeof body === 'string') {
    try { return JSON.parse(body) as ContactPayload } catch { return {} }
  }
  return body && typeof body === 'object' ? body as ContactPayload : {}
}

const getHeader = (headers: ApiRequest['headers'], name: string) => {
  const value = headers[name] || headers[name.toLowerCase()]
  return Array.isArray(value) ? value[0] : value || ''
}

export default async function handler(request: ApiRequest, response: ApiResponse) {
  response.setHeader('Cache-Control', 'no-store')
  if (request.method !== 'POST') {
    response.setHeader('Allow', 'POST')
    response.status(405).json({ error: 'Method not allowed' })
    return
  }

  const siteUrl = process.env.VITE_SITE_URL || 'https://webportfoliozumo.vercel.app'
  const origin = getHeader(request.headers, 'origin')
  const allowedOrigins = new Set([siteUrl, 'http://127.0.0.1:5173', 'http://localhost:5173'])
  if (origin && !allowedOrigins.has(origin)) {
    response.status(403).json({ error: 'Origin not allowed' })
    return
  }

  const payload = parseBody(request.body)
  if (text(payload.website)) {
    response.status(200).json({ ok: true })
    return
  }

  const startedAt = Number(payload.startedAt)
  if (!Number.isFinite(startedAt) || Date.now() - startedAt < 1800 || Date.now() - startedAt > 86_400_000) {
    response.status(400).json({ error: 'Please complete the form normally' })
    return
  }

  const name = text(payload.name)
  const email = text(payload.email).toLowerCase()
  const subject = text(payload.subject)
  const message = text(payload.message)
  const validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
  if (!name || name.length > 80 || !validEmail || email.length > 254 || !subject || subject.length > 120 || !message || message.length > 2000) {
    response.status(400).json({ error: 'Invalid form data' })
    return
  }

  const supabaseUrl = process.env.SUPABASE_URL || process.env.VITE_SUPABASE_URL
  const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_SECRET_KEY
  if (!supabaseUrl || !serviceKey) {
    response.status(503).json({ error: 'Contact service is unavailable' })
    return
  }

  const forwarded = getHeader(request.headers, 'x-forwarded-for').split(',')[0]?.trim()
  const ip = getHeader(request.headers, 'x-real-ip') || forwarded || request.socket?.remoteAddress || 'unknown'
  const rateSecret = process.env.CONTACT_RATE_LIMIT_SECRET || serviceKey
  const ipHash = createHmac('sha256', rateSecret).update(ip).digest('hex')
  const supabase = createClient(supabaseUrl, serviceKey, { auth: { persistSession: false, autoRefreshToken: false } })
  const { error } = await supabase.rpc('server_submit_contact_message', {
    sender_name: name,
    sender_email: email,
    sender_subject: subject,
    sender_message: message,
    sender_ip_hash: ipHash,
  })

  if (error) {
    if (error.message.includes('CONTACT_RATE_LIMIT')) {
      response.status(429).json({ error: 'ส่งข้อความบ่อยเกินไป กรุณารอ 15 นาทีแล้วลองใหม่' })
      return
    }
    response.status(500).json({ error: 'Unable to send message', reference: error.code || 'CONTACT_RPC_FAILED' })
    return
  }

  response.status(201).json({ ok: true })
}
