import type { EmailOtpType } from '@supabase/supabase-js'
import { CheckCircle2, ShieldCheck, XCircle } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { supabase } from '../lib/supabase'

const safeNextPath = (value: string | null) => {
  if (!value || !value.startsWith('/') || value.startsWith('//')) return '/'
  return value
}

export function AuthCallbackPage() {
  const navigate = useNavigate()
  const [error, setError] = useState('')
  const [confirmed, setConfirmed] = useState(false)

  useEffect(() => {
    let active = true

    const confirm = async () => {
      const search = new URLSearchParams(window.location.search)
      const hash = new URLSearchParams(window.location.hash.replace(/^#/, ''))
      const providerError = search.get('error_description') || hash.get('error_description') || search.get('error') || hash.get('error')
      if (providerError) throw new Error(providerError)

      const code = search.get('code')
      const tokenHash = search.get('token_hash')
      const type = search.get('type') as EmailOtpType | null
      const accessToken = hash.get('access_token')
      const refreshToken = hash.get('refresh_token')

      if (code) {
        const { error: callbackError } = await supabase.auth.exchangeCodeForSession(code)
        if (callbackError) throw callbackError
      } else if (tokenHash && type) {
        const { error: callbackError } = await supabase.auth.verifyOtp({ token_hash: tokenHash, type })
        if (callbackError) throw callbackError
      } else if (accessToken && refreshToken) {
        const { error: callbackError } = await supabase.auth.setSession({ access_token: accessToken, refresh_token: refreshToken })
        if (callbackError) throw callbackError
      } else {
        const { data, error: callbackError } = await supabase.auth.getSession()
        if (callbackError) throw callbackError
        if (!data.session) throw new Error('ลิงก์ยืนยันไม่ถูกต้องหรือหมดอายุแล้ว กรุณาขอลิงก์ใหม่')
      }

      if (!active) return
      setConfirmed(true)
      const next = safeNextPath(search.get('next'))
      window.setTimeout(() => navigate(next, { replace: true }), 900)
    }

    void confirm().catch((caught) => {
      if (!active) return
      const message = caught instanceof Error ? caught.message : 'ไม่สามารถยืนยันอีเมลได้'
      setError(message.includes('expired') ? 'ลิงก์ยืนยันหมดอายุแล้ว กรุณาส่งอีเมลยืนยันใหม่' : message)
    })

    return () => { active = false }
  }, [navigate])

  return <section className="auth-page">
    <div className="auth-card auth-callback-card reveal">
      <div className="panel-line" />
      <div className={`auth-orb ${error ? 'callback-error' : ''}`}>
        {error ? <XCircle /> : confirmed ? <CheckCircle2 /> : <ShieldCheck />}
      </div>
      <h1>{error ? 'ยืนยันอีเมลไม่สำเร็จ' : confirmed ? 'ยืนยันอีเมลสำเร็จ' : 'กำลังยืนยันอีเมล'}</h1>
      <p className="mono-label">// EMAIL VERIFICATION</p>
      {!error && !confirmed && <div className="callback-progress"><span className="spinner" /><p>กำลังตรวจสอบลิงก์อย่างปลอดภัย...</p></div>}
      {confirmed && <div className="notice success"><CheckCircle2 size={17} /> บัญชีพร้อมใช้งาน กำลังพาคุณกลับเข้าสู่เว็บไซต์</div>}
      {error && <>
        <div className="notice error"><XCircle size={17} /> {error}</div>
        <Link className="button auth-submit" to="/login">กลับไปเข้าสู่ระบบ</Link>
        <Link className="callback-register-link" to="/register">สมัครหรือส่งอีเมลยืนยันใหม่</Link>
      </>}
    </div>
  </section>
}
