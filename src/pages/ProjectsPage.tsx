import {
  ChevronLeft,
  ChevronRight,
  ExternalLink,
  Github,
  Globe2,
  ImagePlus,
  Images,
  Landmark,
  Link2,
  Pencil,
  Plus,
  Search,
  School,
  Tag,
  Target,
  Trash2,
  Trophy,
  Upload,
  X,
  Youtube,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { useEffect, useMemo, useState, type FormEvent, type MouseEvent } from 'react'
import { createPortal } from 'react-dom'
import { PageHeader } from '../components/PageHeader'
import { useAuth } from '../context/AuthContext'
import { fallbackProjects, projectTags } from '../data'
import { MAX_PROJECT_IMAGE_BYTES, optimizeProjectImage, PROJECT_IMAGE_ACCEPT } from '../lib/imageOptimization'
import { projectImageUrl, supabase } from '../lib/supabase'
import type { Project, ProjectInput, ProjectLinks } from '../types'
import './ProjectsPage.css'

const emptyInput: ProjectInput = { name: '', description: '', images: [], tags: ['งานในโรงเรียน'], links: {} }

const tagMeta: Record<string, { icon: LucideIcon; tone: string }> = {
  'ระดับประเทศ': { icon: Trophy, tone: 'national' },
  'ระดับนานาชาติ': { icon: Globe2, tone: 'international' },
  'ระดับจังหวัด': { icon: Landmark, tone: 'provincial' },
  'งานในโรงเรียน': { icon: School, tone: 'school' },
  'เข้าร่วมกิจกรรม': { icon: Target, tone: 'activity' },
}

const safeExternalUrl = (value?: string) => {
  if (!value) return null
  try {
    const parsed = new URL(value)
    return parsed.protocol === 'https:' || parsed.protocol === 'http:' ? parsed.toString() : null
  } catch {
    return null
  }
}

function TagBadges({ tags }: { tags: string[] }) {
  return <div className="project-tags">
    {tags.map((tag) => {
      const meta = tagMeta[tag] || { icon: Tag, tone: 'default' }
      const Icon = meta.icon
      return <span className={`project-tag tag-${meta.tone}`} key={tag}><Icon size={12} aria-hidden="true" />{tag}</span>
    })}
  </div>
}

function LinkButtons({ links, compact = false, onClick }: { links: ProjectLinks; compact?: boolean; onClick?: (event: MouseEvent) => void }) {
  const github = safeExternalUrl(links.github)
  const youtube = safeExternalUrl(links.youtube)
  const drive = safeExternalUrl(links.drive)
  if (!github && !youtube && !drive) return null

  return <div className={`project-links ${compact ? 'compact' : ''}`} onClick={onClick}>
    {github && <a className="link-github" href={github} target="_blank" rel="noreferrer" aria-label="เปิด GitHub"><Github size={compact ? 15 : 17} /></a>}
    {youtube && <a className="link-youtube" href={youtube} target="_blank" rel="noreferrer" aria-label="เปิด YouTube"><Youtube size={compact ? 15 : 17} /></a>}
    {drive && <a className="link-drive" href={drive} target="_blank" rel="noreferrer" aria-label="เปิด Google Drive"><ExternalLink size={compact ? 15 : 17} /></a>}
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
  const [deleting, setDeleting] = useState<Project | null>(null)

  const loadProjects = async () => {
    setLoading(true)
    const { data, error } = await supabase.from('projects').select('*').order('created_at', { ascending: false })
    setProjects(error ? fallbackProjects : (data as Project[] || []))
    setLoading(false)
  }

  useEffect(() => { void loadProjects() }, [])

  useEffect(() => {
    if (!selected && !editing && !deleting) return
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key !== 'Escape') return
      if (editing) setEditing(null)
      else if (deleting) setDeleting(null)
      else setSelected(null)
    }
    document.addEventListener('keydown', closeOnEscape)
    return () => {
      document.body.style.overflow = previousOverflow
      document.removeEventListener('keydown', closeOnEscape)
    }
  }, [selected, editing, deleting])

  const normalizedQuery = query.trim().toLocaleLowerCase('th')
  const visible = useMemo(() => projects.filter((project) => {
    const searchable = `${project.name} ${project.description} ${(project.tags || []).join(' ')}`.toLocaleLowerCase('th')
    const matchesQuery = !normalizedQuery || searchable.includes(normalizedQuery)
    const matchesTags = !activeTags.length || activeTags.some((tag) => project.tags?.includes(tag))
    return matchesQuery && matchesTags
  }), [projects, normalizedQuery, activeTags])

  const toggleTag = (tag: string) => setActiveTags((current) => current.includes(tag) ? current.filter((item) => item !== tag) : [...current, tag])
  const clearFilters = () => { setQuery(''); setActiveTags([]) }

  return (
    <section className="content-section page-section projects-page">
      <PageHeader eyebrow="PROJECT ARCHIVE" title="MY" accent="PROJECTS">ผลงานด้าน Game, IoT, Robotics, AI และ Web Development</PageHeader>

      <div className="project-controls reveal">
        <div className="project-toolbar">
          <label className="search-box" aria-label="ค้นหาโปรเจกต์">
            <Search size={19} />
            <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="ค้นหาชื่อ รายละเอียด หรือแท็กโปรเจกต์" />
            {query && <button type="button" onClick={() => setQuery('')} aria-label="ล้างคำค้นหา"><X size={16} /></button>}
          </label>
          {isAdmin && <button className="button small add-project-button" onClick={() => setEditing('new')}><Plus size={17} /> เพิ่มโปรเจกต์</button>}
        </div>

        <div className="tag-filters" aria-label="กรองตามระดับผลงาน">
          {projectTags.map((tag) => {
            const meta = tagMeta[tag]
            const Icon = meta.icon
            const active = activeTags.includes(tag)
            return <button className={`tag-filter tag-${meta.tone} ${active ? 'active' : ''}`} aria-pressed={active} onClick={() => toggleTag(tag)} key={tag}><Icon size={14} aria-hidden="true" />{tag}</button>
          })}
          {(activeTags.length > 0 || query) && <button className="clear-filter" onClick={clearFilters}><X size={14} /> ล้างตัวกรอง</button>}
        </div>
        <div className="result-count" aria-live="polite">{loading ? 'กำลังโหลดผลงาน...' : `แสดง ${visible.length} จาก ${projects.length} โปรเจกต์`}</div>
      </div>

      {loading ? <div className="project-grid project-skeleton-grid" aria-label="กำลังโหลด">
        {Array.from({ length: 8 }, (_, index) => <div className="project-skeleton" key={index}><i /><span /><b /></div>)}
      </div> : <div className="project-grid">
        {visible.map((project, index) => (
          <article className="project-card reveal" style={{ animationDelay: `${Math.min(index, 8) * 45}ms` }} key={project.id}>
            <button className="project-card-open" onClick={() => setSelected(project)} aria-label={`เปิดรายละเอียด ${project.name}`}>
              <div className="project-cover">
                <img src={projectImageUrl(project.images?.[0])} alt={project.name} loading="lazy" />
                <span className="project-cover-shade" />
                {project.images?.length > 1 && <span className="image-count"><Images size={14} /> {project.images.length}</span>}
              </div>
              <div className="project-body">
                <TagBadges tags={project.tags || []} />
                <h2>{project.name}</h2>
                <p>{project.description}</p>
              </div>
            </button>
            <div className="project-card-actions">
              <LinkButtons compact links={project.links || {}} onClick={(event) => event.stopPropagation()} />
              {isAdmin && project.id > 0 && <><button className="edit-pill" onClick={(event) => { event.stopPropagation(); setSelected(null); setEditing(project) }}><Pencil size={14} /> แก้ไข</button><button className="delete-pill" onClick={(event) => { event.stopPropagation(); setSelected(null); setDeleting(project) }} aria-label={`ลบ ${project.name}`} title="ลบโปรเจกต์"><Trash2 size={14} /></button></>}
            </div>
          </article>
        ))}
      </div>}

      {!loading && !visible.length && <div className="empty-state"><Search size={32} /><h2>ไม่พบโปรเจกต์ที่ค้นหา</h2><p>ลองเปลี่ยนคำค้นหาหรือเลือกแท็กอื่น</p><button className="button secondary small" onClick={clearFilters}>ล้างตัวกรอง</button></div>}

      {selected && createPortal(<div className="projects-page modal-portal"><ProjectModal project={selected} onClose={() => setSelected(null)} /></div>, document.body)}
      {editing && isAdmin && user && createPortal(<div className="projects-page modal-portal"><ProjectEditor project={editing === 'new' ? null : editing} userId={user.id} onClose={() => setEditing(null)} onSaved={() => { setEditing(null); void loadProjects() }} /></div>, document.body)}
      {deleting && isAdmin && user && createPortal(<div className="projects-page modal-portal"><ProjectEditor project={deleting} userId={user.id} initialDeleteArmed onClose={() => setDeleting(null)} onSaved={() => { setDeleting(null); void loadProjects() }} /></div>, document.body)}
    </section>
  )
}

