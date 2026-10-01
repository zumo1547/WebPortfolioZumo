import { CheckCircle2, Eye, EyeOff, Save, Shield, UserRound } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { PageHeader } from '../components/PageHeader'
import { useAuth } from '../context/AuthContext'
import { supabase } from '../lib/supabase'

export function SettingsPage() {
  const { user, profile, refreshProfile } = useAuth()
  const [tab, setTab] = useState<'profile' | 'password'>('profile')
  const [show, setShow] = useState(false)
  const [status, setStatus] = useState('')
  const [error, setError] = useState('')

  const updateProfile = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setStatus(''); setError('')
    const username = String(new FormData(event.currentTarget).get('username') || '').trim()
    const { error: updateError } = await supabase.from('profiles').update({ username }).eq('id', user!.id)
    if (updateError) setError(updateError.message); else { await refreshProfile(); setStatus('อัปเดตชื่อผู้ใช้เรียบร้อย') }
  }
  const updatePassword = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setStatus(''); setError('')
    const form = new FormData(event.currentTarget), password = String(form.get('password') || ''), confirm = String(form.get('confirm') || '')
    if (password.length < 8) return setError('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร')
    if (password !== confirm) return setError('รหัสผ่านไม่ตรงกัน')
    const { error: updateError } = await supabase.auth.updateUser({ password })
    if (updateError) setError(updateError.message); else { setStatus('เปลี่ยนรหัสผ่านเรียบร้อย'); event.currentTarget.reset() }
  }

  return <section className="content-section page-section settings-page">
    <PageHeader eyebrow="ACCOUNT PREFERENCES" title="USER" accent="SETTINGS">จัดการข้อมูลบัญชีและความปลอดภัย</PageHeader>
    <div className="settings-tabs"><button className={tab === 'profile' ? 'active' : ''} onClick={() => { setTab('profile'); setStatus(''); setError('') }}><UserRound size={17} /> ชื่อผู้ใช้</button><button className={tab === 'password' ? 'active' : ''} onClick={() => { setTab('password'); setStatus(''); setError('') }}><Shield size={17} /> รหัสผ่าน</button></div>
    <div className="settings-card reveal">
      <div className="panel-line" />
      {tab === 'profile' ? <form onSubmit={updateProfile}><div className="form-title"><UserRound /><div><b>เปลี่ยนชื่อผู้ใช้</b><span>PROFILE IDENTITY</span></div></div><div className="current-info"><span>{(profile?.username || 'U')[0]?.toUpperCase()}</span><div><small>บัญชีปัจจุบัน</small><b>{profile?.username}</b><em>{user?.email}</em></div></div><label>ชื่อผู้ใช้ใหม่<input name="username" defaultValue={profile?.username} required minLength={3} maxLength={50} pattern="[A-Za-z0-9ก-๙_.-]{3,50}" /></label><button className="button"><Save size={17} /> บันทึกชื่อผู้ใช้</button></form>
      : <form onSubmit={updatePassword}><div className="form-title"><Shield /><div><b>เปลี่ยนรหัสผ่าน</b><span>SECURITY UPDATE</span></div></div><label>รหัสผ่านใหม่<div className="password-field"><input name="password" type={show ? 'text' : 'password'} required minLength={8} placeholder="อย่างน้อย 8 ตัวอักษร" /><button type="button" onClick={() => setShow(!show)}>{show ? <EyeOff /> : <Eye />}</button></div></label><label>ยืนยันรหัสผ่านใหม่<input name="confirm" type={show ? 'text' : 'password'} required minLength={8} /></label><button className="button"><Shield size={17} /> อัปเดตรหัสผ่าน</button></form>}
      {status && <div className="notice success"><CheckCircle2 size={17} /> {status}</div>}{error && <div className="notice error">{error}</div>}
    </div>
  </section>
}
