import {
  ChevronLeft,
  ChevronRight,
  ArrowDownWideNarrow,
  Check,
  ExternalLink,
  Github,
  Globe2,
  ImagePlus,
  Images,
  Landmark,
  Link2,
  Pencil,
  Pause,
  Play,
  Plus,
  Search,
  Share2,
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
import { Fragment, useEffect, useMemo, useRef, useState, type FormEvent, type MouseEvent } from 'react'
import { createPortal } from 'react-dom'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '../components/PageHeader'
import { Seo } from '../components/Seo'
import { useAuth } from '../context/AuthContext'
import { fallbackProjects, projectTags } from '../data'
import { MAX_PROJECT_IMAGE_BYTES, optimizeProjectImage, PROJECT_IMAGE_ACCEPT } from '../lib/imageOptimization'
import { sortProjectsByImportance } from '../lib/projectRanking'
import { projectImageUrl, supabase } from '../lib/supabase'
import type { AwardType, Project, ProjectInput, ProjectLinks } from '../types'
import './ProjectsPage.css'

const emptyInput: ProjectInput = { slug: '', name: '', description: '', images: [], tags: ['งานในโรงเรียน'], links: {}, award_type: null, award_title: null, award_rank: null }

const normalizeSlug = (value: string) => value
  .trim()
  .toLowerCase()
  .normalize('NFKD')
  .replace(/[^a-z0-9]+/g, '-')
  .replace(/^-+|-+$/g, '')
  .slice(0, 80)

const projectSlug = (project: Project) => project.slug || `project-${project.id}`

const awardOptions: Array<{ value: AwardType; label: string; title: string; rank: number | null }> = [
  { value: 'winner', label: 'ชนะเลิศ', title: 'ชนะเลิศ', rank: 1 },
  { value: 'runner_up_1', label: 'รองชนะเลิศ 1', title: 'รองชนะเลิศอันดับ 1', rank: 2 },
  { value: 'runner_up_2', label: 'รองชนะเลิศ 2', title: 'รองชนะเลิศอันดับ 2', rank: 3 },
  { value: 'gold', label: 'เหรียญทอง', title: 'เหรียญทอง', rank: null },
  { value: 'silver', label: 'เหรียญเงิน', title: 'เหรียญเงิน', rank: null },
  { value: 'bronze', label: 'เหรียญทองแดง', title: 'เหรียญทองแดง', rank: null },
  { value: 'finalist', label: 'เข้ารอบชิง', title: 'เข้ารอบชิงชนะเลิศ', rank: null },
  { value: 'other', label: 'รางวัลอื่น', title: 'รางวัลจากการแข่งขัน', rank: null },
]

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

const stripDescriptionFormatting = (text: string) => text
  .replace(/\*\*([^*\n]+)\*\*/g, '$1')
  .replace(/\*([^*\n]+)\*/g, '$1')

function FormattedDescription({ text }: { text: string }) {
  const lines = text.split('\n')
  const tokenPattern = /(\*\*[^*\n]+\*\*|\*[^*\n]+\*)/g

  return <>{lines.map((line, lineIndex) => <Fragment key={`${lineIndex}-${line}`}>
    {line.split(tokenPattern).filter(Boolean).map((part, index) => {
      if (part.startsWith('**') && part.endsWith('**')) return <strong key={index}>{part.slice(2, -2)}</strong>
      if (part.startsWith('*') && part.endsWith('*')) return <em key={index}>{part.slice(1, -1)}</em>
      return <Fragment key={index}>{part}</Fragment>
    })}
    {lineIndex < lines.length - 1 && <br />}
  </Fragment>)}</>
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

function AwardBadge({ project, large = false }: { project: Project; large?: boolean }) {
  if (!project.award_type || !project.award_title) return null
  const hasRankInTitle = /อันดับ\s*\d/.test(project.award_title)
  return <div className={`project-award ${large ? 'large' : ''}`}>
    <Trophy size={large ? 17 : 13} aria-hidden="true" />
    <span>{project.award_title}</span>
    {project.award_rank && !hasRankInTitle && <small>อันดับ {project.award_rank}</small>}
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
  const navigate = useNavigate()
  const { slug } = useParams<{ slug?: string }>()
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
    setProjects(sortProjectsByImportance(error ? fallbackProjects : (data as Project[] || [])))
    setLoading(false)
  }

  useEffect(() => { void loadProjects() }, [])

  useEffect(() => {
    if (!slug) {
      setSelected(null)
      return
    }
    const matchedProject = projects.find((project) => projectSlug(project) === slug)
    setSelected(matchedProject || null)
  }, [projects, slug])

  useEffect(() => {
    if (!selected && !editing && !deleting) return
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key !== 'Escape') return
      if (editing) setEditing(null)
      else if (deleting) setDeleting(null)
      else {
        setSelected(null)
        void navigate('/projects')
      }
    }
    document.addEventListener('keydown', closeOnEscape)
    return () => {
      document.body.style.overflow = previousOverflow
      document.removeEventListener('keydown', closeOnEscape)
    }
  }, [selected, editing, deleting, navigate])

  const normalizedQuery = query.trim().toLocaleLowerCase('th')
  const visible = useMemo(() => projects.filter((project) => {
    const searchable = `${project.name} ${project.description} ${project.award_title || ''} ${(project.tags || []).join(' ')}`.toLocaleLowerCase('th')
    const matchesQuery = !normalizedQuery || searchable.includes(normalizedQuery)
    const matchesTags = !activeTags.length || activeTags.some((tag) => project.tags?.includes(tag))
    return matchesQuery && matchesTags
  }), [projects, normalizedQuery, activeTags])

  const toggleTag = (tag: string) => setActiveTags((current) => current.includes(tag) ? current.filter((item) => item !== tag) : [...current, tag])
  const clearFilters = () => { setQuery(''); setActiveTags([]) }
  const closeSelected = () => { setSelected(null); void navigate('/projects') }
  const selectedPath = selected ? `/projects/${projectSlug(selected)}` : '/projects'
  const selectedDescription = selected ? stripDescriptionFormatting(selected.description).replace(/\s+/g, ' ').slice(0, 155) : 'รวมผลงาน Game, IoT, Robotics, AI และ Web Development ของ Wutthipat Sriyangnok'

  return (
    <section className="content-section page-section projects-page">
      <Seo
        title={selected ? `${selected.name.trim()} — Wutthipat Sriyangnok` : 'Projects — Wutthipat Sriyangnok'}
        description={selectedDescription}
        path={selectedPath}
        image={selected?.images?.[0] ? projectImageUrl(selected.images[0]) : '/assets/og-portfolio.png'}
        type={selected ? 'article' : 'website'}
      />
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
        <div className="project-result-meta">
          <div className="result-count" aria-live="polite">{loading ? 'กำลังโหลดผลงาน...' : `แสดง ${visible.length} จาก ${projects.length} โปรเจกต์`}</div>
          <span><ArrowDownWideNarrow size={13} /> เรียงระดับสูงสุดและรางวัลก่อน</span>
        </div>
      </div>

      {loading ? <div className="project-grid project-skeleton-grid" aria-label="กำลังโหลด">
        {Array.from({ length: 8 }, (_, index) => <div className="project-skeleton" key={index}><i /><span /><b /></div>)}
      </div> : <div className="project-grid">
        {visible.map((project, index) => (
          <article className="project-card reveal" style={{ animationDelay: `${Math.min(index, 8) * 45}ms` }} key={project.id}>
            <button className="project-card-open" onClick={() => void navigate(`/projects/${projectSlug(project)}`)} aria-label={`เปิดรายละเอียด ${project.name}`}>
              <div className="project-cover">
                <img src={projectImageUrl(project.images?.[0])} alt={project.name} loading="lazy" />
                <span className="project-cover-shade" />
                {project.images?.length > 1 && <span className="image-count"><Images size={14} /> {project.images.length}</span>}
              </div>
              <div className="project-body">
                <AwardBadge project={project} />
                <TagBadges tags={project.tags || []} />
                <h2>{project.name}</h2>
                <p>{stripDescriptionFormatting(project.description)}</p>
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

      {selected && createPortal(<div className="projects-page modal-portal"><ProjectModal project={selected} onClose={closeSelected} /></div>, document.body)}
      {editing && isAdmin && user && createPortal(<div className="projects-page modal-portal"><ProjectEditor project={editing === 'new' ? null : editing} userId={user.id} onClose={() => setEditing(null)} onSaved={() => { setEditing(null); void loadProjects() }} /></div>, document.body)}
      {deleting && isAdmin && user && createPortal(<div className="projects-page modal-portal"><ProjectEditor project={deleting} userId={user.id} initialDeleteArmed onClose={() => setDeleting(null)} onSaved={() => { setDeleting(null); void loadProjects() }} /></div>, document.body)}
    </section>
  )
}

export function ProjectModal({ project, onClose }: { project: Project; onClose: () => void }) {
  const [imageIndex, setImageIndex] = useState(0)
  const [autoPaused, setAutoPaused] = useState(false)
  const [hoverPaused, setHoverPaused] = useState(false)
  const [autoProgress, setAutoProgress] = useState(0)
  const [linkCopied, setLinkCopied] = useState(false)
  const elapsedRef = useRef(0)
  const lastFrameRef = useRef<number | null>(null)
  const lastProgressUpdateRef = useRef(0)
  const images = project.images?.length ? project.images : ['/assets/Icon portfolio.png']
  const galleryPaused = autoPaused || hoverPaused

  const shareProject = async () => {
    const shareData = { title: project.name, text: stripDescriptionFormatting(project.description).slice(0, 140), url: window.location.href }
    try {
      if (navigator.share) await navigator.share(shareData)
      else {
        await navigator.clipboard.writeText(window.location.href)
        setLinkCopied(true)
        window.setTimeout(() => setLinkCopied(false), 2200)
      }
    } catch (caught) {
      if (caught instanceof DOMException && caught.name === 'AbortError') return
      try {
        await navigator.clipboard.writeText(window.location.href)
        setLinkCopied(true)
        window.setTimeout(() => setLinkCopied(false), 2200)
      } catch { /* Clipboard can be unavailable in an insecure preview. */ }
    }
  }

  const selectImage = (offset: number) => {
    setAutoPaused(true)
    setImageIndex((current) => (current + offset + images.length) % images.length)
  }

  const goToImage = (index: number) => {
    setAutoPaused(true)
    setImageIndex(index)
  }

  useEffect(() => {
    elapsedRef.current = 0
    lastFrameRef.current = null
    lastProgressUpdateRef.current = 0
    setAutoProgress(0)
  }, [imageIndex])

  useEffect(() => {
    if (images.length <= 1 || galleryPaused) {
      lastFrameRef.current = null
      return
    }

    const duration = 6000
    let frame = 0
    const tick = (now: number) => {
      if (lastFrameRef.current === null) lastFrameRef.current = now
      const elapsed = Math.min(duration, elapsedRef.current + now - lastFrameRef.current)
      elapsedRef.current = elapsed
      lastFrameRef.current = now
      if (now - lastProgressUpdateRef.current >= 80 || elapsed >= duration) {
        lastProgressUpdateRef.current = now
        setAutoProgress((elapsed / duration) * 100)
      }

      if (elapsed >= duration) {
        elapsedRef.current = 0
        lastFrameRef.current = now
        lastProgressUpdateRef.current = now
        setAutoProgress(0)
        setImageIndex((current) => (current + 1) % images.length)
      }
      frame = requestAnimationFrame(tick)
    }

    frame = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(frame)
  }, [galleryPaused, images.length])

  useEffect(() => {
    const changeImage = (event: KeyboardEvent) => {
      if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return
      setAutoPaused(true)
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
          <button className={`project-share-button ${linkCopied ? 'copied' : ''}`} type="button" onClick={() => void shareProject()} aria-label="แชร์ลิงก์โปรเจกต์" title="แชร์ลิงก์โปรเจกต์">{linkCopied ? <Check size={18} /> : <Share2 size={18} />}<span>{linkCopied ? 'คัดลอกแล้ว' : 'แชร์'}</span></button>
          <button className="modal-close" onClick={onClose} aria-label="ปิดรายละเอียดโปรเจกต์"><X /></button>
        </div>
      </header>
      <div className="project-modal-scroll">
        <div className="modal-image" onMouseEnter={() => setHoverPaused(true)} onMouseLeave={() => setHoverPaused(false)} onClick={() => setAutoPaused(true)}>
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
        {images.length > 1 && <>
          <div className={`gallery-autoplay ${galleryPaused ? 'paused' : ''}`}>
            <button type="button" onClick={(event) => { event.stopPropagation(); setAutoPaused((paused) => !paused) }} aria-label={autoPaused ? 'เล่นสไลด์อัตโนมัติ' : 'หยุดสไลด์อัตโนมัติ'} aria-pressed={autoPaused}>
              {autoPaused ? <Play size={14} fill="currentColor" /> : <Pause size={14} fill="currentColor" />}
              <span>{autoPaused ? 'เล่นอัตโนมัติ' : hoverPaused ? 'พักชั่วคราว' : 'กำลังเล่นอัตโนมัติ'}</span>
            </button>
            <div className="gallery-progress" role="progressbar" aria-label="เวลาจนถึงรูปถัดไป" aria-valuemin={0} aria-valuemax={100} aria-valuenow={Math.round(autoProgress)}>
              <span style={{ transform: `scaleX(${autoProgress / 100})` }} />
            </div>
            <small>{galleryPaused ? (autoPaused ? 'กดเล่นเพื่อดูภาพต่อ' : 'เลื่อนต่อเมื่อเอาเมาส์ออก') : `รูปถัดไปใน ${Math.max(1, Math.ceil(6 - autoProgress * .06))} วินาที`}</small>
          </div>
          <nav className="gallery-pagination" aria-label="เลือกรูปภาพโปรเจกต์">
            <span>รูปภาพ</span>
            <div>{images.map((image, index) => <button type="button" className={index === imageIndex ? 'active' : ''} onClick={() => goToImage(index)} aria-current={index === imageIndex ? 'true' : undefined} aria-label={`ดูรูปที่ ${index + 1}`} key={`${image}-${index}`}>{String(index + 1).padStart(2, '0')}</button>)}</div>
          </nav>
        </>}
        <div className="modal-content">
          <AwardBadge project={project} large />
          <TagBadges tags={project.tags || []} />
          <h2 id="project-modal-title">{project.name}</h2>
          <p className="formatted-description"><FormattedDescription text={project.description} /></p>
        </div>
      </div>
    </article>
  </div>
}

export function ProjectEditor({ project, userId, onClose, onSaved, initialDeleteArmed = false }: { project: Project | null; userId: string; onClose: () => void; onSaved: () => void; initialDeleteArmed?: boolean }) {
  const [value, setValue] = useState<ProjectInput>(project ? {
    slug: project.slug || `project-${project.id}`,
    name: project.name,
    description: project.description,
    tags: project.tags || [],
    links: project.links || {},
    images: project.images || [],
    award_type: project.award_type || null,
    award_title: project.award_title || null,
    award_rank: project.award_rank || null,
  } : emptyInput)
  const [files, setFiles] = useState<File[]>([])
  const [saving, setSaving] = useState(false)
  const [savingStatus, setSavingStatus] = useState('')
  const [error, setError] = useState('')
  const [deleteArmed, setDeleteArmed] = useState(initialDeleteArmed)
  const descriptionRef = useRef<HTMLTextAreaElement>(null)

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setSaving(true); setError('')
    const uploaded: string[] = []
    try {
      if (!value.tags.length) throw new Error('กรุณาเลือก Tag อย่างน้อย 1 รายการ')
      if (value.images.length + files.length > 5) throw new Error('รูปภาพรวมกันได้สูงสุด 5 รูป')
      const cleanSlug = normalizeSlug(value.slug)
      if (!cleanSlug) throw new Error('กรุณากำหนดลิงก์โปรเจกต์เป็นภาษาอังกฤษ เช่น microfiber-filter')

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
      const payload = { ...value, slug: cleanSlug, links: cleanLinks, images: [...value.images, ...uploaded], updated_at: new Date().toISOString() }
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

  const toggleAward = () => setValue(value.award_type ? {
    ...value,
    award_type: null,
    award_title: null,
    award_rank: null,
  } : {
    ...value,
    award_type: 'winner',
    award_title: 'ชนะเลิศ',
    award_rank: 1,
  })

  const selectAwardType = (option: typeof awardOptions[number]) => setValue({
    ...value,
    award_type: option.value,
    award_title: option.title,
    award_rank: option.rank,
  })

  const moveImage = (index: number, offset: number) => {
    const nextIndex = index + offset
    if (nextIndex < 0 || nextIndex >= value.images.length) return
    const images = [...value.images]
    const [movedImage] = images.splice(index, 1)
    images.splice(nextIndex, 0, movedImage)
    setValue({ ...value, images })
  }

  const moveSelectedFile = (index: number, offset: number) => {
    const nextIndex = index + offset
    if (nextIndex < 0 || nextIndex >= files.length) return
    const nextFiles = [...files]
    const [movedFile] = nextFiles.splice(index, 1)
    nextFiles.splice(nextIndex, 0, movedFile)
    setFiles(nextFiles)
  }

  const formatDescription = (marker: '**' | '*') => {
    const textarea = descriptionRef.current
    if (!textarea) return
    const start = textarea.selectionStart
    const end = textarea.selectionEnd
    const selectedText = value.description.slice(start, end) || (marker === '**' ? 'ข้อความตัวหนา' : 'ข้อความตัวเอียง')
    const formattedText = `${marker}${selectedText}${marker}`
    const description = `${value.description.slice(0, start)}${formattedText}${value.description.slice(end)}`
    setValue({ ...value, description })
    requestAnimationFrame(() => {
      textarea.focus()
      textarea.setSelectionRange(start + marker.length, start + marker.length + selectedText.length)
    })
  }

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
          <label>ชื่อโปรเจกต์<input required maxLength={150} value={value.name} onChange={(event) => setValue({ ...value, name: event.target.value, slug: project || value.slug ? value.slug : normalizeSlug(event.target.value) })} placeholder="ชื่อผลงานหรือกิจกรรม" /></label>
          <label>ลิงก์ตรงของโปรเจกต์<div className="project-slug-input"><span>/projects/</span><input required maxLength={80} pattern="[a-z0-9]+(?:-[a-z0-9]+)*" value={value.slug} onChange={(event) => setValue({ ...value, slug: normalizeSlug(event.target.value) })} placeholder="microfiber-filter" /></div><small className="field-help">ใช้ตัวอักษรอังกฤษ ตัวเลข และขีดกลาง เพื่อแชร์ผลงานชิ้นนี้โดยตรง</small></label>
          <div className="editor-description-field">
            <label htmlFor="project-description">คำอธิบาย</label>
            <div className="description-toolbar" aria-label="จัดรูปแบบคำอธิบาย">
              <span>เลือกข้อความแล้วกดรูปแบบ</span>
              <div>
                <button type="button" onClick={() => formatDescription('**')} aria-label="ทำข้อความเป็นตัวหนา"><b>B</b> ตัวหนา</button>
                <button type="button" onClick={() => formatDescription('*')} aria-label="ทำข้อความเป็นตัวเอียง"><i>I</i> ตัวเอียง</button>
              </div>
            </div>
            <textarea id="project-description" ref={descriptionRef} required maxLength={3000} rows={7} value={value.description} onChange={(event) => setValue({ ...value, description: event.target.value })} placeholder="เขียนรายละเอียดของโปรเจกต์ แล้วเลือกข้อความเพื่อทำตัวหนาหรือตัวเอียง" />
            {!!value.description && <div className="description-preview"><span>ตัวอย่างที่จะแสดง</span><p className="formatted-description"><FormattedDescription text={value.description} /></p></div>}
          </div>
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

        <section className="editor-section award-editor-section">
          <div className="editor-section-title"><Trophy size={15} /><span>รางวัล / ผลการแข่งขัน</span></div>
          <label className={`award-toggle ${value.award_type ? 'checked' : ''}`}>
            <input type="checkbox" checked={Boolean(value.award_type)} onChange={toggleAward} />
            <i>{value.award_type ? '✓' : ''}</i>
            <span><b>มีรางวัลหรืออันดับจากการแข่งขัน</b><small>ป้ายจะแสดงบนการ์ดและใช้ช่วยจัดลำดับผลงาน</small></span>
          </label>
          {value.award_type && <div className="award-editor-fields">
            <div className="award-option-grid" role="radiogroup" aria-label="เลือกประเภทรางวัล">
              {awardOptions.map((option) => <button type="button" role="radio" aria-checked={value.award_type === option.value} className={value.award_type === option.value ? 'active' : ''} onClick={() => selectAwardType(option)} key={option.value}>{option.label}</button>)}
            </div>
            <div className="award-detail-fields">
              <label>ข้อความที่แสดง<input required maxLength={100} value={value.award_title || ''} onChange={(event) => setValue({ ...value, award_title: event.target.value })} placeholder="เช่น เหรียญทองแดง" /></label>
              <label>อันดับที่<input type="number" min={1} max={999} value={value.award_rank || ''} onChange={(event) => setValue({ ...value, award_rank: event.target.value ? Number(event.target.value) : null })} placeholder="ไม่ระบุก็ได้" /></label>
            </div>
            <div className="award-preview"><span>ตัวอย่างป้าย</span><AwardBadge project={{ ...(project || { id: 0, created_at: '', updated_at: '' }), ...value } as Project} large /></div>
          </div>}
        </section>

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
          {!!value.images.length && <div className="existing-images">{value.images.map((image, index) => <div className="existing-image" key={`${image}-${index}`}>
            <img src={projectImageUrl(image)} alt={`รูปปัจจุบัน ${index + 1}`} />
            <span className="image-position">{index === 0 ? 'หน้าปก' : `รูป ${index + 1}`}</span>
            <div className="image-order-actions">
              <button type="button" onClick={() => moveImage(index, -1)} disabled={index === 0} aria-label={`เลื่อนรูปที่ ${index + 1} ไปทางซ้าย`}><ChevronLeft size={15} /></button>
              <button type="button" onClick={() => moveImage(index, 1)} disabled={index === value.images.length - 1} aria-label={`เลื่อนรูปที่ ${index + 1} ไปทางขวา`}><ChevronRight size={15} /></button>
              <button type="button" className="remove-image" onClick={() => setValue({ ...value, images: value.images.filter((_, imageIndex) => imageIndex !== index) })} aria-label={`นำรูปที่ ${index + 1} ออก`}><X size={14} /></button>
            </div>
          </div>)}</div>}
          <label className="upload-drop"><ImagePlus /><strong>เลือกรูปภาพเพิ่มเติม</strong><span>ระบบจะแปลงเป็น WebP และย่อไม่เกิน 1920px อัตโนมัติ · สูงสุด 20 MB</span><input type="file" accept={PROJECT_IMAGE_ACCEPT} multiple disabled={value.images.length >= 5 || saving} onChange={(event) => selectFiles(event.target.files)} /></label>
          {!!files.length && <div className="selected-files">{files.map((file, index) => <div key={`${file.name}-${file.size}-${index}`}><span>{file.name} → WebP</span><div><button type="button" onClick={() => moveSelectedFile(index, -1)} disabled={index === 0} aria-label={`เลื่อน ${file.name} ไปก่อนหน้า`}><ChevronLeft size={14} /></button><button type="button" onClick={() => moveSelectedFile(index, 1)} disabled={index === files.length - 1} aria-label={`เลื่อน ${file.name} ไปถัดไป`}><ChevronRight size={14} /></button><button type="button" onClick={() => setFiles(files.filter((_, fileIndex) => fileIndex !== index))} aria-label={`นำ ${file.name} ออก`}><X size={13} /></button></div></div>)}</div>}
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
