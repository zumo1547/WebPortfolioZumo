import { Award, BookOpen, BrainCircuit, Cpu, Gamepad2, MapPin, Rocket } from 'lucide-react'
import { PageHeader } from '../components/PageHeader'
import { assetUrl } from '../lib/supabase'

const timeline = [
  { icon: <Gamepad2 />, title: 'Game Development', text: 'เริ่มต้นจากการสร้างเกมและระบบใน Roblox Studio และ Unity เรียนรู้ Lua และ C# ผ่านการลงมือทำ' },
  { icon: <Cpu />, title: 'IoT & Robotics', text: 'พัฒนา Smart Farm, Smart Home และระบบ Safety ด้วย ESP32, Arduino, Sensor และ Blynk' },
  { icon: <BrainCircuit />, title: 'AI & Computer Vision', text: 'สร้างชุดข้อมูลและทดลองโมเดลตรวจจับวัตถุด้วย Python, OpenCV และเครื่องมือ AI' },
  { icon: <Rocket />, title: 'Web & Cloud', text: 'พัฒนาเว็บสมัยใหม่ด้วย React, TypeScript, PostgreSQL และ Supabase' },
]

export function AboutPage() {
  return (
    <section className="content-section page-section">
      <PageHeader eyebrow="PROFILE DATABASE" title="ABOUT" accent="ME">เรื่องราว ทักษะ และสิ่งที่กำลังเรียนรู้ของผม</PageHeader>
      <div className="about-grid">
        <article className="profile-panel reveal">
          <div className="panel-line" />
          <img src={assetUrl('assets/STUDENT_Wutthipat.png')} alt="Wutthipat Sriyangnok" />
          <div className="profile-panel-copy">
            <span className="mono-label">// IDENTITY</span>
            <h2>Wutthipat<br /><span>Sriyangnok</span></h2>
            <p>“Zumo” — นักพัฒนารุ่นใหม่ที่สนใจการสร้างเกม ระบบ IoT หุ่นยนต์ ปัญญาประดิษฐ์ และเว็บแอป</p>
            <div className="profile-meta"><MapPin size={16} /> Thailand</div>
            <div className="profile-meta"><BookOpen size={16} /> Student &amp; Lifelong Learner</div>
          </div>
        </article>
        <div className="timeline">
          {timeline.map((item, index) => (
            <article className="timeline-item reveal" style={{ animationDelay: `${index * 80}ms` }} key={item.title}>
              <div className="timeline-icon">{item.icon}</div>
              <div><span>0{index + 1}</span><h3>{item.title}</h3><p>{item.text}</p></div>
            </article>
          ))}
        </div>
      </div>
      <div className="achievement-strip reveal"><Award /><div><b>เป้าหมายของผม</b><p>สร้างเทคโนโลยีที่แก้ปัญหาได้จริง และแบ่งปันสิ่งที่เรียนรู้ผ่านทุกโปรเจกต์</p></div></div>
    </section>
  )
}
