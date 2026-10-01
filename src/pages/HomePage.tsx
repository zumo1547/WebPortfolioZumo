import { ArrowRight, Bot, Code2, Cpu, FolderOpen, Gamepad2, Radio, Sparkles } from 'lucide-react'
import { Link } from 'react-router-dom'
import { techStack } from '../data'
import { assetUrl } from '../lib/supabase'

const marquee = ['GAME DEV', 'IoT SYSTEMS', 'REACT', 'TYPESCRIPT', 'ESP32', 'PYTHON', 'ROBOTICS', 'AI DETECTION', 'SUPABASE']

export function HomePage() {
  return (
    <>
      <section className="hero section-pad">
        <div className="hero-copy reveal">
          <div className="eyebrow"><span className="online-dot" /> PORTFOLIO · ZUMO DEV · {new Date().getFullYear()}</div>
          <h1>CREATIVE<br /><span>DEVELOPER</span></h1>
          <p className="hero-thai">สร้างสรรค์เกม ระบบ IoT หุ่นยนต์ และ AI<br />จากไอเดียสู่โปรเจกต์ที่ใช้งานได้จริง</p>
          <div className="hero-actions">
            <Link className="button" to="/projects"><FolderOpen size={18} /> ดูผลงาน <ArrowRight size={17} /></Link>
            <Link className="button secondary" to="/about">รู้จักฉันมากขึ้น</Link>
          </div>
          <div className="hero-status"><Radio size={16} /><span>AVAILABLE FOR LEARNING &amp; COLLABORATION</span></div>
        </div>
        <div className="hero-visual reveal delay-1">
          <div className="orbit orbit-a" /><div className="orbit orbit-b" />
          <div className="profile-frame">
            <img src={assetUrl('assets/STUDENT_Wutthipat.png')} alt="Wutthipat Sriyangnok" />
            <div className="profile-code">ZUMO_1547</div>
          </div>
          <div className="float-chip chip-one"><Cpu size={17} /> IoT BUILDER</div>
          <div className="float-chip chip-two"><Bot size={17} /> AI EXPLORER</div>
          <div className="float-chip chip-three"><Gamepad2 size={17} /> GAME DEV</div>
        </div>
      </section>

      <div className="marquee"><div>{[...marquee, ...marquee].map((item, i) => <span key={`${item}-${i}`}>✦ {item}</span>)}</div></div>

      <section className="content-section">
        <div className="section-label">// TECHNOLOGY STACK</div>
        <div className="tech-grid">
          {techStack.map((tech, i) => (
            <article className="tech-card reveal" style={{ animationDelay: `${i * 60}ms` }} key={tech.name}>
              {tech.image
                ? <img className="tech-logo" src={assetUrl(tech.image)} alt={`${tech.name} logo`} />
                : <span className="tech-icon" aria-hidden="true">{tech.icon}</span>}
              <h3>{tech.name}</h3><p>{tech.detail}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="content-section split-feature">
        <article className="feature-card reveal">
          <div className="feature-icon purple"><Code2 /></div>
          <span className="mono-label">PROGRAMMING SKILLS</span>
          <h2>Build. Test. Learn.</h2>
          <p>ทดลองเทคโนโลยีใหม่ผ่านโปรเจกต์จริง ตั้งแต่เว็บแอปไปจนถึงระบบสมองกลฝังตัว</p>
          {[['Python', 65], ['ESP32 / Arduino', 80], ['React / TypeScript', 72]].map(([name, value]) => (
            <div className="skill-row" key={name as string}><div><span>{name}</span><b>{value}%</b></div><i><em style={{ width: `${value}%` }} /></i></div>
          ))}
        </article>
        <article className="feature-card project-callout reveal delay-1">
          <div className="feature-icon pink"><Sparkles /></div>
          <span className="mono-label">SELECTED WORK</span>
          <h2>Ideas become systems.</h2>
          <p><strong>Smart Farm:</strong> ระบบรดน้ำอัตโนมัติบน Blynk</p>
          <p><strong>Smart Home:</strong> ถังขยะอัตโนมัติด้วย Raspberry Pi</p>
          <p><strong>Safety:</strong> ระบบดับเพลิงจำลองด้วย ESP32</p>
          <Link to="/projects">เปิดคลังผลงาน <ArrowRight size={16} /></Link>
        </article>
      </section>
    </>
  )
}
