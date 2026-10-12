import { execFileSync } from 'node:child_process'
import { mkdirSync, writeFileSync } from 'node:fs'
import { resolve } from 'node:path'

// Only controlled summaries leave the build. Raw subjects, commit bodies and emails never do.
export function summarizeCommit(subject) {
  const text = subject.toLowerCase()
  if (/thai words.*homepage link|homepage link.*thai/.test(text)) return { area: 'หน้าแรก', title: 'ปรับข้อความในลิงก์หน้าแรกไม่ให้ตัดคำ' }
  if (/readability.*homepage|homepage copy|homepage.*readab/.test(text)) return { area: 'หน้าแรก', title: 'ปรับข้อความหน้าแรกให้อ่านง่ายทุกหน้าจอ' }
  if (/mindset|origin gradient|vision section/.test(text)) return { area: 'เกี่ยวกับ', title: 'ปรับจุดเน้นและสีข้อความในหน้า About' }
  if (/about.*text styling|about.*copy layout/.test(text)) return { area: 'เกี่ยวกับ', title: 'จัดรูปแบบข้อความหน้า About ให้อ่านง่ายขึ้น' }
  if (/about.*ai disclosure/.test(text)) return { area: 'เกี่ยวกับ', title: 'ปรับคำอธิบายการใช้ AI ในเว็บไซต์' }
  if (/about.*learning path|about.*timeline/.test(text)) return { area: 'เกี่ยวกับ', title: 'ปรับส่วนเส้นทางการเรียนรู้' }
  if (/about|motivation/.test(text)) return { area: 'เกี่ยวกับ', title: 'ปรับเนื้อหาและการแสดงผลหน้า About' }
  if (/project summaries|homepage skill|linked projects/.test(text)) return { area: 'หน้าแรก', title: 'ปรับสรุปและลิงก์ผลงานในหน้าแรก' }
  if (/project|gallery/.test(text)) return { area: 'ผลงาน', title: 'ปรับหน้าแสดงผลงานและรายละเอียดโปรเจกต์' }
  if (/carousel|slideshow|slides|animation|scroll journey|homepage|hero/.test(text)) return { area: 'หน้าแรก', title: 'ปรับการแสดงผลและการเคลื่อนไหวหน้าแรก' }
  if (/email|auth|password|smtp|security|database|supabase|api|deploy|vercel|secret|token|key|credential/i.test(text)) return { area: 'ระบบ', title: 'ปรับปรุงระบบเบื้องหลังเว็บไซต์' }
  if (/contact/.test(text)) return { area: 'ติดต่อ', title: 'ปรับหน้าและระบบติดต่อ' }
  if (/font|style|css|mobile|responsive|layout/.test(text)) return { area: 'การแสดงผล', title: 'ปรับหน้าตาเว็บไซต์ให้ใช้งานได้ทุกหน้าจอ' }
  return { area: 'เว็บไซต์', title: 'อัปเดตเว็บไซต์' }
}

export function publicAuthor(author) {
  if (/^wutthipat1547$/i.test(author) || /^zumo1547$/i.test(author)) return 'Wutthipat1547'
  return 'ผู้ร่วมพัฒนา'
}

export function parseHistory(raw) {
  return raw.split('\x1e').map((record) => {
    const [sha, date, author, subject] = record.trim().split('\x1f')
    if (!/^[a-f0-9]{40}$/i.test(sha || '') || !Number.isFinite(Date.parse(date))) return null
    return { sha, date, author: publicAuthor(author), ...summarizeCommit(subject || '') }
  }).filter(Boolean)
}

const output = resolve('public/updates.json')
let entries = []
try {
  const raw = execFileSync('git', ['log', '-10', '--first-parent', '--pretty=format:%H%x1f%cI%x1f%an%x1f%s%x1e'], {
    encoding: 'utf8',
    maxBuffer: 1024 * 1024,
    windowsHide: true,
  })
  entries = parseHistory(raw)
} catch {
  // A source archive without Git metadata can still build the website.
}

mkdirSync(resolve('public'), { recursive: true })
writeFileSync(output, JSON.stringify({ entries }, null, 2) + '\n', 'utf8')
process.stdout.write(`Generated ${entries.length} public website updates.\n`)
