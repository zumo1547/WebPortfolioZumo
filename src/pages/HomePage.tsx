import { useEffect, useRef, useState } from 'react'
import {
  ArrowRight,
  Atom,
  Bot,
  Boxes,
  Braces,
  CircuitBoard,
  Code2,
  Cpu,
  Database,
  Film,
  FolderOpen,
  Gamepad2,
  Github,
  Medal,
  Microscope,
  Radio,
  ScanSearch,
  ServerCog,
  Trophy,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Seo } from '../components/Seo'
import { techStack } from '../data'
import { sortProjectsByImportance } from '../lib/projectRanking'
import { assetUrl, projectImageUrl, supabase } from '../lib/supabase'
import type { Project } from '../types'
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

const journeySteps = [
  {
    icon: Gamepad2,
    label: 'GAME DEVELOPMENT',
    title: 'เริ่มจากการสร้างเกม',
    text: 'ผมเริ่มเขียนระบบใน Roblox Studio ด้วย Lua แล้วลองทำเกมใน Unity ด้วย C# ได้ฝึกแก้โค้ดจากสิ่งที่เล่นและทดสอบเอง',
    detail: 'Roblox Studio · Unity',
  },
  {
    icon: Cpu,
    label: 'HARDWARE & IoT',
    title: 'จากหน้าจอสู่บอร์ดจริง',
    text: 'ต่อเซนเซอร์กับ ESP32 และ micro:bit ทำระบบ Smart Farm และเครื่องกรองไมโครไฟเบอร์ที่ทดสอบแล้วกรองได้ 91.97%',
    detail: 'ESP32 · micro:bit · Sensors',
  },
  {
    icon: ScanSearch,
    label: 'AI & DATA',
    title: 'ลองให้คอมพิวเตอร์มองเห็น',
    text: 'ผมใช้ Python, OpenCV และ CiRA CORE ทดลองตรวจจับวัตถุ ฝึกโมเดล และดูว่าข้อมูลที่เก็บมาช่วยแก้โจทย์ได้อย่างไร',
    detail: 'Python · OpenCV · CiRA CORE',
  },
  {
    icon: Trophy,
    label: 'COMPETITIONS',
    title: 'พางานออกไปแข่งขัน',
    text: 'ผมพาโครงงานไปแข่ง OCOP และ Micro:bit Thailand Challenge ส่วนหนังสั้น “ขยะกำพร้า” ที่ทำกับทีมได้รางวัลชนะเลิศ Science Film Festival 2025',
    detail: 'โครงงาน · การแข่งขัน · งานทีม',
  },
  {
    icon: Code2,
    label: 'THIS PORTFOLIO',
    title: 'เว็บนี้ก็เป็นผลงานอีกชิ้น',
    text: 'ผมทำหน้าเว็บด้วย React และ TypeScript ใช้ Supabase เก็บข้อมูลกับรูปภาพ เพื่อให้เปิดดูรายละเอียดผลงานแต่ละชิ้นได้บนคอมและโทรศัพท์',
    detail: 'React · TypeScript · Supabase',
  },
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

function ProjectSlideBackdrop() {
  const [projects, setProjects] = useState<Project[]>([])

  useEffect(() => {
    let active = true
    async function loadProjects() {
      const { data, error } = await supabase.from('projects').select('*').order('created_at', { ascending: false })
      if (active && !error) setProjects(sortProjectsByImportance((data as Project[]) || [])
        .filter((project) => project.images?.[0]).slice(0, 6))
    }
    void loadProjects()
    return () => { active = false }
  }, [])

  if (projects.length === 0) return null

  const slides = Array.from({ length: 10 }, (_, index) => projects[index % projects.length])

  return (
    <div className="hero-slide-backdrop" aria-hidden="true">
      <div className="hero-slide-plane">
        {[0, 1].map((lane) => (
          <div className={`hero-slide-lane hero-slide-lane-${lane + 1}`} key={lane}>
            <div className="hero-slide-reel">
              {[0, 1].map((copy) => (
                <div className="hero-slide-group" key={copy}>
                  {(lane % 2 === 0 ? slides : [...slides].reverse()).map((project, index) => (
                    <div className="hero-slide-card" key={`${project.id}-${index}`}>
                      <img src={projectImageUrl(project.images[0])} alt="" loading={lane === 0 && copy === 0 ? 'eager' : 'lazy'} decoding="async" />
                    </div>
                  ))}
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export function HomePage() {
  const journeyRef = useRef<HTMLElement>(null)

  useEffect(() => {
    const elements = document.querySelectorAll<HTMLElement>('.home-reveal')
    if (!('IntersectionObserver' in window)) {
      elements.forEach((element) => element.classList.add('is-visible'))
      return
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible')
          observer.unobserve(entry.target)
        }
      })
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' })
    elements.forEach((element) => observer.observe(element))
    return () => observer.disconnect()
  }, [])

  useEffect(() => {
    const journey = journeyRef.current
    if (!journey) return
    const track = journey.querySelector<HTMLElement>('.journey-track')
    if (!track) return

    let frame = 0
    const updateProgress = () => {
      frame = 0
      const bounds = track.getBoundingClientRect()
      const progress = Math.max(0, Math.min(1, (window.innerHeight * 0.62 - bounds.top) / bounds.height))
      journey.style.setProperty('--journey-progress', String(progress))
    }
    const scheduleUpdate = () => {
      if (!frame) frame = window.requestAnimationFrame(updateProgress)
    }

    updateProgress()
    window.addEventListener('scroll', scheduleUpdate, { passive: true })
    window.addEventListener('resize', scheduleUpdate)
    return () => {
      window.removeEventListener('scroll', scheduleUpdate)
      window.removeEventListener('resize', scheduleUpdate)
      if (frame) window.cancelAnimationFrame(frame)
    }
  }, [])

  return (
    <>
      <Seo title="Wutthipat Sriyangnok — Creative Developer Portfolio" description="แฟ้มสะสมผลงานของ Wutthipat Sriyangnok นักเรียนมัธยมศึกษาปีที่ 6 รวมผลงาน IoT, AI, Robotics, Game Development และ Web Development" path="/" />
      <section className="hero section-pad home-hero">
        <ProjectSlideBackdrop />
        <div className="hero-copy">
          <div className="eyebrow"><span className="online-dot" /> PORTFOLIO · ZUMO DEV · {new Date().getFullYear()}</div>
          <h1>CREATIVE<br /><span>DEVELOPER</span></h1>
          <p className="hero-thai">วุฒิภัทร ศรียางนอก — นักเรียนสายวิทยาศาสตร์และนวัตกรรม<br />สร้างเกม ระบบ IoT หุ่นยนต์ AI และเว็บจากการทดลองจริง</p>
          <div className="home-intro-tags" aria-label="ข้อมูลโดยย่อ">
            <span>มัธยมศึกษาปีที่ 6</span><span>Science &amp; Innovation</span><span>Samut Prakan</span>
          </div>
          <div className="hero-actions">
            <Link className="button" to="/projects"><FolderOpen size={18} /> สำรวจโปรเจกต์ <ArrowRight size={17} /></Link>
            <Link className="button secondary" to="/about">ประวัติและรางวัล</Link>
          </div>
          <div className="hero-status"><Radio size={16} /><span>LEARNING · BUILDING · IMPROVING</span></div>
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
        </div>
      </section>

      <div className="marquee"><div>{[...marquee, ...marquee].map((item, i) => <span key={`${item}-${i}`}><i />{item}</span>)}</div></div>

      <section className="home-journey content-section" ref={journeyRef} aria-labelledby="home-journey-title">
        <header className="journey-heading home-reveal">
          <span className="mono-label">// MY JOURNEY</span>
          <h2 id="home-journey-title">จากเกมแรกถึงเว็บที่คุณกำลังดู</h2>
          <p>ผมค่อย ๆ ลองทำงานหลายแบบ ทุกช่วงได้เรียนรู้จากการลงมือทำจริง</p>
        </header>
        <div className="journey-track">
          <span className="journey-road" aria-hidden="true" />
          <span className="journey-road-progress" aria-hidden="true" />
          <ol className="journey-steps">
            {journeySteps.map(({ icon: Icon, label, title, text, detail }, index) => (
              <li className={`journey-step home-reveal ${index % 2 ? 'journey-step-right' : 'journey-step-left'}`} key={title}>
                <span className="journey-marker" aria-hidden="true"><Icon size={19} /></span>
                <article className="journey-card">
                  <div className="journey-card-top"><span>0{index + 1} / 0{journeySteps.length}</span><span>{label}</span></div>
                  <h3>{title}</h3>
                  <p>{text}</p>
                  <small>{detail}</small>
                </article>
              </li>
            ))}
          </ol>
        </div>
        <div className="journey-finish home-reveal"><span>วันนี้ผมยังเรียนรู้และทำโปรเจกต์ใหม่อยู่เรื่อย ๆ</span><ArrowRight size={17} aria-hidden="true" /></div>
      </section>

      <section className="content-section home-impact">
        <header className="home-section-heading home-reveal">
          <div><span className="mono-label">// PORTFOLIO HIGHLIGHTS</span><h2>ผลงานที่วัดผลได้จริง</h2></div>
          <p>ตัวเลขสำคัญจากโครงงาน การแข่งขัน และกิจกรรมในแฟ้มสะสมผลงาน</p>
        </header>
        <div className="impact-grid">
          {impact.map(({ icon: Icon, value, title, detail, tone }, index) => <article className={`impact-card impact-${tone} home-reveal`} key={title}>
            <div className="impact-top"><Icon aria-hidden="true" /><span>0{index + 1}</span></div>
            <strong>{value}</strong><h3>{title}</h3><p>{detail}</p>
          </article>)}
        </div>
        <Link className="impact-link home-reveal" to="/about">ดูเส้นทางและรางวัลทั้งหมด <ArrowRight size={16} /></Link>
      </section>

      <section className="content-section home-tech-section">
        <header className="home-section-heading home-reveal">
          <div><span className="mono-label">// TECHNOLOGY USED IN PROJECTS</span><h2>เทคโนโลยีที่ผมใช้ทำโปรเจกต์</h2></div>
          <p>ผมใช้เครื่องมือแต่ละตัวกับงานจริง ตั้งแต่ทำเว็บและเกม ไปจนถึงเขียนโปรแกรมควบคุมเซนเซอร์กับบอร์ด ESP32</p>
        </header>
        <div className="tech-grid">
          {techStack.map((tech) => {
            const Icon = tech.icon ? techIcons[tech.icon] : null
            return (
            <article className="tech-card home-reveal" key={tech.name}>
              {tech.image
                ? <img className="tech-logo" src={assetUrl(tech.image)} alt={`${tech.name} logo`} />
                : Icon ? <Icon className="tech-lucide" aria-hidden="true" /> : null}
              <h3>{tech.name}</h3><p>{tech.detail}</p>
            </article>
          )})}
        </div>
      </section>

      <section className="content-section split-feature home-evidence">
        <article className="feature-card home-reveal">
          <div className="feature-icon purple"><Code2 /></div>
          <span className="mono-label">APPLIED SKILLS</span>
          <h2>ทักษะที่ใช้สร้างงานจริง</h2>
          <p>เรียนรู้จากการออกแบบ ทดลอง แก้ข้อผิดพลาด และนำผลงานไปแข่งขันหรือใช้งานจริง</p>
          <div className="applied-skill-list">
            {appliedSkills.map(([name, detail]) => <div key={name}><b>{name}</b><span>{detail}</span><ArrowRight size={15} /></div>)}
          </div>
        </article>

        <article className="feature-card project-callout home-reveal">
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

      <section className="content-section home-discover home-reveal">
        <div><span className="mono-label">// ดูผลงานเพิ่มเติม</span><h2>ผลงานที่ผมลงมือทำ</h2><p>รวมโครงงาน IoT, AI, Robotics, Game Development และเว็บไซต์ Portfolio นี้ พร้อมภาพและรายละเอียดของแต่ละงาน</p></div>
        <Link className="button" to="/projects"><FolderOpen size={18} /> เปิดดูผลงานทั้งหมด <ArrowRight size={17} /></Link>
      </section>
    </>
  )
}
