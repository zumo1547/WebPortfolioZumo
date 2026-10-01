import type { ReactNode } from 'react'

export function PageHeader({ eyebrow, title, accent, children }: { eyebrow: string; title: string; accent: string; children?: ReactNode }) {
  return (
    <div className="page-heading reveal">
      <div className="eyebrow"><span className="online-dot" /> {eyebrow}</div>
      <h1>{title} <span>{accent}</span></h1>
      {children && <p>{children}</p>}
    </div>
  )
}
