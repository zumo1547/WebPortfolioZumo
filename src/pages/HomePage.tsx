import {
  ArrowRight,
  Atom,
  BarChart3,
  Bot,
  Boxes,
  Braces,
  CircuitBoard,
  Code2,
  Cpu,
  Database,
  ChevronLeft,
  ChevronRight,
  Film,
  FolderOpen,
  Gamepad2,
  Github,
  Medal,
  Microscope,
  Pause,
  Play,
  Presentation,
  Radio,
  ScanSearch,
  ServerCog,
  Timer,
  Trophy,
  X,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { useEffect, useState } from 'react'
import { createPortal } from 'react-dom'
import { Link } from 'react-router-dom'
import { Seo } from '../components/Seo'
import { techStack } from '../data'
import { assetUrl } from '../lib/supabase'
import './HomePage.css'

const marquee = ['GAME DEV', 'IoT SYSTEMS', 'REACT', 'TYPESCRIPT', 'ESP32', 'PYTHON', 'ROBOTICS', 'AI DETECTION', 'SUPABASE']

const impact = [
  {
    icon: Trophy,
    value: 'ชนะเลิศ',
    title: 'Science Film Festival 2025',
    detail: 'ผลงานวิดีโอ “ขยะกำพร้า” ชนะระดับมัธยมศึกษาตอนปลายจากผู้เข้าร่วม 70 โรงเรียน',
    tone: 'pink',
  },
  {
    icon: Microscope,
    value: '91.97%',
    title: 'Microfiber Filter Efficiency',
    detail: 'ลดเส้นใยไมโครไฟเบอร์จาก 137 ชิ้น เหลือหลุดรอดเพียง 9 ชิ้นในการทดสอบ',
    tone: 'blue',
  },
  {
    icon: Medal,
    value: 'TOP 30',
    title: 'SPU AI Prompt Mini Hackathon',
    detail: 'ผ่านเข้าสู่รอบ 30 ทีมสุดท้าย พร้อมฝึกแก้โจทย์และตรวจสอบผลลัพธ์จาก AI อย่างเป็นระบบ',
    tone: 'purple',
  },
]

const appliedSkills = [
  ['Python', 'AI · OpenCV · Data'],
  ['ESP32 / micro:bit', 'IoT · Sensor · Automation'],
  ['React / TypeScript', 'Web · UI · Supabase'],
]

const techIcons: Record<string, LucideIcon> = {
  atom: Atom,
  boxes: Boxes,
  braces: Braces,
  circuit: CircuitBoard,
  code: Code2,
  cpu: Cpu,
  database: Database,
  gamepad: Gamepad2,
  github: Github,
  scan: ScanSearch,
  server: ServerCog,
}

const reviewSlides = [
  {
    code: '01 / PROFILE',
    title: 'Wutthipat Sriyangnok',
    subtitle: 'นักเรียนมัธยมศึกษาปีที่ 6 · Science & Innovation',
    metric: 'M.6',
    metricLabel: 'CURRENT LEVEL',
    description: 'ผมชอบเริ่มจากโจทย์ที่พบรอบตัว แล้วลงมือเขียนโค้ด ต่อวงจร ทำต้นแบบ และทดสอบจนเห็นผลจริง',
    facts: ['IoT & Embedded Systems', 'AI & Data', 'Web & Game Development'],
    link: '/about',
    linkLabel: 'ดูประวัติและเส้นทางการเรียนรู้',
    tone: 'purple',
  },
  {
    code: '02 / ENGINEERING',
    title: 'ESP32-Powered Microfiber Filter',
    subtitle: 'เครื่องกรองไมโครไฟเบอร์จากน้ำทิ้งเครื่องซักผ้า',
    metric: '91.97%',
    metricLabel: 'FILTER EFFICIENCY',
    description: 'รับผิดชอบการเขียนโค้ด ESP32 ออกแบบระบบกรอง และทดสอบเวลาในการตกตะกอนกับตาข่าย 500 mesh',
    facts: ['ลดเส้นใยจาก 137 เหลือ 9 ชิ้น', 'เหรียญทองแดงรอบชิงชนะเลิศ', 'ออกแบบเพื่อพัฒนาต่อเป็น IoT'],
    link: '/projects/microfiber-filter-esp32',
    linkLabel: 'เปิดรายละเอียดโครงงาน',
    tone: 'blue',
  },
  {
    code: '03 / AIoT & DATA',
    title: 'Micro:bit Thailand Challenge 2026',
    subtitle: 'ระบบวิเคราะห์ข้อมูลและควบคุมการเพาะปลูกอัจฉริยะ',
    metric: 'BRONZE',
    metricLabel: 'NATIONAL FINAL',
    description: 'ทำหน้าที่หัวหน้าทีม เชื่อมต่อเซนเซอร์ วิเคราะห์ข้อมูล และประยุกต์ AI ตรวจจับศัตรูพืชเพื่อควบคุมระบบจริง',
    facts: ['Soil & Weather Sensors', 'Micro:bit Automation', 'Computer Vision'],
    link: '/projects/microbit-thailand-challenge-2026',
    linkLabel: 'ดูผลงาน AIoT',
    tone: 'green',
  },
  {
    code: '04 / ACHIEVEMENTS',
    title: 'ผลงานจากสนามจริง',
    subtitle: 'การแข่งขัน โครงงาน และกิจกรรมที่นำความรู้ไปใช้งาน',
    metric: 'WINNER',
    metricLabel: 'SCIENCE FILM FESTIVAL',
    description: 'รางวัลชนะเลิศระดับมัธยมปลายจาก Science Film Festival 2025 พร้อมผลงานด้านวิศวกรรม AIoT และ AI Prompt',
    facts: ['Science Film Festival Winner', 'OCOP Bronze Medal', 'SPU AI Hackathon Top 30'],
    link: '/projects',
    linkLabel: 'เปิดคลังผลงานทั้งหมด',
    tone: 'pink',
  },
] as const

function JudgeReview({ open, onClose }: { open: boolean; onClose: () => void }) {
  const [active, setActive] = useState(0)
  const [paused, setPaused] = useState(false)
  const [hoverPaused, setHoverPaused] = useState(false)
  const [progress, setProgress] = useState(0)
  const slide = reviewSlides[active]
  const isPaused = paused || hoverPaused

  useEffect(() => {
    if (!open) return
    setActive(0)
    setProgress(0)
    setPaused(false)
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    const closeOnEscape = (event: KeyboardEvent) => { if (event.key === 'Escape') onClose() }
    document.addEventListener('keydown', closeOnEscape)
    return () => {
      document.body.style.overflow = previousOverflow
      document.removeEventListener('keydown', closeOnEscape)
    }
  }, [open, onClose])

  useEffect(() => {
    if (!open || isPaused || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
    const interval = window.setInterval(() => {
      setProgress((current) => {
        if (current < 100) return Math.min(100, current + (100 / 150))
        setActive((currentSlide) => (currentSlide + 1) % reviewSlides.length)
        return 0
      })
    }, 100)
    return () => window.clearInterval(interval)
  }, [open, isPaused])

  const goTo = (index: number) => {
    setActive((index + reviewSlides.length) % reviewSlides.length)
    setProgress(0)
  }

  if (!open) return null
  return createPortal(<div className="judge-review-backdrop" role="presentation">
    <section className={`judge-review judge-${slide.tone}`} role="dialog" aria-modal="true" aria-labelledby="judge-review-title" onMouseEnter={() => setHoverPaused(true)} onMouseLeave={() => setHoverPaused(false)}>
      <header className="judge-review-header">
        <div><Presentation size={18} /><span>ADMISSION REVIEW</span><b>60 SECOND PORTFOLIO</b></div>
        <div className="judge-review-actions">
          <button type="button" onClick={() => setPaused((value) => !value)} aria-label={paused ? 'เล่นการนำเสนอต่อ' : 'หยุดการนำเสนอชั่วคราว'}>{paused ? <Play size={17} /> : <Pause size={17} />}</button>
          <button type="button" onClick={onClose} aria-label="ปิดโหมดกรรมการ"><X size={20} /></button>
        </div>
      </header>
      <div className="judge-progress" aria-label={`หน้าที่ ${active + 1} จาก ${reviewSlides.length}`}>
        {reviewSlides.map((item, index) => <button type="button" className={index === active ? 'active' : index < active ? 'passed' : ''} onClick={() => goTo(index)} aria-label={`เปิด ${item.title}`} key={item.code}><span style={{ transform: `scaleX(${(index === active ? progress : index < active ? 100 : 0) / 100})` }} /></button>)}
      </div>
      <div className="judge-review-body" key={slide.code}>
        <div className="judge-story">
          <span className="judge-code">// {slide.code}</span>
          <h2 id="judge-review-title">{slide.title}</h2>
          <h3>{slide.subtitle}</h3>
          <p>{slide.description}</p>
          <ul>{slide.facts.map((fact) => <li key={fact}><span />{fact}</li>)}</ul>
          <Link to={slide.link} onClick={onClose}>{slide.linkLabel}<ArrowRight size={17} /></Link>
        </div>
        <div className="judge-metric">
          <div className="metric-rings"><i /><i /><i /><BarChart3 /></div>
          <span>{slide.metricLabel}</span>
          <strong>{slide.metric}</strong>
          <small>VERIFIED PORTFOLIO DATA</small>
        </div>
      </div>
      <footer className="judge-review-footer">
        <button type="button" onClick={() => goTo(active - 1)} aria-label="หน้าก่อนหน้า"><ChevronLeft /> ก่อนหน้า</button>
        <span>{String(active + 1).padStart(2, '0')} <i>/</i> {String(reviewSlides.length).padStart(2, '0')}</span>
        <button type="button" onClick={() => goTo(active + 1)} aria-label="หน้าถัดไป">ถัดไป <ChevronRight /></button>
      </footer>
    </section>
  </div>, document.body)
}

export function HomePage() {
  const [reviewOpen, setReviewOpen] = useState(false)
  return (
    <>
      <Seo title="Wutthipat Sriyangnok — Creative Developer Portfolio" description="แฟ้มสะสมผลงานของ Wutthipat Sriyangnok นักเรียนมัธยมศึกษาปีที่ 6 รวมผลงาน IoT, AI, Robotics, Game Development และ Web Development" path="/" />
      <section className="hero section-pad home-hero">
        <div className="hero-copy reveal">
          <div className="eyebrow"><span className="online-dot" /> ADMISSION PORTFOLIO · READY TO REVIEW</div>
          <h1><small>I BUILD</small>IDEAS INTO<br /><span>WORKING SYSTEMS.</span></h1>
          <p className="hero-thai"><strong>วุฒิภัทร ศรียางนอก</strong> — นักเรียน ม.6 ที่ชอบเขียนโค้ด ต่อวงจร และทดสอบของจริง<br />ผลงานครอบคลุม IoT, AI, Robotics, Game และ Web Development</p>
          <div className="home-intro-tags" aria-label="ข้อมูลโดยย่อ">
            <span>มัธยมศึกษาปีที่ 6</span><span>Science &amp; Innovation</span><span>Samut Prakan</span>
          </div>
          <div className="hero-actions">
            <button className="button judge-mode-button" type="button" onPointerDown={() => setReviewOpen(true)} onMouseDown={() => setReviewOpen(true)} onClick={() => setReviewOpen(true)}><Timer size={18} /> ดูแฟ้มฉบับ 60 วินาที <ArrowRight size={17} /></button>
            <Link className="button secondary" to="/projects"><FolderOpen size={18} /> เปิดคลังผลงาน</Link>
          </div>
          <div className="hero-proof-stats" aria-label="ผลงานโดยย่อ">
            <div><strong>91.97%</strong><span>FILTER TEST</span></div>
            <div><strong>WINNER</strong><span>FILM FESTIVAL</span></div>
            <div><strong>TOP 30</strong><span>AI HACKATHON</span></div>
          </div>
          <div className="hero-status"><Radio size={16} /><span>AVAILABLE FOR ADMISSION REVIEW · {new Date().getFullYear()}</span></div>
        </div>

        <div className="hero-visual reveal delay-1">
          <div className="orbit orbit-a" /><div className="orbit orbit-b" />
          <div className="profile-frame">
            <img src={assetUrl('assets/STUDENT_Wutthipat.png')} alt="Wutthipat Sriyangnok" />
            <div className="profile-code">ZUMO_1547</div>
          </div>
          <div className="float-chip chip-one hero-project-chip"><Code2 size={17} /><div><small>THIS WEBSITE</small><b>React · TypeScript · Supabase</b></div></div>
          <div className="float-chip chip-two hero-project-chip"><Microscope size={17} /><div><small>ENGINEERING PROJECT</small><b>ESP32-Powered Microfiber Filter</b></div></div>
          <div className="float-chip chip-three hero-project-chip"><Bot size={17} /><div><small>AI &amp; ROBOTICS</small><b>Python · micro:bit · CiRA</b></div></div>
          <div className="hero-proof-card">
            <Trophy size={19} />
            <div><span>LATEST HIGHLIGHT</span><b>Science Film Festival 2025</b><small>รางวัลชนะเลิศระดับมัธยมปลาย</small></div>
          </div>
          <div className="hero-review-stamp"><span>PORTFOLIO</span><strong>{new Date().getFullYear()}</strong><small>SCIENCE &amp; INNOVATION</small></div>
        </div>
      </section>

      <JudgeReview open={reviewOpen} onClose={() => setReviewOpen(false)} />

      <div className="marquee"><div>{[...marquee, ...marquee].map((item, i) => <span key={`${item}-${i}`}><i />{item}</span>)}</div></div>

      <section className="content-section home-impact">
        <header className="home-section-heading reveal">
          <div><span className="mono-label">// PORTFOLIO HIGHLIGHTS</span><h2>ผลงานที่วัดผลได้จริง</h2></div>
          <p>ตัวเลขสำคัญจากโครงงาน การแข่งขัน และกิจกรรมในแฟ้มสะสมผลงาน</p>
        </header>
        <div className="impact-grid">
          {impact.map(({ icon: Icon, value, title, detail, tone }, index) => <article className={`impact-card impact-${tone} reveal`} style={{ animationDelay: `${index * 90}ms` }} key={title}>
            <div className="impact-top"><Icon aria-hidden="true" /><span>0{index + 1}</span></div>
            <strong>{value}</strong><h3>{title}</h3><p>{detail}</p>
          </article>)}
        </div>
        <Link className="impact-link" to="/about">ดูเส้นทางและรางวัลทั้งหมด <ArrowRight size={16} /></Link>
      </section>

      <section className="content-section home-tech-section">
        <header className="home-section-heading reveal">
          <div><span className="mono-label">// TECHNOLOGY USED IN PROJECTS</span><h2>เทคโนโลยีที่ผมใช้ทำโปรเจกต์</h2></div>
          <p>ผมใช้เครื่องมือแต่ละตัวกับงานจริง ตั้งแต่ทำเว็บและเกม ไปจนถึงเขียนโปรแกรมควบคุมเซนเซอร์กับบอร์ด ESP32</p>
        </header>
        <div className="tech-grid">
          {techStack.map((tech, i) => {
            const Icon = tech.icon ? techIcons[tech.icon] : null
            return (
            <article className="tech-card reveal" style={{ animationDelay: `${i * 60}ms` }} key={tech.name}>
              {tech.image
                ? <img className="tech-logo" src={assetUrl(tech.image)} alt={`${tech.name} logo`} />
                : Icon ? <Icon className="tech-lucide" aria-hidden="true" /> : null}
              <h3>{tech.name}</h3><p>{tech.detail}</p>
            </article>
          )})}
        </div>
      </section>

      <section className="content-section split-feature home-evidence">
        <article className="feature-card reveal">
          <div className="feature-icon purple"><Code2 /></div>
          <span className="mono-label">APPLIED SKILLS</span>
          <h2>ทักษะที่ใช้สร้างงานจริง</h2>
          <p>เรียนรู้จากการออกแบบ ทดลอง แก้ข้อผิดพลาด และนำผลงานไปแข่งขันหรือใช้งานจริง</p>
          <div className="applied-skill-list">
            {appliedSkills.map(([name, detail]) => <div key={name}><b>{name}</b><span>{detail}</span><ArrowRight size={15} /></div>)}
          </div>
        </article>

        <article className="feature-card project-callout reveal delay-1">
          <div className="feature-icon pink"><Film /></div>
          <span className="mono-label">SELECTED WORK</span>
          <h2>จากปัญหาสู่ผลงาน</h2>
          <div className="selected-work-list">
            <p><strong>เครื่องกรองไมโครไฟเบอร์</strong><span>ระบบกรองเส้นใยจากน้ำทิ้งเครื่องซักผ้า ควบคุมด้วย ESP32</span></p>
            <p><strong>Smart Agriculture</strong><span>วิเคราะห์ข้อมูลและควบคุมการเพาะปลูกด้วย micro:bit</span></p>
            <p><strong>Green Job Film</strong><span>สื่อเรื่องการจัดการขยะกำพร้าและสิ่งแวดล้อมอย่างยั่งยืน</span></p>
          </div>
          <Link to="/projects">ดูภาพและรายละเอียดแต่ละโปรเจกต์ <ArrowRight size={16} /></Link>
        </article>
      </section>

      <section className="content-section home-discover reveal">
        <div><span className="mono-label">// ดูผลงานเพิ่มเติม</span><h2>ผลงานที่ผมลงมือทำ</h2><p>รวมโครงงาน IoT, AI, Robotics, Game Development และเว็บไซต์ Portfolio นี้ พร้อมภาพและรายละเอียดของแต่ละงาน</p></div>
        <Link className="button" to="/projects"><FolderOpen size={18} /> เปิดดูผลงานทั้งหมด <ArrowRight size={17} /></Link>
      </section>
    </>
  )
}
