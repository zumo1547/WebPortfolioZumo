import { ExternalLink, Github, Images, Pencil, Plus, Search, Trash2, Upload, X, Youtube } from 'lucide-react'
import { useEffect, useMemo, useState, type FormEvent } from 'react'
import { PageHeader } from '../components/PageHeader'
import { useAuth } from '../context/AuthContext'
import { fallbackProjects, projectTags } from '../data'
import { projectImageUrl, supabase } from '../lib/supabase'
import type { Project, ProjectInput, ProjectLinks } from '../types'

const emptyInput: ProjectInput = { name: '', description: '', images: [], tags: ['งานในโรงเรียน'], links: {} }

function LinkButtons({ links }: { links: ProjectLinks }) {
  return <div className="project-links">
    {links.github && <a href={links.github} target="_blank" rel="noreferrer" aria-label="GitHub"><Github size={16} /></a>}
    {links.youtube && <a href={links.youtube} target="_blank" rel="noreferrer" aria-label="YouTube"><Youtube size={16} /></a>}
    {links.drive && <a href={links.drive} target="_blank" rel="noreferrer" aria-label="Google Drive"><ExternalLink size={16} /></a>}
  </div>
}

export function ProjectsPage() {
  const { user, isAdmin } = useAuth()
  const [projects, setProjects] = useState<Project[]>([])
  const [loading, setLoading] = useState(true)
  const [query, setQuery] = useState('')
  const [activeTags, setActiveTags] = useState<string[]>([])
  const [selected, setSelected] = useState<Project | null>(null)
  const [editing, setEditing] = useState<Project | null | 'new'>(null)

  const loadProjects = async () => {
    setLoading(true)
    const { data, error } = await supabase.from('projects').select('*').order('created_at', { ascending: false })
    setProjects(!error && data?.length ? data as Project[] : fallbackProjects)
    setLoading(false)
  }

  useEffect(() => { void loadProjects() }, [])

  const visible = useMemo(() => projects.filter((project) => {
    const matchesQuery = `${project.name} ${project.description}`.toLocaleLowerCase().includes(query.toLocaleLowerCase())
    const matchesTags = !activeTags.length || activeTags.every((tag) => project.tags?.includes(tag))
    return matchesQuery && matchesTags
  }), [projects, query, activeTags])

  const toggleTag = (tag: string) => setActiveTags((current) => current.includes(tag) ? current.filter((item) => item !== tag) : [...current, tag])

  return (
    <section className="content-section page-section">
      <PageHeader eyebrow="PROJECT ARCHIVE" title="MY" accent="PROJECTS">ผลงานด้าน Game, IoT, Robotics, AI และ Web Development</PageHeader>
      <div className="project-toolbar reveal">
        <div className="search-box"><Search size={18} /><input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="ค้นหาชื่อหรือรายละเอียดโปรเจกต์" /></div>
        {isAdmin && <button className="button small" onClick={() => setEditing('new')}><Plus size={17} /> เพิ่มโปรเจกต์</button>}
      </div>
      <div className="tag-filters reveal">
        {projectTags.map((tag) => <button className={activeTags.includes(tag) ? 'active' : ''} onClick={() => toggleTag(tag)} key={tag}>{tag}</button>)}
        {!!activeTags.length && <button className="clear" onClick={() => setActiveTags([])}><X size={14} /> ล้าง</button>}
      </div>
      <div className="result-count">{loading ? 'กำลังโหลด...' : `แสดง ${visible.length} โปรเจกต์`}</div>
      <div className="project-grid">
        {visible.map((project, index) => (
          <article className="project-card reveal" style={{ animationDelay: `${Math.min(index, 8) * 50}ms` }} key={project.id} onClick={() => setSelected(project)}>
            <div className="project-cover">
              <img src={projectImageUrl(project.images?.[0])} alt={project.name} loading="lazy" />
              {project.images?.length > 1 && <span className="image-count"><Images size={14} /> {project.images.length}</span>}
              <LinkButtons links={project.links || {}} />
            </div>
            <div className="project-body">
              <div className="project-tags">{(project.tags || []).map((tag) => <span key={tag}>{tag}</span>)}</div>
              <h2>{project.name}</h2><p>{project.description}</p>
              {isAdmin && project.id > 0 && <button className="edit-pill" onClick={(event) => { event.stopPropagation(); setEditing(project) }}><Pencil size={13} /> Edit</button>}
            </div>
          </article>
        ))}
      </div>
      {!loading && !visible.length && <div className="empty-state">ไม่พบโปรเจกต์ที่ค้นหา</div>}

      {selected && <ProjectModal project={selected} onClose={() => setSelected(null)} />}
      {editing && isAdmin && user && <ProjectEditor project={editing === 'new' ? null : editing} userId={user.id} onClose={() => setEditing(null)} onSaved={() => { setEditing(null); void loadProjects() }} />}
    </section>
  )
}

