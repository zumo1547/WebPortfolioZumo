export function Loading({ label = 'กำลังโหลดข้อมูล' }: { label?: string }) {
  return <div className="loading"><span className="spinner" /><p>{label}</p></div>
}
