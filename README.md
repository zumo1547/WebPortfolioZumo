# WebPortfolioZumo

Portfolio ของ Wutthipat “Zumo” Sriyangnok เขียนใหม่ด้วย React, TypeScript และ Supabase รองรับหน้าจอคอมพิวเตอร์ แท็บเล็ต และโทรศัพท์

## ระบบที่มีให้

- หน้า Home, About, Projects, Contact และ Privacy
- สมัครสมาชิก เข้าสู่ระบบ ลืมรหัสผ่าน และเปลี่ยนรหัสผ่านด้วย Supabase Auth
- จัดเก็บข้อมูลโปรเจกต์ใน Supabase PostgreSQL
- อัปโหลดรูปโปรเจกต์สูงสุด 5 รูปไปยัง Supabase Storage
- ค้นหาและกรองโปรเจกต์ตาม Tag
- Admin Dashboard สำหรับดูโปรเจกต์ ผู้ใช้ และข้อความติดต่อ
- Row Level Security ป้องกันการเพิ่ม แก้ไข และลบข้อมูลโดยผู้ใช้ทั่วไป
- ตั้งค่า Vercel rewrite สำหรับ React Router เรียบร้อย

## เริ่มใช้งาน

```bash
npm install
copy .env.example .env.local
npm run db:setup
npm run dev
```

แก้ `.env.local` ให้เป็นค่าของ Supabase ก่อนรันคำสั่ง setup:

```env
VITE_SUPABASE_URL=https://your-project.supabase.co
VITE_SUPABASE_ANON_KEY=your-anon-key
POSTGRES_URL=postgresql://postgres.project-ref:password@pooler-host:5432/postgres?sslmode=require
```

`POSTGRES_URL` ใช้เฉพาะสคริปต์สร้างฐานข้อมูลและจะไม่ถูกส่งไปยัง browser ส่วนเว็บใช้เฉพาะ URL และ anon key ซึ่งความปลอดภัยถูกควบคุมด้วย RLS ใน `supabase/schema.sql`

บัญชีแรกที่สมัครหลังติดตั้ง schema จะได้รับ role `admin` เพื่อเข้า Dashboard และจัดการโปรเจกต์ บัญชีถัดไปจะเป็น `user`

## Production build

```bash
npm run build
npm run preview
```

## Deploy บน Vercel

Import repository นี้เข้า Vercel แล้ว Vercel จะตรวจพบ Vite และใช้ค่าจาก `vercel.json` โดยอัตโนมัติ ตัวเว็บมี Supabase URL และ publishable key สำรองในโค้ด จึงเชื่อมฐานข้อมูลได้ทันที แต่แนะนำให้ตั้ง Environment Variables สองค่านี้ใน Vercel เพื่อให้เปลี่ยนโปรเจกต์ภายหลังได้ง่าย:

- `VITE_SUPABASE_URL`
- `VITE_SUPABASE_ANON_KEY`

Build command คือ `npm run build` และ output directory คือ `dist` เมื่อ Vercel เชื่อมกับ GitHub แล้ว ทุก push เข้า `main` จะ deploy อัตโนมัติ

ใน Supabase Dashboard ให้เพิ่ม URL ของ Vercel ที่ **Authentication → URL Configuration → Redirect URLs** เช่น `https://ชื่อโปรเจกต์.vercel.app/**` เพื่อให้ลิงก์ยืนยันอีเมลและรีเซ็ตรหัสผ่านกลับมาที่เว็บได้

> ห้ามใส่ `SUPABASE_SERVICE_ROLE_KEY`, `SUPABASE_SECRET_KEY`, JWT secret หรือรหัสผ่าน PostgreSQL ใน source code และ GitHub repository
