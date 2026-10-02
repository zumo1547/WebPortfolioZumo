import {
  Award,
  BookOpen,
  Bot,
  BrainCircuit,
  Code2,
  Cpu,
  Database,
  Gamepad2,
  GitBranch,
  GraduationCap,
  MapPin,
  Medal,
  Rocket,
  Trophy,
  UsersRound,
} from 'lucide-react'
import { PageHeader } from '../components/PageHeader'
import { Seo } from '../components/Seo'
import { assetUrl } from '../lib/supabase'
import './AboutPage.css'

const timeline = [
  { icon: <Gamepad2 />, title: 'Game Development', text: 'เริ่มต้นจากการสร้างเกมและระบบใน Roblox Studio และ Unity พร้อมเรียนรู้ Lua และ C# ผ่านการลงมือทำจริง' },
  { icon: <Cpu />, title: 'IoT & Robotics', text: 'พัฒนาระบบด้วย ESP32, Arduino, micro:bit, Sensor และ Blynk ตั้งแต่ Smart Farm ไปจนถึงระบบอัตโนมัติ' },
  { icon: <BrainCircuit />, title: 'AI & Data', text: 'ทดลอง Computer Vision การเทรนโมเดล และวิเคราะห์ข้อมูลด้วย Python, OpenCV, Google Colab และ CiRA CORE' },
  { icon: <Rocket />, title: 'Web & Cloud', text: 'พัฒนาเว็บสมัยใหม่ด้วย React, TypeScript, PostgreSQL และ Supabase ให้ใช้งานได้ทั้งบนคอมพิวเตอร์และโทรศัพท์' },
]

const highlights = [
  { value: '3.77', label: 'GPAX', detail: 'ระดับมัธยมศึกษาตอนต้น' },
  { value: '91.97%', label: 'FILTER EFFICIENCY', detail: 'ผลทดสอบเครื่องกรองไมโครไฟเบอร์' },
  { value: 'TOP 30', label: 'AI HACKATHON', detail: 'SPU AI Prompt Mini Hackathon 2025' },
  { value: '70', label: 'SCHOOLS', detail: 'ผู้ร่วม Science Film Festival 2025' },
]

const achievements = [
  {
    icon: Trophy,
    title: 'ชนะเลิศ Science Film Festival 2025',
    text: 'รางวัลชนะเลิศระดับมัธยมศึกษาตอนปลาย จากผลงานวิดีโอ “ขยะกำพร้า” ในหัวข้อ Green Job',
  },
  {
    icon: Medal,
    title: 'เหรียญทองแดง OCOP',
    text: 'อันดับ 4 รอบชิงชนะเลิศ โครงงานเครื่องกรองไมโครไฟเบอร์อัตโนมัติที่ควบคุมด้วย ESP32',
  },
  {
    icon: Award,
    title: 'Micro:bit Thailand Challenge 2026',
    text: 'เหรียญทองแดงระดับมัธยมปลาย รายการ AIoT & Data Collection จากระบบเกษตรอัจฉริยะ',
  },
  {
    icon: UsersRound,
    title: 'ศิลปหัตถกรรม ครั้งที่ 73',
    text: 'เหรียญทอง รองชนะเลิศอันดับ 2 การออกแบบสิ่งของเครื่องใช้ด้วยโปรแกรมคอมพิวเตอร์',
  },
]

const portfolioWorkflow = [
  {
    icon: Code2,
    title: 'Frontend Development',
    stack: 'React · TypeScript · Vite',
    text: 'ผมออกแบบหน้าเว็บ เขียน Component และปรับ Responsive ให้ใช้งานได้ทั้งบนคอมพิวเตอร์และโทรศัพท์',
  },
  {
    icon: Database,
    title: 'Data & Authentication',
    stack: 'Supabase · PostgreSQL · Storage',
    text: 'ใช้เก็บข้อมูลและรูปโปรเจกต์ พร้อมระบบสมัครสมาชิก เข้าสู่ระบบ และกำหนดสิทธิ์ของแอดมิน',
  },
  {
    icon: Bot,
    title: 'AI Coding Assistant',
    stack: 'OpenAI Codex · GPT-6',
    text: 'ใช้ช่วยตรวจโค้ด หาแนวทางแก้บั๊ก และทบทวน Responsive โดยผมเป็นคนกำหนดเนื้อหา ออกแบบ และเลือกการแก้ไขทั้งหมด',
  },
  {
    icon: GitBranch,
    title: 'Version & Deployment',
    stack: 'GitHub · Vercel',
    text: 'จัดการเวอร์ชันของโค้ดด้วย GitHub และให้ Vercel build กับ deploy เว็บไซต์ทุกครั้งที่อัปเดต main',
  },
]

