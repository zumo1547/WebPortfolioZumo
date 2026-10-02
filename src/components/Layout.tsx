import { Bell, Github, LayoutDashboard, LogIn, LogOut, Menu, Settings, X, Zap } from 'lucide-react'
import { useEffect, useState, type ReactNode } from 'react'
import { Link, NavLink, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { assetUrl, supabase } from '../lib/supabase'
import type { ContactMessage } from '../types'
import './Layout.css'

const links = [
  { to: '/', label: 'Home' },
  { to: '/about', label: 'About' },
  { to: '/projects', label: 'Projects' },
  { to: '/contact', label: 'Contact' },
]

export function Layout({ children }: { children: ReactNode }) {
  const [menuOpen, setMenuOpen] = useState(false)
  const [unreadMessages, setUnreadMessages] = useState(0)
  const [newMessage, setNewMessage] = useState<ContactMessage | null>(null)
  const { user, profile, isAdmin, signOut } = useAuth()
  const location = useLocation()

  useEffect(() => {
    if (!menuOpen) return
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => { document.body.style.overflow = previousOverflow }
  }, [menuOpen])

  useEffect(() => { setMenuOpen(false) }, [location.pathname])

  useEffect(() => {
    if (!isAdmin) {
      setUnreadMessages(0)
      setNewMessage(null)
      return
    }

    const refreshUnread = async () => {
      const { count } = await supabase.from('contact_messages').select('id', { count: 'exact', head: true }).eq('read', false)
      setUnreadMessages(count || 0)
    }
    void refreshUnread()

    const channel = supabase
      .channel('admin-contact-notifications')
      .on('postgres_changes', { event: '*', schema: 'public', table: 'contact_messages' }, (payload) => {
        void refreshUnread()
        if (payload.eventType === 'INSERT') setNewMessage(payload.new as ContactMessage)
      })
      .subscribe()

    return () => { void supabase.removeChannel(channel) }
  }, [isAdmin])

  useEffect(() => {
    if (!newMessage) return
    const timer = window.setTimeout(() => setNewMessage(null), 7000)
    return () => window.clearTimeout(timer)
  }, [newMessage])

  return (
    <div className="app-shell">
      <div className="ambient ambient-one" />
      <div className="ambient ambient-two" />
      <header className="site-header">
        <div className="rainbow-line" />
        <nav className="nav-wrap" aria-label="เมนูหลัก">
          <Link to="/" className="brand" onClick={() => setMenuOpen(false)}>
            <span className="brand-mark"><img src={assetUrl('assets/Icon portfolio.png')} alt="" /></span>
            <span><strong>Portfolio</strong><small>ZUMO.DEV</small></span>
          </Link>
          <div className="desktop-nav">
            {links.map((link) => (
              <NavLink key={link.to} to={link.to} end={link.to === '/'}>{link.label}</NavLink>
            ))}
          </div>
          <div className="nav-actions">
            {user ? (
              <>
                <Link className="user-chip" to="/settings">
                  <span>{(profile?.username || user.email || 'U').slice(0, 1).toUpperCase()}</span>
                  <b>{profile?.username || user.email?.split('@')[0]}</b>
                </Link>
                {isAdmin && <Link className="icon-button admin admin-dashboard-link" to="/dashboard" aria-label={`Dashboard${unreadMessages ? ` มีข้อความใหม่ ${unreadMessages} ข้อความ` : ''}`}><LayoutDashboard size={18} />{unreadMessages > 0 && <span className="admin-unread-badge">{unreadMessages > 99 ? '99+' : unreadMessages}</span>}</Link>}
                <Link className="icon-button" to="/settings" aria-label="Settings"><Settings size={18} /></Link>
                <button className="icon-button danger" onClick={() => void signOut()} aria-label="Logout"><LogOut size={18} /></button>
              </>
            ) : (
              <Link className="button small secondary" to="/login"><LogIn size={17} /> เข้าสู่ระบบ</Link>
            )}
          </div>
          <button className="menu-button" onClick={() => setMenuOpen(!menuOpen)} aria-expanded={menuOpen} aria-label="เปิดเมนู">
            {menuOpen ? <X /> : <Menu />}
          </button>
        </nav>
      </header>
      {menuOpen && (
        <div className="mobile-menu" role="dialog" aria-modal="true" aria-label="เมนูหลัก">
          <div className="mobile-menu-head"><span className="online-dot" /> NAVIGATION</div>
          {links.map((link) => (
            <NavLink key={link.to} to={link.to} end={link.to === '/'} onClick={() => setMenuOpen(false)}>{link.label}<span>→</span></NavLink>
          ))}
          {user ? (
            <>
              {isAdmin && <Link to="/dashboard" onClick={() => setMenuOpen(false)}>Dashboard <span className="mobile-admin-status"><LayoutDashboard size={18} />{unreadMessages > 0 && <b>{unreadMessages}</b>}</span></Link>}
              <Link to="/settings" onClick={() => setMenuOpen(false)}>Settings <Settings size={18} /></Link>
              <button onClick={() => { void signOut(); setMenuOpen(false) }}>ออกจากระบบ <LogOut size={18} /></button>
            </>
          ) : <Link to="/login" onClick={() => setMenuOpen(false)}>เข้าสู่ระบบ <LogIn size={18} /></Link>}
        </div>
      )}
      {isAdmin && newMessage && <aside className="admin-message-toast" role="status" aria-live="polite">
        <Bell size={19} />
        <Link to="/dashboard" onClick={() => setNewMessage(null)}><span>มีข้อความใหม่จากหน้า Contact</span><b>{newMessage.name}</b><small>{newMessage.subject}</small></Link>
        <button type="button" onClick={() => setNewMessage(null)} aria-label="ปิดการแจ้งเตือน"><X size={16} /></button>
      </aside>}
      <main key={location.pathname}>{children}</main>
      <footer className="site-footer">
        <div><Zap size={15} /> ZUMO DEV PORTFOLIO</div>
        <span>React · TypeScript · Supabase</span>
        <a href="https://github.com/zumo1547" target="_blank" rel="noreferrer"><Github size={17} /> GitHub</a>
      </footer>
    </div>
  )
}