export function ProjectModal({ project, onClose }: { project: Project; onClose: () => void }) {
  const [imageIndex, setImageIndex] = useState(0)
  const images = project.images?.length ? project.images : ['/assets/Icon portfolio.png']
  const selectImage = (offset: number) => setImageIndex((current) => (current + offset + images.length) % images.length)

  useEffect(() => {
    const changeImage = (event: KeyboardEvent) => {
      if (event.key === 'ArrowRight') setImageIndex((current) => (current + 1) % images.length)
      if (event.key === 'ArrowLeft') setImageIndex((current) => (current - 1 + images.length) % images.length)
    }
    document.addEventListener('keydown', changeImage)
    return () => document.removeEventListener('keydown', changeImage)
  }, [images.length])

  return <div className="modal-backdrop" onMouseDown={onClose} role="presentation">
    <article className="project-modal" role="dialog" aria-modal="true" aria-labelledby="project-modal-title" onMouseDown={(event) => event.stopPropagation()}>
      <header className="modal-toolbar">
        <div><span>PROJECT DETAILS</span><strong>รายละเอียดผลงาน</strong></div>
        <div className="modal-toolbar-actions">
          <LinkButtons compact links={project.links || {}} />
          <button className="modal-close" onClick={onClose} aria-label="ปิดรายละเอียดโปรเจกต์"><X /></button>
        </div>
      </header>
      <div className="project-modal-scroll">
        <div className="modal-image">
          <img
            key={`${images[imageIndex]}-${imageIndex}`}
            src={projectImageUrl(images[imageIndex])}
            alt={`${project.name} รูปที่ ${imageIndex + 1}`}
            decoding="async"
            onError={(event) => {
              const fallback = projectImageUrl('/assets/Icon portfolio.png')
              if (event.currentTarget.src !== fallback) event.currentTarget.src = fallback
            }}
          />
          {images.length > 1 && <>
            <button className="gallery-arrow previous" onClick={() => selectImage(-1)} aria-label="รูปก่อนหน้า"><ChevronLeft /></button>
            <button className="gallery-arrow next" onClick={() => selectImage(1)} aria-label="รูปถัดไป"><ChevronRight /></button>
            <span className="gallery-count">{imageIndex + 1} / {images.length}</span>
          </>}
        </div>
        {images.length > 1 && <nav className="gallery-pagination" aria-label="เลือกรูปภาพโปรเจกต์">
          <span>รูปภาพ</span>
          <div>{images.map((image, index) => <button type="button" className={index === imageIndex ? 'active' : ''} onClick={() => setImageIndex(index)} aria-current={index === imageIndex ? 'true' : undefined} aria-label={`ดูรูปที่ ${index + 1}`} key={`${image}-${index}`}>{String(index + 1).padStart(2, '0')}</button>)}</div>
        </nav>}
        <div className="modal-content">
          <TagBadges tags={project.tags || []} />
          <h2 id="project-modal-title">{project.name}</h2>
          <p>{project.description}</p>
        </div>
      </div>
    </article>
  </div>
}

