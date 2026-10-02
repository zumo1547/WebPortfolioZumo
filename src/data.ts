import type { Project } from './types'

export const projectTags = [
  'ระดับประเทศ',
  'ระดับนานาชาติ',
  'ระดับจังหวัด',
  'งานในโรงเรียน',
  'เข้าร่วมกิจกรรม',
] as const

export const techStack = [
  { name: 'ESP32 / Arduino', detail: 'IoT · Sensors', icon: 'cpu' },
  { name: 'micro:bit', detail: 'AIoT · Robotics', icon: 'circuit' },
  { name: 'Python', detail: 'AI · OpenCV · Data', image: 'assets/Python-logo-notext.svg.png' },
  { name: 'C++ / C#', detail: 'Embedded · Game', icon: 'code' },
  { name: 'React', detail: 'UI · Components', icon: 'atom' },
  { name: 'TypeScript', detail: 'Web · Type Safety', icon: 'braces' },
  { name: 'Node.js / JavaScript', detail: 'Web · Runtime', icon: 'server' },
  { name: 'Supabase', detail: 'Postgres · Auth · Storage', icon: 'database' },
  { name: 'GitHub', detail: 'Git · Version Control', icon: 'github' },
  { name: 'Unity', detail: 'C# · Game Engine', icon: 'boxes' },
  { name: 'Roblox Studio', detail: 'Lua · Game Systems', icon: 'gamepad' },
  { name: 'CiRA CORE', detail: 'Computer Vision', icon: 'scan' },
]

export const fallbackProjects: Project[] = [
  {
    id: -1,
    slug: 'smart-farm-iot-system',
    name: 'Smart Farm IoT System',
    description: 'ระบบรดน้ำต้นไม้อัตโนมัติ ใช้ Sensor วัดความชื้นร่วมกับ ESP32 และแสดงผลแบบ Real-time บน Blynk',
    images: ['/assets/bg_bridge.png'],
    tags: ['งานในโรงเรียน'],
    links: {},
    award_type: null,
    award_title: null,
    award_rank: null,
    created_at: new Date(0).toISOString(),
    updated_at: new Date(0).toISOString(),
  },
  {
    id: -2,
    slug: 'ai-object-detection',
    name: 'AI Object Detection',
    description: 'ฝึกโมเดลตรวจจับวัตถุด้วย Python, OpenCV และชุดข้อมูลที่สร้างขึ้นเอง',
    images: ['/assets/Python-logo-notext.svg.png'],
    tags: ['เข้าร่วมกิจกรรม'],
    links: {},
    award_type: null,
    award_title: null,
    award_rank: null,
    created_at: new Date(0).toISOString(),
    updated_at: new Date(0).toISOString(),
  },
]
