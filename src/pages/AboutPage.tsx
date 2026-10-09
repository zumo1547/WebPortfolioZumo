import { Fragment, useEffect, useRef } from 'react'
import {
  Award,
  BookOpen,
  Bot,
  Boxes,
  BrainCircuit,
  Code2,
  Cpu,
  Database,
  Gamepad2,
  GitBranch,
  GraduationCap,
  Lightbulb,
  MapPin,
  Medal,
  ScanSearch,
  Trophy,
  UsersRound,
} from 'lucide-react'
import { PageHeader } from '../components/PageHeader'
import { Seo } from '../components/Seo'
import { assetUrl } from '../lib/supabase'
import './AboutPage.css'

const timeline = [
  { icon: <Gamepad2 />, title: 'Game Development', text: 'เริ่มต้นจากการสร้างเกมใน Roblox Studio และเข้ารวมค่ายทำเกมโดยใช้โปรแกรม Unity พร้อมเรียนรู้ Lua และ C# จากพัฒนาเกมด้วยตัวเอง' },
  { icon: <Cpu />, title: 'IoT & Robotics', text: 'พัฒนาระบบด้วย ESP32, Arduino, Micro:bit โดยนำเซ็นเซอร์มาร่วมใช้เข้ากับบอร์ดต่างๆและพัฒนาระบบ Smart Farm ไปจนการทำเครื่องกรอกไมโครไฟเบอร์อัตโนมัติ' },
  { icon: <BrainCircuit />, title: 'AI & Data', text: 'เรียนรู้ Computer Vision ผ่านการเทรนโมเดล และวิเคราะห์ข้อมูลด้วย Python, OpenCV, Google Colab และการใช้งาน CiRA CORE' },
  { icon: <Database />, title: 'Web & Database', text: 'พัฒนาเว็บด้วย React, TypeScript, PostgreSQL และ Supabase ให้สามารถใช้งานได้ทุกแพลตฟอร์ม' },
]

const highlights = [
  { value: '3.58', label: 'GPAX', detail: 'เกรดรวมมัธยมปลาย 4 เทอม' },
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
    text: 'จัดการเวอร์ชันของโค้ดด้วย GitHub และให้ Vercel build กับ Deploy เว็บไซต์ทุกครั้งที่อัปเดต Main',
  },
]

const whatDrivesMe = [
  { icon: Bot, title: 'การแข่งขันหุ่นยนต์เดินตามเส้น', text: 'ในสนามที่แข่งขันผมทำหน้าที่ปรับทั้งความเร็ว ค่าเซนเซอร์ และโค้ด จนกว่าจะให้หุ่นยนต์วิ่งตามเส้นแม่นขึ้นและทำให้สามารถแข่งขันได้' },
  { icon: Boxes, title: 'การออกแบบ 3 มิติให้เป็นชิ้นงาน', text: 'ผมฝึกออกแบบโมเดลด้วย SketchUp ตอนแข่งศิลปหัตถกรรม จากนั้นผมก็ศึกษาโปรแกรม Blender, SolidWorks จึงทำให้สามารถออกแบบชิ้นงานและขึ้นรูปการพิมพ์ 3 มิติได้' },
  { icon: Cpu, title: 'เครื่องกรองไมโครไฟเบอร์อัตโนมัติด้วย ESP32', text: 'ผมทำหน้าที่เขียนโค้ดลงในบอร์ด ESP32 ออกแบบตัวเครื่อง พร้อมทดสอบกับสามาชิกภายในทีมจนสามารถแก้ไขปัญหาได้โดยเส้นใยจากไมโครไฟเบอร์ที่มาจากน้ำทิ้งจากเครื่องซักผ้าที่มาจากการตกตะกอน 15 นาทีสามารถลดจาก 137 เหลือ 9 ชิ้น' },
  { icon: ScanSearch, title: 'การใช้ AI (Artificial Intelligence) ให้สามารถรวมทำงานกับอุปกรณ์ต่างๆได้', text: 'การแข่งขัน WRG Thailand Championship 2025 ผมพัฒนาการเทรนโมเดลและใช้กล้องตรวจวัตถุ แล้วส่งผลผ่าน Arduino Uno R3 จำนวน 2 ชุด ร่วมทำงานกับแขนกลและสั่งการทำงานมอเตอร์บนสายพาน' },
]

