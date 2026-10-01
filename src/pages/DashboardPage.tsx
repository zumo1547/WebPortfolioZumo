import { FolderOpen, Mail, RefreshCcw, ShieldCheck, Users } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { PageHeader } from '../components/PageHeader'
import { supabase } from '../lib/supabase'
import type { Profile, Project } from '../types'

interface ContactMessage { id: number; name: string; email: string; subject: string; message: string; created_at: string; read: boolean }

export function DashboardPage() {
  const [projects, setProjects] = useState<Project[]>([])
  const [profiles, setProfiles] = useState<Profile[]>([])
  const [messages, setMessages] = useState<ContactMessage[]>([])
  const [loading, setLoading] = useState(true)
  const load = async () => {
    setLoading(true)
    const [projectResult, profileResult, messageResult] = await Promise.all([
      supabase.from('projects').select('*').order('created_at', { ascending: false }),
      supabase.from('profiles').select('*').order('created_at', { ascending: false }).limit(20),
      supabase.from('contact_messages').select('*').order('created_at', { ascending: false }).limit(20),
    ])
    setProjects((projectResult.data as Project[]) || [])
    setProfiles((profileResult.data as Profile[]) || [])
    setMessages((messageResult.data as ContactMessage[]) || [])
    setLoading(false)
  }
  useEffect(() => { void load() }, [])
  const markRead = async (id: number) => { await supabase.from('contact_messages').update({ read: true }).eq('id', id); void load() }

  return <section className="content-section page-section dashboard-page">
    <PageHeader eyebrow="ADMIN CONTROL CENTER" title="SYSTEM" accent="DASHBOARD">ภาพรวม Portfolio และข้อมูลจาก Supabase แบบ Real-time</PageHeader>
    <div className="dashboard-actions"><span><span className="online-dot" /> DATABASE ONLINE</span><button className="icon-button" onClick={() => void load()} title="รีเฟรช"><RefreshCcw size={17} /></button></div>
    <div className="stats-grid">
      <div className="stat-card"><i className="purple"><FolderOpen /></i><div><b>{loading ? '—' : projects.length}</b><span>PROJECTS</span></div></div>
      <div className="stat-card"><i className="pink"><Users /></i><div><b>{loading ? '—' : profiles.length}</b><span>USERS</span></div></div>
      <div className="stat-card"><i className="blue"><Mail /></i><div><b>{loading ? '—' : messages.length}</b><span>MESSAGES</span></div></div>
      <div className="stat-card"><i className="green"><ShieldCheck /></i><div><b>{profiles.filter((item) => item.role === 'admin').length}</b><span>ADMINS</span></div></div>
    </div>
    <div className="dashboard-grid">
      <section className="dash-panel"><header><div><b>โปรเจกต์ล่าสุด</b><span>RECENT PROJECTS</span></div><Link to="/projects">จัดการ →</Link></header>{projects.length ? projects.slice(0, 6).map((project) => <div className="dash-row" key={project.id}><span className="row-avatar">{project.name.slice(0, 1)}</span><div><b>{project.name}</b><span>{project.tags?.join(' · ') || 'PROJECT'}</span></div><time>{new Date(project.created_at).toLocaleDateString('th-TH')}</time></div>) : <p className="panel-empty">ยังไม่มีข้อมูลโปรเจกต์</p>}</section>
      <section className="dash-panel"><header><div><b>ข้อความติดต่อ</b><span>INBOX</span></div></header>{messages.length ? messages.slice(0, 6).map((message) => <button className={`dash-row message-row ${message.read ? '' : 'unread'}`} onClick={() => void markRead(message.id)} key={message.id}><span className="row-avatar"><Mail size={15} /></span><div><b>{message.subject}</b><span>{message.name} · {message.email}</span></div><time>{new Date(message.created_at).toLocaleDateString('th-TH')}</time></button>) : <p className="panel-empty">ยังไม่มีข้อความใหม่</p>}</section>
    </div>
    <section className="dash-panel user-panel"><header><div><b>ผู้ใช้ล่าสุด</b><span>SUPABASE AUTH PROFILES</span></div></header><div className="user-table"><div className="table-head"><span>ผู้ใช้</span><span>อีเมล/ID</span><span>สิทธิ์</span><span>วันที่สมัคร</span></div>{profiles.map((profile) => <div className="table-row" key={profile.id}><span><i>{profile.username.slice(0, 1).toUpperCase()}</i>{profile.username}</span><code>{profile.id.slice(0, 12)}…</code><b className={`role ${profile.role}`}>{profile.role}</b><time>{new Date(profile.created_at).toLocaleDateString('th-TH')}</time></div>)}</div></section>
  </section>
}