export function AboutPage() {
  return (
    <section className="content-section page-section about-page">
      <Seo title="About — Wutthipat Sriyangnok" description="รู้จัก Wutthipat Sriyangnok นักเรียนมัธยมศึกษาปีที่ 6 พร้อมเส้นทางการเรียนรู้ รางวัล และเครื่องมือที่ใช้สร้างผลงาน" path="/about" />
      <PageHeader eyebrow="PROFILE DATABASE" title="ABOUT" accent="ME">สิ่งที่ผมสนใจ ผลงานที่เคยทำ และเครื่องมือที่ใช้พัฒนาแต่ละโปรเจกต์</PageHeader>

      <div className="about-signal-strip reveal" aria-label="ข้อมูลปัจจุบัน">
        <article><span>01 / CURRENT</span><b>มัธยมศึกษาปีที่ 6</b><small>Science &amp; Innovation</small></article>
        <article><span>02 / FOCUS</span><b>IoT · AI · Web</b><small>สร้างระบบจากปัญหาใกล้ตัว</small></article>
        <article><span>03 / PROCESS</span><b>Build · Test · Improve</b><small>ทดลอง วัดผล และพัฒนาต่อ</small></article>
      </div>

      <div className="about-grid">
        <article className="profile-panel reveal">
          <div className="panel-line" />
          <img src={assetUrl('assets/STUDENT_Wutthipat.png')} alt="Wutthipat Sriyangnok" />
          <div className="profile-panel-copy">
            <span className="mono-label">// IDENTITY</span>
            <h2>Wutthipat<br /><span>Sriyangnok</span></h2>
            <p>“Zumo” — นักเรียนชั้นมัธยมศึกษาปีที่ 6 แผนการเรียนวิทยาศาสตร์–นวัตกรรม ผมชอบทดลองทำเกม ระบบ IoT หุ่นยนต์ AI และเว็บ แล้วนำสิ่งที่เรียนรู้ไปแก้ปัญหาในโปรเจกต์จริง</p>
            <div className="profile-meta"><MapPin size={16} /> Samut Prakan, Thailand</div>
            <div className="profile-meta"><GraduationCap size={16} /> Nawaminthrachinuthit Triamudomsuksapattanakarn School</div>
            <div className="profile-meta"><BookOpen size={16} /> Science &amp; Innovation Program</div>
            <div className="profile-capabilities"><span>Problem Solving</span><span>Teamwork</span><span>Rapid Learning</span></div>
            <div className="profile-build-credit"><Code2 size={18} /><div><span>THIS PORTFOLIO</span><b>พัฒนาเว็บไซต์ Portfolio นี้ด้วยตัวเอง</b><small>React · TypeScript · Supabase · Vercel</small><em>AI assistant: OpenAI Codex (GPT-6)</em></div></div>
          </div>
        </article>

        <div className="timeline">
          <div className="timeline-heading"><span className="mono-label">// LEARNING PATH</span><b>สิ่งที่ผมกำลังพัฒนา</b></div>
          {timeline.map((item, index) => (
            <article className="timeline-item reveal" style={{ animationDelay: `${index * 80}ms` }} key={item.title}>
              <div className="timeline-icon">{item.icon}</div>
              <div><span>0{index + 1}</span><h3>{item.title}</h3><p>{item.text}</p></div>
            </article>
          ))}
        </div>
      </div>

      <section className="portfolio-build reveal" aria-labelledby="portfolio-build-title">
        <header>
          <div><span className="mono-label">// HOW I BUILT THIS WEBSITE</span><h2 id="portfolio-build-title">เว็บไซต์นี้พัฒนาด้วยอะไรบ้าง</h2></div>
          <p>ตั้งแต่เขียนหน้าเว็บ จัดการฐานข้อมูล ใช้ AI ช่วยตรวจงาน ไปจนถึงนำเว็บขึ้นใช้งานจริง</p>
        </header>
        <div className="portfolio-build-grid">
          {portfolioWorkflow.map(({ icon: Icon, title, stack, text }, index) => <article key={title}>
            <div className="portfolio-build-top"><span>0{index + 1}</span><Icon aria-hidden="true" /></div>
            <h3>{title}</h3>
            <b>{stack}</b>
            <p>{text}</p>
          </article>)}
        </div>
        <div className="ai-disclosure"><Bot aria-hidden="true" /><p><strong>AI ช่วยในส่วนไหน?</strong> ใช้ OpenAI Codex ที่ทำงานด้วย GPT-6 เป็นผู้ช่วยตรวจโค้ด แนะนำวิธีแก้บั๊ก และช่วยทดสอบหน้าเว็บ ส่วนข้อมูลส่วนตัว เนื้อหาผลงาน รูปภาพ และการตัดสินใจออกแบบมาจากผม</p></div>
      </section>

      <section className="portfolio-proof reveal" aria-labelledby="portfolio-proof-title">
        <header>
          <div><span className="mono-label">// VERIFIED HIGHLIGHTS</span><h2 id="portfolio-proof-title">ตัวเลขจากผลงานจริง</h2></div>
          <p>ข้อมูลสรุปจากแฟ้มสะสมผลงาน ครอบคลุมผลการเรียน การแข่งขัน และผลทดสอบโครงงาน</p>
        </header>
        <div className="highlight-grid">
          {highlights.map((item) => <article key={item.label}><strong>{item.value}</strong><b>{item.label}</b><span>{item.detail}</span></article>)}
        </div>
      </section>

      <section className="achievement-board reveal" aria-labelledby="achievement-title">
        <div className="achievement-board-heading"><span className="mono-label">// SELECTED ACHIEVEMENTS</span><h2 id="achievement-title">ผลงานเด่นและรางวัล</h2></div>
        <div className="achievement-grid">
          {achievements.map(({ icon: Icon, title, text }, index) => <article key={title}>
            <div className="achievement-rank">0{index + 1}</div>
            <Icon aria-hidden="true" />
            <h3>{title}</h3>
            <p>{text}</p>
          </article>)}
        </div>
      </section>

      <div className="achievement-strip reveal"><Award /><div><b>เป้าหมายของผม</b><p>สร้างเทคโนโลยีที่แก้ปัญหาได้จริง วัดผลได้ และพัฒนาต่อจนเกิดประโยชน์ต่อผู้ใช้งานและสิ่งแวดล้อม</p></div></div>
    </section>
  )
}