const mindsetText = 'เวลาหุ่นยนต์วิ่งหลุดเดินตามเส้น ผมก็กลับไปเช็กค่าจากเซนเซอร์ ส่วนเครื่องกรองไมโครไฟเบอร์ แม้จะทำงานได้แล้ว ผมก็ต้องคอยเช็คประสิทธิภาพของเครื่องหลังกรอง จึงเป็นสิ่งที่ทำให้ผมค่อยๆ แก้ไขปัญหาแล้วลองทดสอบอีกครั้ง เพื่อดูว่าที่สิ่งแก้ไปได้ผลดีขึ้นจริงไหมและผมก็เป็นที่ไม่ยอมแพ้อะไรง่ายๆจนกว่าจะทำสิ่งนั้นสำเร็จ'
const mindsetHighlight = 'ผมก็เป็นที่ไม่ยอมแพ้อะไรง่ายๆจนกว่าจะทำสิ่งนั้นสำเร็จ'
const visionText = 'ถ้าแม้ว่าเราจะมีปัญญาประดิษฐ์ที่สามารถวิเคราะห์ข้อมูลได้ดีแค่ไหน ถ้าเราไม่มีอุปกรณ์ที่สามารถเก็บข้อมูลได้อย่างมีประสิทธิภาพ เช่น ภาพถ่าย อุณหภูมิ หรือความชื้น ก็จะไม่สามารถใช้ประโยชน์จากปัญญาประดิษฐ์ได้อย่างเต็มที่ เพราะฉะนั้นผมจึงมองว่า "ถ้ามีระบบ IoT ที่ดี ซึ่งเป็นรากฐานที่สามารถพัฒนาปัญญาประดิษฐ์ และซอฟต์แวร์ที่ดีได้"'
const visionQuote = '"ถ้ามีระบบ IoT ที่ดี ซึ่งเป็นรากฐานที่สามารถพัฒนาปัญญาประดิษฐ์ และซอฟต์แวร์ที่ดีได้"'
const originText = 'ผมชอบคอมพิวเตอร์มาตั้งแต่เด็กและชอบเรียนรู้อะไรใหม่ๆอยู่ตลอด จุดเปลี่ยนของผมคือในช่วงมัธยมต้น'
const originHighlight = 'จุดเปลี่ยนของผมคือในช่วงมัธยมต้น'
const thaiWordSegmenter = typeof Intl.Segmenter === 'function'
  ? new Intl.Segmenter('th', { granularity: 'word' })
  : null

function ReadableThai({ text }: { text: string }) {
  if (!thaiWordSegmenter) return text
  return <>{Array.from(thaiWordSegmenter.segment(text), ({ segment, isWordLike }, index) =>
    isWordLike && /\p{Script=Thai}/u.test(segment)
      ? <span className="about-thai-word" key={index}>{segment}</span>
      : <Fragment key={index}>{segment}</Fragment>,
  )}</>
}

function HighlightedThai({ text, phrase, className }: { text: string; phrase: string; className: string }) {
  const start = text.indexOf(phrase)
  if (start < 0) return <ReadableThai text={text} />
  return <>
    <ReadableThai text={text.slice(0, start)} />
    <strong className={className}><ReadableThai text={phrase} /></strong>
    <ReadableThai text={text.slice(start + phrase.length)} />
  </>
}

