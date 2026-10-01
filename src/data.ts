import type { Project } from './types'

export const projectTags = [
  'ระดับประเทศ',
  'ระดับนานาชาติ',
  'ระดับจังหวัด',
  'งานในโรงเรียน',
  'เข้าร่วมกิจกรรม',
] as const

export const techStack = [
  { name: 'ESP32', detail: 'Arduino / IoT', icon: '⚡' },
  { name: 'Python', detail: 'AI / OpenCV', image: 'assets/Python-logo-notext.svg.png' },
  { name: 'React', detail: 'TypeScript', icon: '⚛' },
  { name: 'Supabase', detail: 'Postgres / Auth', icon: '◆' },
  { name: 'Roblox', detail: 'Lua Studio', icon: '◈' },
  { name: 'Unity', detail: 'C# Engine', icon: '⬡' },
]

export const fallbackProjects: Project[] = [
  {
    id: -1,
    name: 'Smart Farm IoT System',
    description: 'ระบบรดน้ำต้นไม้อัตโนมัติ ใช้ Sensor วัดความชื้นร่วมกับ ESP32 และแสดงผลแบบ Real-time บน Blynk',
    images: ['/assets/bg_bridge.png'],
    tags: ['งานในโรงเรียน'],
    links: {},
    created_at: new Date(0).toISOString(),
    updated_at: new Date(0).toISOString(),
  },
  {
    id: -2,
    name: 'AI Object Detection',
    description: 'ฝึกโมเดลตรวจจับวัตถุด้วย Python, OpenCV และชุดข้อมูลที่สร้างขึ้นเอง',
    images: ['/assets/Python-logo-notext.svg.png'],
    tags: ['เข้าร่วมกิจกรรม'],
    links: {},
    created_at: new Date(0).toISOString(),
    updated_at: new Date(0).toISOString(),
  },
]
