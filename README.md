# WebPortfolioZumo

Portfolio ของ Wutthipat “Zumo” Sriyangnok เขียนใหม่ด้วย React, TypeScript และ Supabase รองรับหน้าจอคอมพิวเตอร์ แท็บเล็ต และโทรศัพท์

โค้ด PHP รุ่นเดิมถูกเก็บไว้ใน `legacy-php/` เฉพาะในเครื่องเพื่อใช้อ้างอิงระหว่างย้ายระบบ โฟลเดอร์นี้ถูกตัดออกจาก Git และ Vercel เพื่อไม่ให้ config หรือ server code เก่าหลุดไปกับเว็บ production

## ระบบที่มีให้

- หน้า Home, About, Projects, Contact และ Privacy
- สมัครสมาชิก เข้าสู่ระบบ ลืมรหัสผ่าน และเปลี่ยนรหัสผ่านด้วย Supabase Auth
- จัดเก็บข้อมูลโปรเจกต์ใน Supabase PostgreSQL
- อัปโหลดรูปโปรเจกต์สูงสุด 5 รูปไปยัง Supabase Storage
- แปลงรูป JPG/PNG/WebP เป็น WebP คุณภาพ 82% และย่อด้านยาวไม่เกิน 1920px ก่อนอัปโหลด
- ค้นหาและกรองโปรเจกต์ตาม Tag
- Admin Dashboard สำหรับดู เพิ่ม แก้ไข และลบโปรเจกต์ จัดการข้อความ ตรวจข้อมูลผู้ใช้ และดู Audit Log
- Row Level Security และ Admin RPC ตรวจสิทธิ์ซ้ำที่ PostgreSQL ก่อนแก้ไขหรือลบข้อมูล
- Security headers บน Vercel รวม CSP, HSTS, anti-frame และ browser permissions policy
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

บัญชีที่สมัครใหม่จะได้รับ role `user` เสมอ เพื่อป้องกันผู้สมัครรายแรกยึดสิทธิ์แอดมิน การตั้งแอดมินครั้งแรกให้ทำผ่าน Supabase SQL Editor ด้วยบัญชีเจ้าของโปรเจกต์:

```sql
update public.profiles set role = 'admin' where id = 'AUTH_USER_UUID';
```

หลังจากนั้นแอดมินสามารถจัดการสิทธิ์บัญชีอื่นผ่าน Dashboard ได้ โดยระบบไม่อนุญาตให้แอดมินเปลี่ยนสิทธิ์บัญชีที่กำลังใช้งานเอง

## Production build

```bash
npm run build
npm run preview
```

## Deploy บน Vercel

Import repository นี้เข้า Vercel แล้ว Vercel จะตรวจพบ Vite และใช้ค่าจาก `vercel.json` โดยอัตโนมัติ ตัวเว็บมี Supabase URL และ publishable key สำรองในโค้ด จึงเชื่อมฐานข้อมูลได้ทันที แต่แนะนำให้ตั้ง Environment Variables สองค่านี้ใน Vercel เพื่อให้เปลี่ยนโปรเจกต์ภายหลังได้ง่าย:

- `VITE_SUPABASE_URL`
- `VITE_SUPABASE_ANON_KEY`
- `VITE_SITE_URL` ตั้งเป็น `https://webportfoliozumo.vercel.app`

Build command คือ `npm run build` และ output directory คือ `dist` เมื่อ Vercel เชื่อมกับ GitHub แล้ว ทุก push เข้า `main` จะ deploy อัตโนมัติ

ใน Supabase Dashboard ตั้งค่าที่ **Authentication → URL Configuration** ดังนี้:

- Site URL: `https://webportfoliozumo.vercel.app`
- Redirect URLs: `https://webportfoliozumo.vercel.app/auth/callback`
- สำหรับพัฒนาในเครื่อง เพิ่ม `http://localhost:5173/auth/callback`

หน้า `/auth/callback` รองรับทั้งลิงก์ยืนยันอีเมลและลิงก์ตั้งรหัสผ่านใหม่ หากลิงก์เดิมหมดอายุ ผู้ใช้ส่งอีเมลยืนยันซ้ำได้จากหน้าสมัครหรือหน้าเข้าสู่ระบบ

> ห้ามใส่ `SUPABASE_SERVICE_ROLE_KEY`, `SUPABASE_SECRET_KEY`, JWT secret หรือรหัสผ่าน PostgreSQL ใน source code และ GitHub repository
