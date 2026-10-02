import { CheckCircle2, Copy, Facebook, Github, Instagram, Mail, MessageCircle, Send } from 'lucide-react'
import { useRef, useState, type FormEvent } from 'react'
import { PageHeader } from '../components/PageHeader'
import { Seo } from '../components/Seo'
import './ContactPage.css'

const socials = [
  { icon: <Instagram />, name: 'Instagram', value: '@zumo_1547', href: 'https://www.instagram.com/zumo_1547/', color: 'pink' },
  { icon: <Facebook />, name: 'Facebook', value: 'Wutthipat Sriyangnok', href: 'https://www.facebook.com/profile.php?id=100020911959223', color: 'blue' },
  { icon: <Github />, name: 'GitHub', value: 'zumo1547', href: 'https://github.com/zumo1547', color: 'neutral' },
]

export function ContactPage() {
  const [status, setStatus] = useState<'idle' | 'sending' | 'sent' | 'error'>('idle')
  const [errorMessage, setErrorMessage] = useState('')
  const startedAt = useRef(Date.now())
  const email = 'pakkawan.zumo@gmail.com'

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setStatus('sending')
    const form = new FormData(event.currentTarget)
    setErrorMessage('')
    try {
      const response = await fetch('/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: String(form.get('name') || ''),
          email: String(form.get('email') || ''),
          subject: String(form.get('subject') || ''),
          message: String(form.get('message') || ''),
          website: String(form.get('website') || ''),
          startedAt: startedAt.current,
        }),
      })
      const result = await response.json().catch(() => ({})) as { error?: string }
      if (!response.ok) throw new Error(result.error || 'ส่งข้อความไม่สำเร็จ')
      setStatus('sent')
      event.currentTarget.reset()
      startedAt.current = Date.now()
    } catch (caught) {
      setErrorMessage(caught instanceof Error ? caught.message : 'ส่งข้อความไม่สำเร็จ')
      setStatus('error')
    }
  }

  return (
    <section className="content-section page-section">
      <Seo title="Contact — Wutthipat Sriyangnok" description="ช่องทางติดต่อ Wutthipat Sriyangnok สำหรับพูดคุยเกี่ยวกับโปรเจกต์ เทคโนโลยี และการศึกษา" path="/contact" />
      <PageHeader eyebrow="OPEN COMMUNICATION CHANNEL" title="CONTACT" accent="ME">ติดต่อ พูดคุย หรือชวนกันสร้างโปรเจกต์ใหม่</PageHeader>
      <div className="contact-grid">
        <div className="signal-panel reveal">
          <div className="panel-line" />
          <div className="contact-intro"><span className="online-dot" /><b>ONLINE</b><p>เลือกช่องทางที่สะดวก แล้วมาคุยกันครับ</p></div>
          {socials.map((social) => <a className={`social-card ${social.color}`} href={social.href} target="_blank" rel="noreferrer" key={social.name}><i>{social.icon}</i><div><b>{social.name}</b><span>{social.value}</span></div><em>↗</em></a>)}
          <div className="email-copy"><Mail size={18} /><span>{email}</span><button onClick={() => void navigator.clipboard.writeText(email)} title="คัดลอกอีเมล"><Copy size={17} /></button></div>
        </div>
        <form className="contact-form reveal delay-1" onSubmit={submit}>
          <div className="form-title"><MessageCircle /><div><b>ส่งข้อความ</b><span>MESSAGE TERMINAL</span></div></div>
          <label>ชื่อ<input name="name" required maxLength={80} placeholder="ชื่อของคุณ" /></label>
          <label>อีเมล<input name="email" type="email" required maxLength={254} placeholder="you@example.com" /></label>
          <label>หัวข้อ<input name="subject" required maxLength={120} placeholder="เรื่องที่ต้องการติดต่อ" /></label>
          <label>ข้อความ<textarea name="message" required maxLength={2000} rows={6} placeholder="เขียนข้อความของคุณที่นี่..." /></label>
          <label className="contact-honeypot" aria-hidden="true">เว็บไซต์<input name="website" tabIndex={-1} autoComplete="off" /></label>
          <button className="button" disabled={status === 'sending'}>{status === 'sending' ? 'กำลังส่ง...' : <><Send size={17} /> ส่งข้อความ</>}</button>
          {status === 'sent' && <div className="notice success"><CheckCircle2 size={18} /> ส่งข้อความเรียบร้อยแล้ว</div>}
          {status === 'error' && <div className="notice error">{errorMessage || 'ส่งไม่สำเร็จ กรุณาลองใหม่หรือติดต่อทางอีเมล'}</div>}
        </form>
      </div>
    </section>
  )
}
