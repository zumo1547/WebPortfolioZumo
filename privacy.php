<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <title>นโยบายความเป็นส่วนตัว - Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background: #111827;
      color: #e5e7eb;
      font-family: 'Segoe UI', sans-serif;
    }

    /* 2. เพิ่มอนิเมชั่น "เลื่อนขึ้น" */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .animated-container {
      animation: fadeInUp 1s ease-out;
    }
  </style>
</head>
<body class="min-h-screen px-6 py-10">

  <div class="max-w-4xl mx-auto bg-gray-900 p-8 rounded-2xl shadow-lg animated-container">
    
    <h1 class="text-3xl font-bold text-purple-400 mb-6 inline-block transition-transform duration-300 hover:scale-105">
      นโยบายความเป็นส่วนตัว
    </h1>
    
    <p class="mb-4">
      เว็บไซต์นี้ใช้คุกกี้เพื่อปรับปรุงประสบการณ์ของคุณ รวมถึงการจดจำผู้ใช้งาน การวิเคราะห์ทราฟฟิก และปรับแต่งเนื้อหาให้เหมาะสม
    </p>

    <h2 class="text-xl font-semibold mt-6 mb-2">เราใช้คุกกี้อย่างไร?</h2>
    <ul class="list-disc list-inside mb-4 space-y-1">
      <li class="transition-all duration-300 hover:translate-x-2 hover:text-purple-300">จำข้อมูลการเข้าสู่ระบบ</li>
      <li class="transition-all duration-300 hover:translate-x-2 hover:text-purple-300">วิเคราะห์สถิติการเข้าชมเว็บไซต์</li>
      <li class="transition-all duration-300 hover:translate-x-2 hover:text-purple-300">ปรับแต่งการแสดงผลให้เหมาะกับผู้ใช้</li>
    </ul>

    <h2 class="text-xl font-semibold mt-6 mb-2">คุณสามารถควบคุมคุกกี้ได้อย่างไร?</h2>
    <p class="mb-4">
      คุณสามารถเลือกที่จะปิดการใช้คุกกี้จากเบราว์เซอร์ของคุณ หรือไม่ยอมรับผ่านแบนเนอร์ที่แสดงในครั้งแรก
    </p>

    <p class="text-sm text-gray-400">
      หากคุณมีคำถามเพิ่มเติม กรุณาติดต่อเราผ่านหน้า <a href="contact.php" class="underline text-purple-300">Contact</a>
    </p>
  </div>

</body>
</html>