export function AboutPage() {
  const pageRef = useRef<HTMLElement>(null)

  useEffect(() => {
    const elements = pageRef.current?.querySelectorAll<HTMLElement>('.about-reveal')
    if (!elements) return
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
    }, { threshold: 0.08, rootMargin: '0px 0px -36px 0px' })
    elements.forEach((element) => observer.observe(element))
    return () => observer.disconnect()
  }, [])

  return (
    <section className="content-section page-section about-page" ref={pageRef}>
      <Seo title="About — Wutthipat Sriyangnok" description="รู้จัก Wutthipat Sriyangnok นักเรียนมัธยมศึกษาปีที่ 6 พร้อมเส้นทางการเรียนรู้ รางวัล และเครื่องมือที่ใช้สร้างผลงาน" path="/about" />
      <PageHeader eyebrow="PROFILE DATABASE" title="ABOUT" accent="ME">สิ่งที่ผมสนใจ ผลงานที่เคยทำ และเครื่องมือที่ใช้พัฒนาแต่ละโปรเจกต์</PageHeader>

      <div className="about-signal-strip about-reveal" aria-label="ข้อมูลปัจจุบัน">
        <article><span>01 / CURRENT</span><b>มัธยมศึกษาปีที่ 6</b><small>Science &amp; Innovation</small></article>
        <article><span>02 / FOCUS</span><b>IoT · AI · Web</b><small>สร้างระบบจากปัญหาในชีวิตประจำวัน</small></article>
        <article><span>03 / PROCESS</span><b>Build · Test · Improve</b><small>ทดลอง วัดผล และพัฒนาต่อยอด</small></article>
      </div>

      <div className="about-grid">
        <article className="profile-panel about-reveal">
          <div className="panel-line" />
          <img src={assetUrl('assets/STUDENT_Wutthipat.png')} alt="Wutthipat Sriyangnok" />
          <div className="profile-panel-copy">
            <span className="mono-label">// IDENTITY</span>
            <h2>Wutthipat<br /><span>Sriyangnok</span></h2>
            <p><ReadableThai text="“Zumo” — นักเรียนชั้นมัธยมศึกษาปีที่ 6 แผนการเรียนวิทยาศาสตร์–นวัตกรรม ผมชอบพัฒนาเกี่ยวกับ เว็บไซต์ หุ่นยนต์ และโปรเจกต์ด้าน IoT กับ AI แล้วนำสิ่งที่ได้เรียนรู้มาปรับใช้แก้ปัญหาที่เจอระหว่างลงมือทำ" /></p>
            <div className="profile-meta"><MapPin size={16} /> Samut Prakan, Thailand</div>
            <div className="profile-meta"><GraduationCap size={16} /> Nawaminthrachinuthit Triamudomsuksapattanakarn School</div>
            <div className="profile-meta"><BookOpen size={16} /> Science &amp; Innovation Program</div>
            <div className="profile-capabilities"><span>Problem Solving</span><span>Teamwork</span><span>Rapid Learning</span></div>
            <div className="profile-build-credit"><Code2 size={18} /><div><span>THIS PORTFOLIO</span><b><ReadableThai text="พัฒนาเว็บไซต์ Portfolio นี้ด้วยตนเองโดยใช้ปัญญาประดิษฐ์(AI) เป็นเครื่องมือในการช่วยทำงาน" /></b><small>React · TypeScript · Supabase · Vercel</small><em>AI assistant: OpenAI Codex (GPT-6)</em></div></div>
          </div>
        </article>

        <div className="timeline">
          <div className="timeline-heading"><span className="mono-label">// LEARNING PATH</span><b><ReadableThai text="สิ่งที่ผมได้่เรียนรู้และพัฒนาต่อในอนาคต" /></b></div>
          {timeline.map((item, index) => (
            <article className="timeline-item about-reveal" key={item.title}>
              <div className="timeline-icon">{item.icon}</div>
              <div><span>0{index + 1}</span><h3>{item.title}</h3><p><ReadableThai text={item.text} /></p></div>
            </article>
          ))}
        </div>
      </div>

      <section className="portfolio-proof about-reveal" aria-labelledby="portfolio-proof-title">
        <header>
          <div><span className="mono-label">// VERIFIED HIGHLIGHTS</span><h2 id="portfolio-proof-title">ตัวเลขจากผลงานจริง</h2></div>
          <p>ข้อมูลสรุปจากแฟ้มสะสมผลงาน ครอบคลุมผลการเรียน การแข่งขัน และผลทดสอบโครงงาน</p>
        </header>
        <div className="highlight-grid">
          {highlights.map((item) => <article key={item.label}><strong>{item.value}</strong><b>{item.label}</b><span>{item.detail}</span></article>)}
        </div>
      </section>

      <section className="achievement-board about-reveal" aria-labelledby="achievement-title">
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

      <section className="about-vision" aria-labelledby="about-vision-title">
        <header className="about-vision-heading about-reveal">
          <span className="mono-label">// WHAT DRIVES ME</span>
          <h2 id="about-vision-title"><ReadableThai text="เพราะอะไรที่อยากให้ผมพัฒนาต่อไปในอนาคต?" /></h2>
        </header>
        <div className="about-vision-intro about-reveal">
          <div><span className="about-vision-kicker">จุดเริ่มต้น</span><h3><HighlightedThai text={originText} phrase={originHighlight} className="about-origin-emphasis" /></h3></div>
          <p><ReadableThai text="โดยในช่วงมัธยมต้น คุณครูคอมพิวเตอร์สอนผมรู้จักบอร์ด และการทำงานของหุ่นยนต์ การทำเว็บเบื้องต้น จากเดิมที่ชอบอยู่แล้ว จึงเริ่มอยากฝึกเขียนโปรแกรมและอยากลองสร้างชิ้นใหม่ๆขึ้นด้วยตัวเอง" /></p>
        </div>
        <div className="about-vision-grid">
          {whatDrivesMe.map(({ icon: Icon, title, text }) => <article className="about-reveal" key={title}>
            <span className="about-vision-icon"><Icon size={20} aria-hidden="true" /></span>
            <div><h3><ReadableThai text={title} /></h3><p><ReadableThai text={text} /></p></div>
          </article>)}
        </div>
        <section className="about-vision-story about-reveal" aria-labelledby="about-mindset-title">
          <header className="about-vision-story-heading">
            <span className="about-vision-story-icon"><Lightbulb size={23} aria-hidden="true" /></span>
            <div><span className="mono-label">// MINDSET &amp; VISION</span><h3 id="about-mindset-title">ทัศนคติและวิสัยทัศน์</h3></div>
          </header>
          <div className="about-vision-story-grid">
            <div className="about-vision-story-panel"><span className="mono-label">01 / MINDSET</span><p><HighlightedThai text={mindsetText} phrase={mindsetHighlight} className="about-mindset-emphasis" /></p></div>
            <div className="about-vision-story-panel"><span className="mono-label">02 / VISION</span><p><ReadableThai text={visionText.slice(0, -visionQuote.length)} /><strong className="about-vision-quote"><ReadableThai text={visionQuote} /></strong></p></div>
          </div>
        </section>
      </section>

      <section className="portfolio-build about-reveal" aria-labelledby="portfolio-build-title">
        <header>
          <div><span className="mono-label">// HOW I BUILT THIS WEBSITE</span><h2 id="portfolio-build-title">เว็บไซต์นี้พัฒนาด้วยอะไรบ้าง</h2></div>
          <p><ReadableThai text="ตั้งแต่เขียนหน้าเว็บ จัดการฐานข้อมูล ใช้ AI (Artificial Intelligence) ในการช่วยตรวจและแก้ไข ไปจนถึงการนำเว็บไซต์ขึ้นมาใช้งาน" /></p>
        </header>
        <div className="portfolio-build-grid">
          {portfolioWorkflow.map(({ icon: Icon, title, stack, text }, index) => <article key={title}>
            <div className="portfolio-build-top"><span>0{index + 1}</span><Icon aria-hidden="true" /></div>
            <h3>{title}</h3>
            <b>{stack}</b>
            <p><ReadableThai text={text} /></p>
          </article>)}
        </div>
        <div className="ai-disclosure"><Bot aria-hidden="true" /><p><strong>AI ช่วยในส่วนไหน?</strong> <ReadableThai text="ใช้ OpenAI Codex ที่ทำงานด้วย GPT-6 เป็นผู้ช่วยตรวจโค้ด แนะนำวิธีแก้บั๊ก และช่วยทดสอบหน้าเว็บ ส่วนข้อมูลส่วนตัว เนื้อหาผลงาน รูปภาพ และการตัดสินใจออกแบบมาจากผม" /></p></div>
      </section>
    </section>
  )
}
