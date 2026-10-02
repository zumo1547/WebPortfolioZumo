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

export function HomePage() {
  return (
    <>
      <Seo title="Wutthipat Sriyangnok — Creative Developer Portfolio" description="แฟ้มสะสมผลงานของ Wutthipat Sriyangnok นักเรียนมัธยมศึกษาปีที่ 6 รวมผลงาน IoT, AI, Robotics, Game Development และ Web Development" path="/" />
      <section className="hero section-pad home-hero">
        <div className="hero-copy reveal">
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
