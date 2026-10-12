import { useEffect, useState } from 'react'
import { ArrowUpRight, GitCommitHorizontal, History } from 'lucide-react'

type WebsiteUpdate = {
  sha: string
  date: string
  author: string
  area: string
  title: string
}

const dateFormatter = new Intl.DateTimeFormat('th-TH', {
  dateStyle: 'medium',
  timeStyle: 'short',
  timeZone: 'Asia/Bangkok',
})

function isWebsiteUpdate(value: unknown): value is WebsiteUpdate {
  if (!value || typeof value !== 'object') return false
  const entry = value as Partial<WebsiteUpdate>
  return typeof entry.sha === 'string' && /^[a-f0-9]{40}$/i.test(entry.sha)
    && typeof entry.date === 'string' && Number.isFinite(Date.parse(entry.date))
    && typeof entry.author === 'string' && typeof entry.area === 'string'
    && typeof entry.title === 'string'
}

export function UpdateLog() {
  const [updates, setUpdates] = useState<WebsiteUpdate[]>([])
  const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading')

  useEffect(() => {
    let mounted = true
    let controller: AbortController | null = null

    async function refresh() {
      if (document.hidden) return
      controller?.abort()
      controller = new AbortController()
      try {
        const response = await fetch('/updates.json', { cache: 'no-store', signal: controller.signal })
        if (!response.ok) throw new Error('Update log unavailable')
        const data: unknown = await response.json()
        if (!data || typeof data !== 'object' || !('entries' in data) || !Array.isArray(data.entries)) {
          throw new Error('Invalid update log')
        }
        if (mounted) {
          setUpdates(data.entries.filter(isWebsiteUpdate).slice(0, 10))
          setStatus('ready')
        }
      } catch (error) {
        if (mounted && !(error instanceof DOMException && error.name === 'AbortError')) setStatus('error')
      }
    }

    const refreshWhenVisible = () => { if (!document.hidden) void refresh() }
    void refresh()
    const interval = window.setInterval(refresh, 45_000)
    document.addEventListener('visibilitychange', refreshWhenVisible)
    window.addEventListener('focus', refreshWhenVisible)
    return () => {
      mounted = false
      controller?.abort()
      window.clearInterval(interval)
      document.removeEventListener('visibilitychange', refreshWhenVisible)
      window.removeEventListener('focus', refreshWhenVisible)
    }
  }, [])

  return (
    <section className="update-web about-reveal" aria-labelledby="update-web-title">
      <header className="update-web-heading">
        <span className="update-web-icon"><History size={23} aria-hidden="true" /></span>
        <div className="update-web-heading-copy">
          <span className="mono-label">// WEBSITE CHANGELOG</span>
          <h2 id="update-web-title">Update <span>Web</span></h2>
          <p>บันทึกการปรับเว็บจาก commit ในเวอร์ชันที่เผยแพร่แล้ว</p>
        </div>
        <span className="update-web-live"><i aria-hidden="true" /> ตรวจทุก 45 วินาที</span>
      </header>

      <div className="update-web-scroll" role="region" aria-label="รายการอัปเดตเว็บไซต์ เลื่อนเพื่อดูเพิ่มเติม" tabIndex={0}>
        {updates.length > 0 ? (
          <ol className="update-web-list">
            {updates.map((item, index) => (
              <li className="update-web-item" key={item.sha}>
                <span className="update-web-rail"><GitCommitHorizontal size={18} aria-hidden="true" /></span>
                <div className="update-web-entry">
                  <div className="update-web-entry-top">
                    <span className="update-web-area">{item.area}</span>
                    {index === 0 && <span className="update-web-latest">LATEST</span>}
                    <time dateTime={item.date}>{dateFormatter.format(new Date(item.date))}</time>
                  </div>
                  <h3>{item.title}</h3>
                  <div className="update-web-meta">
                    <span>โดย {item.author}</span>
                    <span className="update-web-meta-dot" aria-hidden="true">·</span>
                    <a href={`https://github.com/zumo1547/WebPortfolioZumo/commit/${item.sha}`} target="_blank" rel="noopener noreferrer" aria-label={`ดู commit ${item.sha.slice(0, 7)} บน GitHub`}>
                      {item.sha.slice(0, 7)} <ArrowUpRight size={13} aria-hidden="true" />
                    </a>
                  </div>
                </div>
              </li>
            ))}
          </ol>
        ) : (
          <p className="update-web-empty" role="status" aria-live="polite">
            {status === 'loading' ? 'กำลังโหลดประวัติการอัปเดต…' : status === 'error' ? 'ยังโหลดประวัติไม่ได้ ระบบจะลองใหม่อัตโนมัติ' : 'ยังไม่มีประวัติการอัปเดต'}
          </p>
        )}
      </div>
      <p className="update-web-note">{updates.length > 0 ? `${updates.length} รายการล่าสุด · ` : ''}วันและเวลาอ้างอิงจาก commit ตามเวลาไทย · แสดงเฉพาะสรุปที่คัดกรองแล้ว</p>
    </section>
  )
}
