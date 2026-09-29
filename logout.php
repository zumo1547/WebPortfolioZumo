<?php
// 1. ทำงานทันทีที่เข้ามา: ทำลาย Session (สำคัญมาก!)
session_start();
session_destroy();

// 2. ไม่ต้อง redirect ทันที เราจะแสดงผลหน้า HTML ก่อน
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/Icon portfolio.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Custom animation สำหรับ Fade-in และ Scale-up */
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .animate-fade-in-scale {
            animation: fadeInScale 0.7s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen overflow-hidden">

    <div class="text-center p-8 rounded-lg animate-fade-in-scale">
        
        <svg class="animate-spin h-16 w-16 text-purple-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        
        <h1 class="text-3xl font-bold mt-6 text-white">กำลังออกจากระบบ</h1>
        <p class="text-lg text-gray-400 mt-2">ขอบคุณที่ใช้บริการ แล้วพบกันใหม่!</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // หน่วงเวลา 2000ms (2 วินาที) เพื่อให้คนเห็นอนิเมชัน
            setTimeout(() => {
                
                // (Optional) เพิ่มเอฟเฟกต์ Fade-out ก่อนเปลี่ยนหน้า
                document.body.classList.add('transition-opacity', 'duration-500', 'opacity-0');
                
                // รอให้ Fade-out จบ (500ms) แล้วค่อย Redirect
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 500);

            }, 2000); // <-- ปรับเวลาหน่วงตรงนี้ (หน่วยเป็น ms)
        });
    </script>

</body>
</html>