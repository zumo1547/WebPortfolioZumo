import { useEffect, useState } from 'react'
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
import { fallbackProjects, techStack } from '../data'
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

const projectSlug = (project: Project) => project.slug || `project-${project.id}`
const projectSummary = (description: string) => description
  .replace(/\*\*([^*\n]+)\*\*/g, '$1')
  .replace(/\*([^*\n]+)\*/g, '$1')
  .replace(/\s+/g, ' ')
  .trim()

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

function ProjectSlideBackdrop({ projects }: { projects: Project[] }) {
  const illustrated = projects.filter((project) => project.images?.[0]).slice(0, 6)
  if (illustrated.length === 0) return null

  const slides = Array.from({ length: 10 }, (_, index) => illustrated[index % illustrated.length])

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
  const [projects, setProjects] = useState<Project[]>([])
  const [projectsLoading, setProjectsLoading] = useState(true)

  useEffect(() => {
    let active = true
    async function loadProjects() {
      const { data, error } = await supabase.from('projects').select('*').order('created_at', { ascending: false })
      if (!active) return
      setProjects(sortProjectsByImportance(error ? fallbackProjects : ((data as Project[]) || [])))
      setProjectsLoading(false)
    }
    void loadProjects()
    return () => { active = false }
  }, [])

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

  return (
    <>
      <Seo title="Wutthipat Sriyangnok — Creative Developer Portfolio" description="แฟ้มสะสมผลงานของ Wutthipat Sriyangnok นักเรียนมัธยมศึกษาปีที่ 6 รวมผลงาน IoT, AI, Robotics, Game Development และ Web Development" path="/" />
      <section className="hero section-pad home-hero">
        <ProjectSlideBackdrop projects={projects} />
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
        <article className="feature-card applied-project-card home-reveal">
          <div className="feature-icon purple"><Code2 /></div>
          <span className="mono-label">APPLIED SKILLS</span>
          <h2>ทักษะที่ใช้สร้างงานจริง</h2>
          <p>โปรเจกต์ที่ผมทำไว้ กดแต่ละงานเพื่อดูภาพและรายละเอียด</p>
          <div className="portfolio-source">
            <div><span className="mono-label">THIS WEBSITE</span><b>Zumo Dev Portfolio</b><small>React · TypeScript · Supabase</small></div>
            <div className="portfolio-source-links">
              <a href="https://webportfoliozumo.vercel.app/" target="_blank" rel="noopener noreferrer">ดูเว็บไซต์ <ArrowRight size={13} /></a>
              <a href="https://github.com/zumo1547/WebPortfolioZumo" target="_blank" rel="noopener noreferrer"><Github size={14} /> GitHub</a>
            </div>
          </div>
          <div className="applied-project-heading"><span>PROJECT ARCHIVE</span><small>{projectsLoading ? 'กำลังโหลด...' : `${projects.length} โปรเจกต์ · เลื่อนดูได้`}</small></div>
          <div className="applied-project-list" tabIndex={0} aria-label="รายการโปรเจกต์ เลื่อนดูได้">
            {projects.map((project) => <Link className="applied-project-item" to={`/projects/${projectSlug(project)}`} key={project.id}>
              <img src={projectImageUrl(project.images?.[0])} alt="" loading="lazy" />
              <span className="applied-project-copy"><b>{project.name}</b><small>{projectSummary(project.description)}</small></span>
              <ArrowRight size={16} aria-hidden="true" />
            </Link>)}
            {!projectsLoading && projects.length === 0 && <p className="applied-project-empty">ยังไม่มีโปรเจกต์ในคลังผลงาน</p>}
          </div>
        </article>

        <article className="feature-card project-callout home-reveal">
          <div className="feature-icon pink"><CircuitBoard /></div>
          <span className="mono-label">BEHIND THE WORK</span>
          <h2>ผมทำงานยังไง</h2>
          <p>เครื่องกรองไมโครไฟเบอร์เป็นตัวอย่างของวิธีที่ผมทำโปรเจกต์กับทีม</p>
          <div className="home-process-list">
            <div><span>01</span><div><strong>เริ่มจากสิ่งที่สงสัย</strong><p>น้ำทิ้งจากเครื่องซักผ้ามีเส้นใยเล็ก ๆ ปนอยู่ เราเลยลองหาวิธีดักมันก่อนลงท่อ</p></div></div>
            <div><span>02</span><div><strong>ทำต้นแบบแล้วแก้</strong><p>ประกอบเครื่องกรอง เขียนโค้ด ESP32 และปรับส่วนที่ยังทำงานไม่ตรงตามที่คิด</p></div></div>
            <div><span>03</span><div><strong>นับผลที่ได้จริง</strong><p>ทดสอบกับน้ำทิ้งแล้วนับเส้นใยที่หลุดรอด จาก 137 ชิ้นเหลือ 9 ชิ้น</p></div></div>
          </div>
          <Link to="/about">อ่านเรื่องราวและสิ่งที่ผมสนใจ <ArrowRight size={16} /></Link>
        </article>
      </section>

      <section className="content-section home-discover home-reveal">
        <div><span className="mono-label">// ดูผลงานเพิ่มเติม</span><h2>ผลงานที่ผมลงมือทำ</h2><p>รวมโครงงาน IoT, AI, Robotics, Game Development และเว็บไซต์ Portfolio นี้ พร้อมภาพและรายละเอียดของแต่ละงาน</p></div>
        <Link className="button" to="/projects"><FolderOpen size={18} /> เปิดดูผลงานทั้งหมด <ArrowRight size={17} /></Link>
      </section>
    </>
  )
}
