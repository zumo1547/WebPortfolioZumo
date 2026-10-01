import { Cookie, Database, ShieldCheck } from 'lucide-react'
import { PageHeader } from '../components/PageHeader'

export function PrivacyPage() {
  return <section className="content-section page-section privacy-page"><PageHeader eyebrow="PRIVACY POLICY" title="YOUR DATA" accent="MATTERS">นโยบายการจัดเก็บและใช้งานข้อมูลของเว็บไซต์</PageHeader><div className="privacy-grid"><article><ShieldCheck /><h2>ข้อมูลบัญชี</h2><p>ระบบใช้ Supabase Auth จัดการอีเมลและรหัสผ่านอย่างปลอดภัย เว็บไซต์ไม่สามารถอ่านข้อความรหัสผ่านของคุณได้</p></article><article><Database /><h2>ข้อมูลที่จัดเก็บ</h2><p>ชื่อผู้ใช้ ข้อมูลโปรเจกต์ และข้อความที่ส่งผ่านหน้าติดต่อจะถูกเก็บในฐานข้อมูล PostgreSQL ของ Supabase ตามสิทธิ์ที่กำหนด</p></article><article><Cookie /><h2>คุกกี้และ Session</h2><p>เราใช้พื้นที่จัดเก็บของเบราว์เซอร์เพื่อรักษาสถานะการเข้าสู่ระบบ ไม่มีคุกกี้โฆษณาหรือระบบติดตามจากบุคคลที่สาม</p></article></div></section>
}
