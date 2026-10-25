import { ArrowLeft, AtSign, CheckCircle2, Eye, EyeOff, KeyRound, LockKeyhole, LogIn, Mail, ShieldCheck, UserPlus } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { authCallbackUrl, supabase } from '../lib/supabase'
import './AuthPage.css'

type Mode = 'login' | 'register' | 'forgot' | 'reset'

export function AuthPage({ mode }: { mode: Mode }) {
  const { user } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [showPassword, setShowPassword] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [message, setMessage] = useState(() => new URLSearchParams(location.search).has('confirmed') ? 'ยืนยันอีเมลสำเร็จแล้ว กรุณาเข้าสู่ระบบ' : '')
  const [pendingEmail, setPendingEmail] = useState('')
  if (user && mode !== 'reset') return <Navigate to={(location.state as { from?: string } | null)?.from || '/'} replace />

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setLoading(true); setError(''); setMessage('')
    const form = new FormData(event.currentTarget)
    const email = String(form.get('email') || '').trim()
    const password = String(form.get('password') || '')
    try {
      if (mode === 'login') {
        const { error: authError } = await supabase.auth.signInWithPassword({ email, password })
        if (authError) throw authError
        navigate((location.state as { from?: string } | null)?.from || '/')
      } else if (mode === 'register') {
        const username = String(form.get('username') || '').trim()
        const confirm = String(form.get('confirm') || '')
        if (username.length < 3) throw new Error('ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร')
        if (password.length < 8) throw new Error('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร')
        if (password !== confirm) throw new Error('รหัสผ่านไม่ตรงกัน')
        const { data, error: authError } = await supabase.auth.signUp({ email, password, options: { data: { username }, emailRedirectTo: authCallbackUrl('/') } })
        if (authError) throw authError
        setPendingEmail(data.session ? '' : email)
        setMessage(data.session ? 'สมัครสำเร็จ กำลังเข้าสู่ระบบ...' : 'สมัครสำเร็จ กรุณายืนยันอีเมลก่อนเข้าสู่ระบบ')
        if (data.session) setTimeout(() => navigate('/'), 800)
      } else if (mode === 'forgot') {
        const { error: authError } = await supabase.auth.resetPasswordForEmail(email, { redirectTo: authCallbackUrl('/reset-password') })
        if (authError) throw authError
        setMessage('ส่งลิงก์ตั้งรหัสผ่านใหม่ไปยังอีเมลแล้ว')
      } else {
        const confirm = String(form.get('confirm') || '')
        if (password.length < 8) throw new Error('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร')
        if (password !== confirm) throw new Error('รหัสผ่านไม่ตรงกัน')
        const { error: authError } = await supabase.auth.updateUser({ password })
        if (authError) throw authError
        setMessage('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว')
        setTimeout(() => navigate('/settings'), 900)
      }
    } catch (caught) {
      const caughtMessage = caught instanceof Error ? caught.message : 'เกิดข้อผิดพลาด กรุณาลองใหม่'
      if (mode === 'login' && caughtMessage.toLowerCase().includes('email not confirmed')) {
        setPendingEmail(email)
        setError('อีเมลนี้ยังไม่ได้รับการยืนยัน กรุณากดส่งอีเมลยืนยันใหม่')
      } else setError(caughtMessage)
    }
    finally { setLoading(false) }
  }

  const resendConfirmation = async () => {
    if (!pendingEmail) return
    setLoading(true); setError(''); setMessage('')
    const { error: resendError } = await supabase.auth.resend({
      type: 'signup',
      email: pendingEmail,
      options: { emailRedirectTo: authCallbackUrl('/') },
    })
    if (resendError) setError(resendError.message)
    else setMessage(`ส่งอีเมลยืนยันใหม่ไปที่ ${pendingEmail} แล้ว`)
    setLoading(false)
  }

  const signInWithGoogle = async () => {
    setLoading(true); setError(''); setMessage('')
    try {
      const next = (location.state as { from?: string } | null)?.from || '/'
      const redirectTo = new URL(authCallbackUrl(next))
      redirectTo.searchParams.set('provider', 'google')
      const { error: authError } = await supabase.auth.signInWithOAuth({
        provider: 'google',
        options: { redirectTo: redirectTo.toString(), queryParams: { prompt: 'select_account' } },
      })
      if (authError) throw authError
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : 'ไม่สามารถเข้าสู่ระบบด้วย Google ได้ กรุณาลองอีกครั้ง')
      setLoading(false)
    }
  }

  const copy = {
    login: { icon: <LogIn />, title: 'เข้าสู่ระบบ', code: 'SECURE LOGIN', button: 'เข้าสู่ระบบ' },
    register: { icon: <UserPlus />, title: 'สมัครสมาชิก', code: 'CREATE ACCOUNT', button: 'สร้างบัญชี' },
    forgot: { icon: <Mail />, title: 'ลืมรหัสผ่าน', code: 'RECOVERY LINK', button: 'ส่งลิงก์รีเซ็ต' },
    reset: { icon: <KeyRound />, title: 'ตั้งรหัสผ่านใหม่', code: 'RESET PASSWORD', button: 'บันทึกรหัสผ่านใหม่' },
  }[mode]

  return <section className="auth-page">
    <Link to="/" className="back-home"><ArrowLeft size={16} /> กลับหน้า Portfolio</Link>
    <div className="auth-card reveal">
      <div className="panel-line" />
      <div className="auth-orb">{copy.icon}</div><h1>{copy.title}</h1><p className="mono-label">// {copy.code}</p>
      <div className="secure-label"><ShieldCheck size={15} /> SUPABASE AUTH SECURED</div>
      <form onSubmit={submit}>
        {mode === 'register' && <label>ชื่อผู้ใช้<div className="input-icon-wrap"><AtSign /><input name="username" required minLength={3} maxLength={50} placeholder="Zumo" /></div></label>}
        {mode !== 'reset' && <label>อีเมล<div className="input-icon-wrap"><Mail /><input name="email" type="email" autoComplete="email" required placeholder="you@example.com" /></div></label>}
        {(mode === 'login' || mode === 'register' || mode === 'reset') && <label>{mode === 'reset' ? 'รหัสผ่านใหม่' : 'รหัสผ่าน'}<div className="input-icon-wrap"><LockKeyhole /><input name="password" type={showPassword ? 'text' : 'password'} autoComplete={mode === 'login' ? 'current-password' : 'new-password'} required minLength={8} placeholder="••••••••" /><button type="button" onClick={() => setShowPassword(!showPassword)}>{showPassword ? <EyeOff /> : <Eye />}</button></div></label>}
        {(mode === 'register' || mode === 'reset') && <label>ยืนยันรหัสผ่าน<div className="input-icon-wrap"><CheckCircle2 /><input name="confirm" type={showPassword ? 'text' : 'password'} required minLength={8} placeholder="••••••••" /></div></label>}
        {mode === 'login' && <Link className="forgot-link" to="/forgot-password">ลืมรหัสผ่าน?</Link>}
        {error && <div className="notice error">{error}</div>}
        {message && <div className="notice success"><CheckCircle2 size={17} /> {message}</div>}
        {pendingEmail && <button type="button" className="button secondary auth-resend" disabled={loading} onClick={() => void resendConfirmation()}><Mail size={17} /> ส่งอีเมลยืนยันอีกครั้ง</button>}
        <button className="button auth-submit" disabled={loading}>{loading ? 'กำลังดำเนินการ...' : copy.button}</button>
      </form>
      {mode === 'login' && <>
        <div className="auth-provider-divider"><span>หรือ</span></div>
        <button type="button" className="auth-google-button" disabled={loading} onClick={() => void signInWithGoogle()}>
          <svg aria-hidden="true" viewBox="0 0 48 48" width="21" height="21"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6C44.4 38.03 46.98 31.88 46.98 24.55Z"/><path fill="#FBBC05" d="M10.53 28.59A14.5 14.5 0 0 1 9.75 24c0-1.6.27-3.15.76-4.59l-7.98-6.2A23.9 23.9 0 0 0 0 24c0 3.87.93 7.52 2.56 10.78l7.97-6.19Z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.73-6c-2.15 1.45-4.92 2.3-8.18 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.97 6.19C6.5 42.62 14.62 48 24 48Z"/></svg>
          เข้าสู่ระบบด้วย Google
        </button>
      </>}
      {mode === 'login' && <p className="auth-switch">ยังไม่มีบัญชี? <Link to="/register">สมัครสมาชิก</Link></p>}
      {mode === 'register' && <p className="auth-switch">มีบัญชีแล้ว? <Link to="/login">เข้าสู่ระบบ</Link></p>}
      {mode === 'forgot' && <p className="auth-switch"><Link to="/login">กลับไปเข้าสู่ระบบ</Link></p>}
    </div>
  </section>
}
