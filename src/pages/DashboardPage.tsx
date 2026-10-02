import {
  Activity,
  CheckCircle2,
  Eye,
  FileText,
  FolderOpen,
  Mail,
  Pencil,
  Plus,
  RefreshCcw,
  Search,
  ShieldCheck,
  Trash2,
  UserCog,
  Users,
  X,
} from 'lucide-react'
import { useCallback, useEffect, useMemo, useState } from 'react'
import { createPortal } from 'react-dom'
import { PageHeader } from '../components/PageHeader'
import { useAuth } from '../context/AuthContext'
import { projectImageUrl, supabase } from '../lib/supabase'
import { sortProjectsByImportance } from '../lib/projectRanking'
import type { AdminActivity, ContactMessage, Profile, Project, UserRole } from '../types'
import { ProjectEditor, ProjectModal } from './ProjectsPage'
import './DashboardPage.css'

type DashboardTab = 'projects' | 'messages' | 'users' | 'security'
type EditorState = { project: Project | null; deleteArmed?: boolean } | null

const tabs: Array<{ id: DashboardTab; label: string; icon: typeof FolderOpen }> = [
  { id: 'projects', label: 'โปรเจกต์', icon: FolderOpen },
  { id: 'messages', label: 'ข้อความ', icon: Mail },
  { id: 'users', label: 'ผู้ใช้', icon: Users },
  { id: 'security', label: 'ความปลอดภัย', icon: ShieldCheck },
]

const formatDate = (value: string) => new Date(value).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' })