export function ProjectEditor({ project, userId, onClose, onSaved, initialDeleteArmed = false }: { project: Project | null; userId: string; onClose: () => void; onSaved: () => void; initialDeleteArmed?: boolean }) {
  const [value, setValue] = useState<ProjectInput>(project ? { name: project.name, description: project.description, tags: project.tags || [], links: project.links || {}, images: project.images || [] } : emptyInput)
  const [files, setFiles] = useState<File[]>([])
  const [saving, setSaving] = useState(false)
  const [savingStatus, setSavingStatus] = useState('')
  const [error, setError] = useState('')
  const [deleteArmed, setDeleteArmed] = useState(initialDeleteArmed)

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setSaving(true); setError('')
    const uploaded: string[] = []
    try {
      if (!value.tags.length) throw new Error('กรุณาเลือก Tag อย่างน้อย 1 รายการ')
      if (value.images.length + files.length > 5) throw new Error('รูปภาพรวมกันได้สูงสุด 5 รูป')

      for (const [index, file] of files.entries()) {
        setSavingStatus(`กำลังแปลงรูป ${index + 1}/${files.length} เป็น WebP...`)
        const optimized = await optimizeProjectImage(file)
        setSavingStatus(`กำลังอัปโหลดรูป ${index + 1}/${files.length}...`)
        const path = `${userId}/${crypto.randomUUID()}.webp`
        const { error: uploadError } = await supabase.storage.from('project-images').upload(path, optimized, {
          contentType: 'image/webp',
          cacheControl: '31536000',
          upsert: false,
        })
        if (uploadError) throw uploadError
        uploaded.push(path)
      }

      setSavingStatus('กำลังบันทึกข้อมูลโปรเจกต์...')
      const cleanLinks = Object.fromEntries(Object.entries(value.links).filter(([, link]) => safeExternalUrl(link))) as ProjectLinks
      const payload = { ...value, links: cleanLinks, images: [...value.images, ...uploaded], updated_at: new Date().toISOString() }
      const result = project
        ? await supabase.from('projects').update(payload).eq('id', project.id).select('id').maybeSingle()
        : await supabase.from('projects').insert(payload).select('id').maybeSingle()
      if (result.error) throw result.error
      if (!result.data) throw new Error('บันทึกไม่สำเร็จ กรุณาตรวจสอบสิทธิ์แอดมินแล้วลองใหม่')

      const removedImages = (project?.images || []).filter((path) => !value.images.includes(path) && !path.startsWith('/') && !/^https?:/i.test(path))
      if (removedImages.length) await supabase.storage.from('project-images').remove(removedImages)
      onSaved()
    } catch (caught) {
      if (uploaded.length) await supabase.storage.from('project-images').remove(uploaded)
      setError(caught instanceof Error ? caught.message : 'บันทึกไม่สำเร็จ')
    } finally { setSaving(false); setSavingStatus('') }
  }

  const remove = async () => {
    if (!project) return
    setSaving(true); setSavingStatus('กำลังลบโปรเจกต์...'); setError('')
    try {
      const { data: storagePaths, error: deleteError } = await supabase.rpc('admin_delete_project', { target_project_id: project.id })
      if (deleteError) throw deleteError
      const removablePaths = ((storagePaths as string[] | null) || project.images || []).filter((path) => !path.startsWith('/') && !/^https?:/i.test(path))
      if (removablePaths.length) await supabase.storage.from('project-images').remove(removablePaths)
      onSaved()
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : 'ลบโปรเจกต์ไม่สำเร็จ')
    } finally {
      setSaving(false); setSavingStatus(''); setDeleteArmed(false)
    }
  }

  const toggleEditorTag = (tag: string) => setValue({ ...value, tags: value.tags.includes(tag) ? value.tags.filter((item) => item !== tag) : [...value.tags, tag] })

  const selectFiles = (selectedFiles: FileList | null) => {
    setError('')
    const picked = Array.from(selectedFiles || []).slice(0, 5 - value.images.length)
    const invalid = picked.find((file) => !PROJECT_IMAGE_ACCEPT.includes(file.type) || file.size > MAX_PROJECT_IMAGE_BYTES)
    if (invalid) {
      setFiles([])
      setError(`${invalid.name}: รองรับเฉพาะ JPG, PNG และ WebP ขนาดไม่เกิน 20 MB`)
      return
    }
    setFiles(picked)
  }

  return <div className="modal-backdrop" onMouseDown={onClose} role="presentation">
    <form className="editor-modal" onSubmit={submit} onMouseDown={(event) => event.stopPropagation()} role="dialog" aria-modal="true" aria-labelledby="editor-title">
      <header className="modal-toolbar editor-toolbar">
        <div className="form-title"><Upload /><div><strong id="editor-title">{project ? 'แก้ไขโปรเจกต์' : 'เพิ่มโปรเจกต์'}</strong><span>PROJECT EDITOR</span></div></div>
        <button type="button" className="modal-close" onClick={onClose} aria-label="ปิดหน้าต่างแก้ไข"><X /></button>
      </header>

      <div className="editor-modal-scroll">
        <section className="editor-section">
          <div className="editor-section-title"><Pencil size={15} /><span>ข้อมูลโปรเจกต์</span></div>
          <label>ชื่อโปรเจกต์<input required maxLength={150} value={value.name} onChange={(event) => setValue({ ...value, name: event.target.value })} placeholder="ชื่อผลงานหรือกิจกรรม" /></label>
          <label>คำอธิบาย<textarea required maxLength={3000} rows={5} value={value.description} onChange={(event) => setValue({ ...value, description: event.target.value })} placeholder="เล่าแนวคิด บทบาท เทคโนโลยี และผลลัพธ์ของโปรเจกต์" /></label>
        </section>

        <fieldset className="editor-section editor-fieldset">
          <legend className="editor-section-title"><Tag size={15} aria-hidden="true" /><span>ระดับ / Tag</span></legend>
          <div className="editor-tags">{projectTags.map((tag) => {
            const meta = tagMeta[tag]
            const Icon = meta.icon
            const checked = value.tags.includes(tag)
            return <label className={`editor-tag tag-${meta.tone} ${checked ? 'checked' : ''}`} key={tag}><input type="checkbox" checked={checked} onChange={() => toggleEditorTag(tag)} /><i>{checked ? '✓' : ''}</i><span><Icon size={14} aria-hidden="true" />{tag}</span></label>
          })}</div>
        </fieldset>

        <section className="editor-section">
          <div className="editor-section-title"><Link2 size={15} /><span>ลิงก์โปรเจกต์</span></div>
          <div className="editor-links">
            <label><span><Github size={17} /> GitHub</span><input type="url" value={value.links.github || ''} onChange={(event) => setValue({ ...value, links: { ...value.links, github: event.target.value } })} placeholder="https://github.com/..." /></label>
            <label><span><Youtube size={17} /> YouTube</span><input type="url" value={value.links.youtube || ''} onChange={(event) => setValue({ ...value, links: { ...value.links, youtube: event.target.value } })} placeholder="https://youtube.com/..." /></label>
            <label><span><ExternalLink size={17} /> Google Drive</span><input type="url" value={value.links.drive || ''} onChange={(event) => setValue({ ...value, links: { ...value.links, drive: event.target.value } })} placeholder="https://drive.google.com/..." /></label>
          </div>
        </section>

        <section className="editor-section">
          <div className="editor-section-title"><Images size={15} /><span>รูปภาพโปรเจกต์ ({value.images.length + files.length}/5)</span></div>
          {!!value.images.length && <div className="existing-images">{value.images.map((image, index) => <div className="existing-image" key={`${image}-${index}`}><img src={projectImageUrl(image)} alt={`รูปปัจจุบัน ${index + 1}`} /><button type="button" onClick={() => setValue({ ...value, images: value.images.filter((item) => item !== image) })} aria-label={`นำรูปที่ ${index + 1} ออก`}><X size={14} /></button></div>)}</div>}
          <label className="upload-drop"><ImagePlus /><strong>เลือกรูปภาพเพิ่มเติม</strong><span>ระบบจะแปลงเป็น WebP และย่อไม่เกิน 1920px อัตโนมัติ · สูงสุด 20 MB</span><input type="file" accept={PROJECT_IMAGE_ACCEPT} multiple disabled={value.images.length >= 5 || saving} onChange={(event) => selectFiles(event.target.files)} /></label>
          {!!files.length && <div className="selected-files">{files.map((file) => <span key={`${file.name}-${file.size}`}>{file.name} → WebP</span>)}</div>}
        </section>

        {error && <div className="notice error">{error}</div>}
        {savingStatus && <div className="image-processing-status" role="status"><span className="spinner" />{savingStatus}</div>}
        <div className={`editor-actions ${deleteArmed ? 'confirming-delete' : ''}`}>
          {project && !deleteArmed && <button type="button" className="button danger-button" onClick={() => { setDeleteArmed(true); setError('') }} disabled={saving}><Trash2 size={17} /> ลบโปรเจกต์</button>}
          {project && deleteArmed && <div className="delete-confirmation" role="alertdialog" aria-label="ยืนยันลบโปรเจกต์">
            <span><Trash2 size={16} /><b>ลบ “{project.name}” ถาวร?</b></span>
            <div><button type="button" className="button secondary small" onClick={() => setDeleteArmed(false)} disabled={saving}>ยกเลิก</button><button type="button" className="button danger-button small" onClick={() => void remove()} disabled={saving}>ยืนยันลบ</button></div>
          </div>}
          <button className="button" disabled={saving || deleteArmed}>{saving ? 'กำลังประมวลผล...' : 'บันทึกโปรเจกต์'}</button>
        </div>
      </div>
    </form>
  </div>
}
