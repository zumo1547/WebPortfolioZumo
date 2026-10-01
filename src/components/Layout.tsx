import { Github, LayoutDashboard, LogIn, LogOut, Menu, Settings, X, Zap } from 'lucide-react'
import { useEffect, useState, type ReactNode } from 'react'
import { Link, NavLink, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { assetUrl } from '../lib/supabase'

const links = [
  { to: '/', label: 'Home' },
  { to: '/about', label: 'About' },
  { to: '/projects', label: 'Projects' },
  { to: '/contact', label: 'Contact' },
]

export function Layout({ children }: { children: ReactNode }) {
  const [menuOpen, setMenuOpen] = useState(false)
  const { user, profile, isAdmin, signOut } = useAuth()
  const location = useLocation()

  useEffect(() => {
    if (!menuOpen) return
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => { document.body.style.overflow = previousOverflow }
  }, [menuOpen])

  useEffect(() => { setMenuOpen(false) }, [location.pathname])

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
                {isAdmin && <Link className="icon-button admin" to="/dashboard" aria-label="Dashboard"><LayoutDashboard size={18} /></Link>}
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
              {isAdmin && <Link to="/dashboard" onClick={() => setMenuOpen(false)}>Dashboard <LayoutDashboard size={18} /></Link>}
              <Link to="/settings" onClick={() => setMenuOpen(false)}>Settings <Settings size={18} /></Link>
              <button onClick={() => { void signOut(); setMenuOpen(false) }}>ออกจากระบบ <LogOut size={18} /></button>
            </>
          ) : <Link to="/login" onClick={() => setMenuOpen(false)}>เข้าสู่ระบบ <LogIn size={18} /></Link>}
        </div>
      )}
      <main key={location.pathname}>{children}</main>
      <footer className="site-footer">
        <div><Zap size={15} /> ZUMO DEV PORTFOLIO</div>
        <span>React · TypeScript · Supabase</span>
        <a href="https://github.com/zumo1547" target="_blank" rel="noreferrer"><Github size={17} /> GitHub</a>
      </footer>
    </div>
  )
}
