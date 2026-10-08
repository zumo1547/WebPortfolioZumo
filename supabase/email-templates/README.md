# อีเมลบัญชีผู้ใช้ของ Zumo Dev Portfolio

ไฟล์นี้เป็นเทมเพลต HTML สำหรับ **Supabase Auth ของโปรเจกต์ที่โฮสต์บน Supabase** การ deploy เว็บหรือ push Git เพียงอย่างเดียวจะไม่เปลี่ยนอีเมลที่ส่งจริง ต้องบันทึกเทมเพลตใน Supabase Dashboard ของโปรเจกต์ `qywoebwqrtatnaakocav` ด้วย

| เมนูใน Authentication → Email Templates | Subject | เนื้อหา HTML |
| --- | --- | --- |
| Reset password | `ตั้งรหัสผ่านใหม่ | Zumo Dev Portfolio` | [`recovery.html`](./recovery.html) |
| Confirm sign up | `ยืนยันอีเมล | Zumo Dev Portfolio` | [`confirmation.html`](./confirmation.html) |

1. เปิด Supabase Dashboard ของโปรเจกต์ แล้วไปที่ **Authentication → Email Templates**
2. เลือกเทมเพลตตามตาราง วาง Subject และ HTML จากไฟล์ที่ตรงกัน แล้วบันทึก
3. ตรวจ **Authentication → URL Configuration** ว่า Site URL เป็น `https://webportfoliozumo.vercel.app` และ Redirect URL อนุญาต `/auth/callback` ตามที่ระบุใน README หลัก
4. ส่งอีเมลทดสอบจากหน้า `/forgot-password` และจากการสมัครบัญชีทดสอบ ตรวจชื่อเรื่อง โลโก้ ปุ่ม ลิงก์สำรอง และการเปิดบนโทรศัพท์

เทมเพลตใช้ `{{ .ConfirmationURL }}` ของ Supabase สำหรับลิงก์ครั้งเดียว ห้ามแทนที่ด้วย URL คงที่ โลโก้ในเนื้อหาอ้างถึงไฟล์สาธารณะของเว็บที่ `https://webportfoliozumo.vercel.app/assets/Icon%20portfolio.png` จึงต้องให้ URL นี้ใช้งานได้ก่อนส่งทดสอบ

**ชื่อผู้ส่งและรูปประจำผู้ส่งใน Gmail เป็นคนละส่วนกับ HTML นี้** หากต้องการให้ชื่อจาก `Supabase Auth` เปลี่ยนเป็น `Zumo Dev Portfolio` และใช้ที่อยู่ผู้ส่งของตัวเอง ให้ตั้ง **Authentication → SMTP Settings** ด้วยผู้ให้บริการ SMTP และอีเมลจากโดเมนที่เป็นเจ้าของ แล้วกำหนด Sender name เป็น `Zumo Dev Portfolio` ห้ามใส่รหัสผ่าน SMTP ใน Git หรือไฟล์ frontend การใส่ `<img>` ในอีเมลจะทำให้เห็นโลโก้ในเนื้อหา แต่ไม่เปลี่ยนรูปวงกลมที่ Gmail แสดงข้างชื่อผู้ส่งโดยอัตโนมัติ การแสดงโลโก้แบรนด์ในกล่องจดหมาย Gmail ด้วย BIMI ต้องมีโดเมนที่ยืนยันตัวตนและเงื่อนไขของ Google เพิ่มเติม

ถ้าหน้า Dashboard ไม่อนุญาตให้แก้เทมเพลต ให้ตรวจแพ็กเกจและการตั้งค่า SMTP: Supabase จำกัดการแก้เทมเพลตของ **โปรเจกต์ Free ใหม่ที่ใช้ SMTP เริ่มต้น** ตั้งแต่ 3 มิถุนายน 2026 ส่วนโปรเจกต์เก่าหรือโปรเจกต์ที่ใช้ SMTP ของตัวเองมีเงื่อนไขต่างกัน

อ้างอิง: [Supabase Email Templates](https://supabase.com/docs/guides/auth/auth-email-templates), [Supabase Custom SMTP](https://supabase.com/docs/guides/auth/auth-smtp), [การเปลี่ยนแปลงแพ็กเกจ Free](https://supabase.com/changelog/46599-changes-to-email-template-customisation-on-free-tier), [Google BIMI](https://support.google.com/a/answer/10911320?hl=en-ch)