function ProjectModal({ project, onClose }: { project: Project; onClose: () => void }) {
  const [imageIndex, setImageIndex] = useState(0)
  const images = project.images?.length ? project.images : ['/assets/Icon portfolio.png']
  return <div className="modal-backdrop" onMouseDown={onClose} role="presentation">
    <article className="project-modal" onMouseDown={(e) => e.stopPropagation()}>
      <button className="modal-close" onClick={onClose}><X /></button>
      <div className="modal-image"><img src={projectImageUrl(images[imageIndex])} alt={project.name} /></div>
      {images.length > 1 && <div className="thumb-row">{images.map((image, index) => <button className={index === imageIndex ? 'active' : ''} onClick={() => setImageIndex(index)} key={image}><img src={projectImageUrl(image)} alt="" /></button>)}</div>}
      <div className="modal-content"><div className="project-tags">{project.tags?.map((tag) => <span key={tag}>{tag}</span>)}</div><h2>{project.name}</h2><p>{project.description}</p><LinkButtons links={project.links || {}} /></div>
    </article>
  </div>
}

function ProjectEditor({ project, userId, onClose, onSaved }: { project: Project | null; userId: string; onClose: () => void; onSaved: () => void }) {
  const [value, setValue] = useState<ProjectInput>(project ? { name: project.name, description: project.description, tags: project.tags || [], links: project.links || {}, images: project.images || [] } : emptyInput)
  const [files, setFiles] = useState<File[]>([])
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setSaving(true); setError('')
    try {
      const uploaded: string[] = []
      for (const file of files.slice(0, Math.max(0, 5 - value.images.length))) {
        if (!file.type.startsWith('image/') || file.size > 20 * 1024 * 1024) throw new Error('รองรับไฟล์ภาพไม่เกิน 20 MB ต่อไฟล์')
        const ext = file.name.split('.').pop()?.toLowerCase() || 'webp'
        const path = `${userId}/${crypto.randomUUID()}.${ext}`
        const { error: uploadError } = await supabase.storage.from('project-images').upload(path, file, { contentType: file.type })
        if (uploadError) throw uploadError
        uploaded.push(path)
      }
      const payload = { ...value, images: [...value.images, ...uploaded], updated_at: new Date().toISOString() }
      const result = project
        ? await supabase.from('projects').update(payload).eq('id', project.id)
        : await supabase.from('projects').insert(payload)
      if (result.error) throw result.error
      onSaved()
    } catch (caught) { setError(caught instanceof Error ? caught.message : 'บันทึกไม่สำเร็จ') }
    finally { setSaving(false) }
  }

  const remove = async () => {
    if (!project || !confirm(`ลบโปรเจกต์ “${project.name}” ใช่หรือไม่?`)) return
    setSaving(true)
    const storagePaths = project.images.filter((path) => !path.startsWith('/') && !/^https?:/i.test(path))
    if (storagePaths.length) await supabase.storage.from('project-images').remove(storagePaths)
    const { error: deleteError } = await supabase.from('projects').delete().eq('id', project.id)
    setSaving(false)
    if (deleteError) setError(deleteError.message); else onSaved()
  }

  return <div className="modal-backdrop" onMouseDown={onClose} role="presentation">
    <form className="editor-modal" onSubmit={submit} onMouseDown={(e) => e.stopPropagation()}>
      <button type="button" className="modal-close" onClick={onClose}><X /></button>
      <div className="form-title"><Upload /><div><b>{project ? 'แก้ไขโปรเจกต์' : 'เพิ่มโปรเจกต์'}</b><span>PROJECT EDITOR</span></div></div>
      <label>ชื่อโปรเจกต์<input required maxLength={150} value={value.name} onChange={(e) => setValue({ ...value, name: e.target.value })} /></label>
      <label>รายละเอียด<textarea required maxLength={3000} rows={5} value={value.description} onChange={(e) => setValue({ ...value, description: e.target.value })} /></label>
      <fieldset><legend>Tags</legend><div className="editor-tags">{projectTags.map((tag) => <label key={tag}><input type="checkbox" checked={value.tags.includes(tag)} onChange={() => setValue({ ...value, tags: value.tags.includes(tag) ? value.tags.filter((item) => item !== tag) : [...value.tags, tag] })} /> {tag}</label>)}</div></fieldset>
      <div className="three-fields"><label>GitHub<input type="url" value={value.links.github || ''} onChange={(e) => setValue({ ...value, links: { ...value.links, github: e.target.value } })} /></label><label>YouTube<input type="url" value={value.links.youtube || ''} onChange={(e) => setValue({ ...value, links: { ...value.links, youtube: e.target.value } })} /></label><label>Google Drive<input type="url" value={value.links.drive || ''} onChange={(e) => setValue({ ...value, links: { ...value.links, drive: e.target.value } })} /></label></div>
      {!!value.images.length && <div className="existing-images">{value.images.map((image) => <button type="button" onClick={() => setValue({ ...value, images: value.images.filter((item) => item !== image) })} key={image}><img src={projectImageUrl(image)} alt="" /><X size={14} /></button>)}</div>}
      <label>รูปภาพ (รวมสูงสุด 5 รูป)<input type="file" accept="image/*" multiple onChange={(e) => setFiles(Array.from(e.target.files || []).slice(0, 5))} /></label>
      {error && <div className="notice error">{error}</div>}
      <div className="editor-actions">{project && <button type="button" className="button danger-button" onClick={() => void remove()} disabled={saving}><Trash2 size={17} /> ลบ</button>}<button className="button" disabled={saving}>{saving ? 'กำลังบันทึก...' : 'บันทึกโปรเจกต์'}</button></div>
    </form>
  </div>
}