export function DashboardPage() {
  const { user } = useAuth()
  const [projects, setProjects] = useState<Project[]>([])
  const [profiles, setProfiles] = useState<Profile[]>([])
  const [messages, setMessages] = useState<ContactMessage[]>([])
  const [activity, setActivity] = useState<AdminActivity[]>([])
  const [tab, setTab] = useState<DashboardTab>('projects')
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [selectedProject, setSelectedProject] = useState<Project | null>(null)
  const [editor, setEditor] = useState<EditorState>(null)
  const [selectedMessage, setSelectedMessage] = useState<ContactMessage | null>(null)
  const [selectedProfile, setSelectedProfile] = useState<Profile | null>(null)
  const [deleteMessageArmed, setDeleteMessageArmed] = useState(false)
  const [working, setWorking] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    setError('')
    const [projectResult, profileResult, messageResult, activityResult] = await Promise.all([
      supabase.from('projects').select('*').order('created_at', { ascending: false }),
      supabase.from('profiles').select('id, username, role, created_at').order('created_at', { ascending: false }).limit(100),
      supabase.from('contact_messages').select('*').order('created_at', { ascending: false }).limit(100),
      supabase.from('admin_activity').select('*').order('created_at', { ascending: false }).limit(100),
    ])
    setProjects(sortProjectsByImportance((projectResult.data as Project[]) || []))
    setProfiles((profileResult.data as Profile[]) || [])
    setMessages((messageResult.data as ContactMessage[]) || [])
    setActivity((activityResult.data as AdminActivity[]) || [])
    const firstError = projectResult.error || profileResult.error || messageResult.error || activityResult.error
    if (firstError) setError(firstError.message)
    setLoading(false)
  }, [])

  useEffect(() => { void load() }, [load])

  useEffect(() => {
    if (!selectedProject && !editor && !selectedMessage && !selectedProfile) return
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => { document.body.style.overflow = previousOverflow }
  }, [selectedProject, editor, selectedMessage, selectedProfile])

  const normalizedQuery = query.trim().toLocaleLowerCase('th')
  const visibleProjects = useMemo(() => projects.filter((project) => `${project.name} ${project.description} ${project.award_title || ''} ${(project.tags || []).join(' ')}`.toLocaleLowerCase('th').includes(normalizedQuery)), [projects, normalizedQuery])
  const visibleMessages = useMemo(() => messages.filter((message) => `${message.subject} ${message.name} ${message.email} ${message.message}`.toLocaleLowerCase('th').includes(normalizedQuery)), [messages, normalizedQuery])
  const visibleProfiles = useMemo(() => profiles.filter((profile) => `${profile.username} ${profile.id} ${profile.role}`.toLocaleLowerCase('th').includes(normalizedQuery)), [profiles, normalizedQuery])

  const openMessage = async (message: ContactMessage) => {
    setSelectedMessage(message)
    setDeleteMessageArmed(false)
    if (!message.read) {
      const { error: updateError } = await supabase.from('contact_messages').update({ read: true }).eq('id', message.id)
      if (updateError) setError(updateError.message)
      else setMessages((current) => current.map((item) => item.id === message.id ? { ...item, read: true } : item))
    }
  }

  const toggleMessageRead = async () => {
    if (!selectedMessage) return
    setWorking(true)
    const nextRead = !selectedMessage.read
    const { error: updateError } = await supabase.from('contact_messages').update({ read: nextRead }).eq('id', selectedMessage.id)
    if (updateError) setError(updateError.message)
    else {
      const next = { ...selectedMessage, read: nextRead }
      setSelectedMessage(next)
      setMessages((current) => current.map((item) => item.id === next.id ? next : item))
    }
    setWorking(false)
  }

  const deleteMessage = async () => {
    if (!selectedMessage) return
    setWorking(true)
    const { error: deleteError } = await supabase.from('contact_messages').delete().eq('id', selectedMessage.id)
    if (deleteError) setError(deleteError.message)
    else {
      setMessages((current) => current.filter((item) => item.id !== selectedMessage.id))
      setSelectedMessage(null)
      setDeleteMessageArmed(false)
    }
    setWorking(false)
  }

  const changeRole = async (nextRole: UserRole) => {
    if (!selectedProfile) return
    setWorking(true)
    setError('')
    const { error: roleError } = await supabase.rpc('admin_set_user_role', { target_user_id: selectedProfile.id, next_role: nextRole })
    if (roleError) setError(roleError.message)
    else {
      const next = { ...selectedProfile, role: nextRole }
      setSelectedProfile(next)
      setProfiles((current) => current.map((item) => item.id === next.id ? next : item))
      await load()
    }
    setWorking(false)
  }

  const unreadMessages = messages.filter((message) => !message.read).length

  return <section className="content-section page-section dashboard-page">
    <PageHeader eyebrow="ADMIN CONTROL CENTER" title="SYSTEM" accent="DASHBOARD">จัดการโปรเจกต์ ข้อความ ผู้ใช้ และตรวจสอบการทำงานของระบบ</PageHeader>

    <div className="dashboard-topbar">
      <span><span className="online-dot" /> SUPABASE CONNECTED · RLS ACTIVE</span>
      <button className="icon-button" onClick={() => void load()} title="รีเฟรช" disabled={loading}><RefreshCcw className={loading ? 'spin-icon' : ''} size={17} /></button>
    </div>

    {error && <div className="notice error dashboard-notice"><X size={17} />{error}</div>}

    <div className="stats-grid dashboard-stats">
      <button onClick={() => setTab('projects')} className="stat-card"><i className="purple"><FolderOpen /></i><div><b>{loading ? '—' : projects.length}</b><span>PROJECTS</span></div></button>
      <button onClick={() => setTab('messages')} className="stat-card"><i className="blue"><Mail /></i><div><b>{loading ? '—' : messages.length}</b><span>MESSAGES · {unreadMessages} UNREAD</span></div></button>
      <button onClick={() => setTab('users')} className="stat-card"><i className="pink"><Users /></i><div><b>{loading ? '—' : profiles.length}</b><span>USERS</span></div></button>
      <button onClick={() => setTab('security')} className="stat-card"><i className="green"><ShieldCheck /></i><div><b>{profiles.filter((item) => item.role === 'admin').length}</b><span>ADMINS</span></div></button>
    </div>

    <nav className="dashboard-tabs" aria-label="ส่วนจัดการระบบ">
      {tabs.map(({ id, label, icon: Icon }) => <button className={tab === id ? 'active' : ''} onClick={() => { setTab(id); setQuery('') }} key={id}><Icon size={16} />{label}</button>)}
    </nav>

    {tab !== 'security' && <label className="dashboard-search"><Search size={17} /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={`ค้นหา${tabs.find((item) => item.id === tab)?.label || 'ข้อมูล'}...`} />{query && <button onClick={() => setQuery('')} aria-label="ล้างคำค้นหา"><X size={15} /></button>}</label>}

    {tab === 'projects' && <section className="admin-panel">
      <header><div><b>จัดการโปรเจกต์</b><span>VIEW · CREATE · UPDATE · DELETE</span></div><button className="button small" onClick={() => setEditor({ project: null })}><Plus size={16} /> เพิ่มโปรเจกต์</button></header>
      <div className="admin-list">
        {visibleProjects.map((project) => <article className="admin-project-row" key={project.id}>
          <img src={projectImageUrl(project.images?.[0])} alt="" />
          <div><b>{project.name}</b><span>{project.tags?.join(' · ') || 'ไม่มีแท็ก'} · {project.images?.length || 0} รูป</span><small>อัปเดต {formatDate(project.updated_at || project.created_at)}</small></div>
          <div className="admin-row-actions"><button onClick={() => setSelectedProject(project)} title="ดูรายละเอียด"><Eye size={16} /></button><button onClick={() => setEditor({ project })} title="แก้ไข"><Pencil size={16} /></button><button className="danger" onClick={() => setEditor({ project, deleteArmed: true })} title="ลบ"><Trash2 size={16} /></button></div>
        </article>)}
        {!loading && !visibleProjects.length && <p className="panel-empty">ไม่พบข้อมูลโปรเจกต์</p>}
      </div>
    </section>}

    {tab === 'messages' && <section className="admin-panel">
      <header><div><b>ข้อความติดต่อ</b><span>{unreadMessages} ข้อความที่ยังไม่ได้อ่าน</span></div></header>
      <div className="admin-list">
        {visibleMessages.map((message) => <button className={`admin-message-row ${message.read ? '' : 'unread'}`} onClick={() => void openMessage(message)} key={message.id}><span className="row-avatar"><Mail size={15} /></span><div><b>{message.subject}</b><span>{message.name} · {message.email}</span><small>{message.message}</small></div><time>{formatDate(message.created_at)}</time></button>)}
        {!loading && !visibleMessages.length && <p className="panel-empty">ยังไม่มีข้อความ</p>}
      </div>
    </section>}

    {tab === 'users' && <section className="admin-panel">
      <header><div><b>บัญชีผู้ใช้</b><span>ROLE MANAGEMENT · PROFILE DETAILS</span></div></header>
      <div className="admin-user-table">
        <div className="admin-user-head"><span>ผู้ใช้</span><span>User ID</span><span>สิทธิ์</span><span>วันที่สมัคร</span><span /></div>
        {visibleProfiles.map((profile) => <button className="admin-user-row" onClick={() => setSelectedProfile(profile)} key={profile.id}><span><i>{profile.username.slice(0, 1).toUpperCase()}</i><b>{profile.username}</b></span><code>{profile.id}</code><em className={`role ${profile.role}`}>{profile.role}</em><time>{formatDate(profile.created_at)}</time><Eye size={16} /></button>)}
      </div>
    </section>}

    {tab === 'security' && <>
      <section className="security-grid">
        <article><ShieldCheck /><b>Row Level Security</b><span>การเพิ่ม แก้ไข และลบข้อมูลถูกตรวจสิทธิ์ที่ Supabase ไม่ได้อาศัยการซ่อนปุ่มหน้าเว็บ</span><strong>SERVER ENFORCED</strong></article>
        <article><UserCog /><b>Admin RPC</b><span>คำสั่งลบโปรเจกต์และเปลี่ยนสิทธิ์ผู้ใช้ตรวจ role ซ้ำใน PostgreSQL ก่อนทำงาน</span><strong>ADMIN ONLY</strong></article>
        <article><FileText /><b>Audit Log</b><span>บันทึกผู้ทำรายการ ประเภทข้อมูล และเวลาของการเปลี่ยนแปลงสำคัญ</span><strong>{activity.length} EVENTS</strong></article>
        <article><CheckCircle2 /><b>Browser Security</b><span>ใช้ Publishable Key เท่านั้น พร้อม CSP, anti-frame และ content-type headers บน Vercel</span><strong>ACTIVE</strong></article>
      </section>
      <section className="admin-panel audit-panel"><header><div><b>ประวัติการจัดการ</b><span>ADMIN AUDIT LOG</span></div></header><div className="admin-list">{activity.map((item) => <article className="audit-row" key={item.id}><Activity size={16} /><div><b>{item.action} · {item.entity_type}</b><span>{item.entity_id || '—'} · ผู้ทำรายการ {item.actor_id?.slice(0, 8) || 'system'}</span></div><time>{formatDate(item.created_at)}</time></article>)}{!activity.length && <p className="panel-empty">ยังไม่มีประวัติการจัดการ</p>}</div></section>
    </>}

    {selectedProject && createPortal(<div className="projects-page modal-portal"><ProjectModal project={selectedProject} onClose={() => setSelectedProject(null)} /></div>, document.body)}
    {editor && user && createPortal(<div className="projects-page modal-portal"><ProjectEditor project={editor.project} userId={user.id} initialDeleteArmed={editor.deleteArmed} onClose={() => setEditor(null)} onSaved={() => { setEditor(null); void load() }} /></div>, document.body)}

    {selectedMessage && createPortal(<div className="dashboard-modal-backdrop" onMouseDown={() => setSelectedMessage(null)}><article className="dashboard-detail-modal" onMouseDown={(event) => event.stopPropagation()} role="dialog" aria-modal="true" aria-labelledby="message-title"><header><div><span>MESSAGE DETAILS</span><h2 id="message-title">{selectedMessage.subject}</h2></div><button onClick={() => setSelectedMessage(null)} aria-label="ปิด"><X /></button></header><dl><div><dt>ผู้ส่ง</dt><dd>{selectedMessage.name}</dd></div><div><dt>อีเมล</dt><dd><a href={`mailto:${selectedMessage.email}`}>{selectedMessage.email}</a></dd></div><div><dt>วันที่ส่ง</dt><dd>{formatDate(selectedMessage.created_at)}</dd></div><div><dt>สถานะ</dt><dd>{selectedMessage.read ? 'อ่านแล้ว' : 'ยังไม่ได้อ่าน'}</dd></div></dl><div className="message-full-text">{selectedMessage.message}</div><footer>{!deleteMessageArmed ? <><button className="button secondary small" onClick={() => void toggleMessageRead()} disabled={working}>{selectedMessage.read ? 'ทำเป็นยังไม่อ่าน' : 'ทำเป็นอ่านแล้ว'}</button><button className="button danger-button small" onClick={() => setDeleteMessageArmed(true)} disabled={working}><Trash2 size={15} /> ลบข้อความ</button></> : <div className="dashboard-delete-confirm"><span>ยืนยันลบข้อความนี้ถาวร?</span><button className="button secondary small" onClick={() => setDeleteMessageArmed(false)}>ยกเลิก</button><button className="button danger-button small" onClick={() => void deleteMessage()} disabled={working}>ยืนยันลบ</button></div>}</footer></article></div>, document.body)}

    {selectedProfile && createPortal(<div className="dashboard-modal-backdrop" onMouseDown={() => setSelectedProfile(null)}><article className="dashboard-detail-modal user-detail-modal" onMouseDown={(event) => event.stopPropagation()} role="dialog" aria-modal="true" aria-labelledby="user-title"><header><div><span>USER DETAILS</span><h2 id="user-title">{selectedProfile.username}</h2></div><button onClick={() => setSelectedProfile(null)} aria-label="ปิด"><X /></button></header><dl><div><dt>User ID</dt><dd><code>{selectedProfile.id}</code></dd></div><div><dt>สิทธิ์ปัจจุบัน</dt><dd><em className={`role ${selectedProfile.role}`}>{selectedProfile.role}</em></dd></div><div><dt>วันที่สมัคร</dt><dd>{formatDate(selectedProfile.created_at)}</dd></div></dl><div className="role-management"><UserCog /><div><b>จัดการสิทธิ์</b><span>การเปลี่ยนสิทธิ์ตรวจสอบซ้ำด้วย Admin RPC ที่ฐานข้อมูล</span></div><button className="button secondary small" disabled={working || selectedProfile.id === user?.id} onClick={() => void changeRole(selectedProfile.role === 'admin' ? 'user' : 'admin')}>{selectedProfile.role === 'admin' ? 'ลดเป็น User' : 'ตั้งเป็น Admin'}</button></div>{selectedProfile.id === user?.id && <p className="self-role-note">ไม่อนุญาตให้เปลี่ยนสิทธิ์บัญชีที่กำลังใช้งาน เพื่อป้องกันการล็อกตัวเองออกจากระบบ</p>}</article></div>, document.body)}
  </section>
}
