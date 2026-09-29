<?php
session_start();
include 'config.php';
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION["username"];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เกี่ยวกับฉัน - Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="darkmode.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/Icon portfolio.png">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --purple: #a855f7;
      --pink:   #ec4899;
      --cyan:   #22d3ee;
      --green:  #34d399;
      --orange: #fb923c;
    }

    * { box-sizing: border-box; }

    body {
      background: linear-gradient(135deg, #c31432, #240b36, #0f0c29, #302b63, #2424e0);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      font-family: 'Noto Sans Thai', 'Kanit', sans-serif;
      overflow-x: hidden;
    }
    @keyframes gradientBG {
      0%   { background-position: 0% 50%; }
      50%  { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    html { scroll-behavior: smooth; }

    /* ===== PARTICLES ===== */
    #about-canvas {
      position: fixed; top: 0; left: 0;
      width: 100%; height: 100%;
      pointer-events: none; z-index: 0; opacity: 0.3;
    }
    .z1 { position: relative; z-index: 1; }

    /* ===== SCROLL REVEAL ===== */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(32px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .scroll-reveal { opacity: 0; }
    .scroll-reveal.is-visible { animation: fadeInUp 0.7s cubic-bezier(0.165,0.84,0.44,1) forwards; }
    .scroll-reveal-delay-100.is-visible { animation-delay: 100ms; }
    .scroll-reveal-delay-200.is-visible { animation-delay: 200ms; }
    .scroll-reveal-delay-300.is-visible { animation-delay: 300ms; }

    /* ===== GLASS ===== */
    .glass {
      background: rgba(17,24,39,0.72);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.09);
    }
    .glass-hover {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .glass-hover:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 12px 40px rgba(168,85,247,0.22);
    }

    /* ===== GRADIENT TEXT ===== */
    .grad-purple {
      background: linear-gradient(135deg,#c084fc,#f472b6);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .grad-cyan {
      background: linear-gradient(135deg,#67e8f9,#3b82f6);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }

    /* ===== SYNTAX ===== */
    .syntax-keyword { color: #f9a8d4; font-weight: 700; }
    .syntax-string  { color: #fdba74; font-weight: 700; }
    .syntax-tool    { color: #67e8f9; font-weight: 700; }

    /* ===== HERO AVATAR RING ===== */
    @keyframes ringPulse {
      0%,100% { box-shadow: 0 0 0 0 rgba(168,85,247,.5), 0 0 0 8px rgba(168,85,247,.15); }
      50%     { box-shadow: 0 0 0 6px rgba(168,85,247,.3), 0 0 0 14px rgba(168,85,247,.06); }
    }
    .avatar-ring {
      width: 110px; height: 110px; border-radius: 50%;
      background: linear-gradient(135deg,#7c3aed,#db2777);
      display: flex; align-items: center; justify-content: center;
      animation: ringPulse 3s ease-in-out infinite;
      flex-shrink: 0;
    }

    /* ===== STATUS DOT ===== */
    @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:.3;} }
    .status-dot {
      width:10px;height:10px;border-radius:50%;
      background:#34d399;
      animation: blink 2s ease-in-out infinite;
      display:inline-block;
    }

    /* ===== ICON PULSE ===== */
    @keyframes iconPulse {
      0%,100% { transform:scale(1); opacity:.85; }
      50%     { transform:scale(1.12); opacity:1; }
    }
    .icon-pulse { animation: iconPulse 2.5s ease-in-out infinite; }

    /* ===== TABS ===== */
    .tab-btn {
      padding: 8px 20px; border-radius: 9999px;
      font-size: .82rem; font-weight: 700;
      border: 1.5px solid rgba(255,255,255,.1);
      color: #9ca3af; cursor: pointer;
      transition: all .2s ease;
      background: transparent;
      font-family: 'Noto Sans Thai','Kanit',sans-serif;
    }
    .tab-btn:hover { border-color: rgba(168,85,247,.4); color: #e9d5ff; }
    .tab-btn.active {
      background: linear-gradient(135deg,rgba(124,58,237,.35),rgba(219,39,119,.25));
      border-color: rgba(168,85,247,.6);
      color: #e9d5ff;
      box-shadow: 0 0 16px rgba(168,85,247,.3);
    }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* ===== TOOL CHIP ===== */
    .tool-chip {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 8px 14px; border-radius: 12px;
      font-size: .8rem; font-weight: 600;
      border: 1px solid rgba(255,255,255,.08);
      background: rgba(255,255,255,.04);
      transition: all .22s ease; cursor: default;
    }
    .tool-chip:hover {
      transform: translateY(-3px) scale(1.04);
      border-color: rgba(168,85,247,.4);
      background: rgba(168,85,247,.1);
    }
    .chip-cyan   { border-color:rgba(34,211,238,.2);  }
    .chip-cyan:hover   { border-color:rgba(34,211,238,.5); background:rgba(34,211,238,.08); }
    .chip-pink   { border-color:rgba(236,72,153,.2);  }
    .chip-pink:hover   { border-color:rgba(236,72,153,.5); background:rgba(236,72,153,.08); }
    .chip-green  { border-color:rgba(52,211,153,.2);  }
    .chip-green:hover  { border-color:rgba(52,211,153,.5); background:rgba(52,211,153,.08); }
    .chip-orange { border-color:rgba(251,146,60,.2);  }
    .chip-orange:hover { border-color:rgba(251,146,60,.5); background:rgba(251,146,60,.08); }

    /* ===== PROJECT FEATURED CARD ===== */
    .proj-card {
      background: rgba(17,24,39,.78);
      backdrop-filter: blur(14px);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 20px;
      overflow: hidden;
      transition: transform .3s ease, box-shadow .3s ease;
      position: relative;
    }
    .proj-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 48px rgba(168,85,247,.2);
    }
    .proj-card-accent {
      height: 3px;
      width: 100%;
    }
    .proj-card::before {
      content:'';
      position:absolute;top:0;left:-100%;width:50%;height:100%;
      background:linear-gradient(90deg,transparent,rgba(255,255,255,.03),transparent);
      transition:left .5s ease;
      pointer-events:none;
    }
    .proj-card:hover::before { left:160%; }

    /* ===== QUOTE CARD ===== */
    .quote-mark {
      font-family: 'Kanit', serif;
      font-size: 5rem; line-height: .8;
      color: rgba(168,85,247,.35);
      font-weight: 800;
      display: block;
    }

    /* ===== GOAL ITEMS ===== */
    .goal-item {
      display: flex; align-items: flex-start; gap: 14px;
      padding: 14px 16px; border-radius: 14px;
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.06);
      transition: all .22s ease;
    }
    .goal-item:hover {
      background: rgba(168,85,247,.08);
      border-color: rgba(168,85,247,.25);
      transform: translateX(4px);
    }
    .goal-icon {
      width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem;
    }

    /* ===== SECTION DIVIDER ===== */
    .sec-line {
      height: 1px;
      background: linear-gradient(90deg,transparent,rgba(168,85,247,.35),rgba(236,72,153,.35),transparent);
      max-width: 500px; margin: 0 auto;
    }

    /* ===== SCROLL TOP ===== */
    #scrollTopBtn {
      position: fixed; bottom: -100px; right: 2rem; z-index: 50;
      transition: bottom .5s cubic-bezier(.23,1,.32,1);
      background: var(--purple); color: white;
      width: 3rem; height: 3rem; border-radius: 50%; border: none;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      box-shadow: 0 5px 15px rgba(168,85,247,.4);
    }
    #scrollTopBtn.is-visible { bottom: 2rem; }
    #scrollTopBtn:hover { background: #9333ea; transform: scale(1.1); }

    /* ===== MODAL/COOKIE ===== */
    .modal-bg { background-color: rgba(0,0,0,.65); backdrop-filter: blur(4px); }
    .cookie-banner {
      background-color: rgba(15,12,40,.95);
      border-top: 1px solid rgba(168,85,247,.25);
    }

    /* ===== SHIMMER ===== */
    @keyframes shimmerBar {
      0%   { background-position:0% 0; }
      100% { background-position:200% 0; }
    }
  </style>
</head>
<body class="text-white min-h-screen flex flex-col">

  <canvas id="about-canvas"></canvas>
  <?php include 'navbar.php'; ?>

  <main class="flex-grow py-12 px-4 z1">
  <div class="max-w-4xl mx-auto">

    <!-- =====================================================
         HERO
    ===================================================== -->
    <section class="scroll-reveal mb-20">
      <div class="glass rounded-3xl p-8 sm:p-12 relative overflow-hidden">

        <!-- shimmer top -->
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#7c3aed,#ec4899,#67e8f9,#ec4899,#7c3aed);background-size:200% 100%;animation:shimmerBar 3s linear infinite;"></div>

        <!-- ambient glow -->
        <div aria-hidden="true" style="pointer-events:none;position:absolute;inset:0;overflow:hidden;">
          <div style="position:absolute;top:-60px;right:-60px;width:280px;height:280px;background:radial-gradient(circle,rgba(124,58,237,.2),transparent 70%);"></div>
          <div style="position:absolute;bottom:-60px;left:-60px;width:250px;height:250px;background:radial-gradient(circle,rgba(219,39,119,.14),transparent 70%);"></div>
        </div>

        <div class="relative flex flex-col sm:flex-row items-center sm:items-start gap-8">

          <!-- Profile Photo -->
          <div class="flex flex-col items-center gap-3 flex-shrink-0">
            <div style="
              width:120px;height:120px;border-radius:50%;
              padding:3px;
              background:linear-gradient(135deg,#7c3aed,#ec4899,#22d3ee);
              box-shadow:0 0 28px rgba(168,85,247,.55),0 0 10px rgba(236,72,153,.35);
              animation:ringPulse 3s ease-in-out infinite;
            ">
              <img src="assets/STUDENT_Wutthipat.png"
                   alt="Wutthipat Sriyangnok"
                   style="width:100%;height:100%;border-radius:50%;object-fit:cover;object-position:center top;border:2px solid #0f0c29;">
            </div>
            <span class="flex items-center gap-2 text-xs text-green-400 font-semibold">
              <span class="status-dot"></span> Active Developer
            </span>
          </div>

          <!-- Info -->
          <div class="text-center sm:text-left flex-1">
            <p class="text-xs font-bold text-purple-400 uppercase tracking-widest mb-2">About Me</p>

            <!-- Full name TH + EN -->
            <h1 class="text-3xl sm:text-4xl font-extrabold mb-0.5 leading-tight" style="font-family:'Kanit',sans-serif;">
              <span class="grad-purple">Wutthipat Sriyangnok</span>
            </h1>
            <p class="text-base text-gray-400 font-medium mb-1" style="font-family:'Noto Sans Thai',sans-serif;">
              นายวุฒิภัทร ศรียางนอก
            </p>

            <!-- Nickname badge -->
            <div class="flex items-center gap-2 mb-4 justify-center sm:justify-start flex-wrap">
              <span style="
                background:linear-gradient(135deg,rgba(124,58,237,.35),rgba(219,39,119,.25));
                border:1px solid rgba(168,85,247,.5);
                color:#e9d5ff; font-size:.75rem; font-weight:800;
                padding:3px 12px; border-radius:999px; letter-spacing:.04em;
              ">⚡ Zumo | ซูโม่</span>
              <span class="text-gray-600 text-xs">·</span>
              <span class="text-pink-400 text-sm font-semibold">Game Dev</span>
              <span class="text-gray-600 text-xs">·</span>
              <span class="text-cyan-400 text-sm font-semibold">IoT & Hardware</span>
            </div>

            <p class="text-gray-300 leading-relaxed max-w-xl text-sm sm:text-base">
              สวัสดีครับ! ผมเป็นนักพัฒนาที่หลงใหลในการสร้างสรรค์โลกดิจิทัล ทั้งในสาย
              <strong class="text-pink-400">Game Development</strong> และ
              <strong class="text-cyan-400">IoT & Hardware</strong>
              ผมเชื่อในการเปลี่ยนไอเดียให้เป็นความจริง ไม่ว่าจะเป็นเกมที่น่าตื่นตา หรืออุปกรณ์อัจฉริยะที่ทำให้ชีวิตง่ายขึ้น 🚀
            </p>

            <!-- Quick stat chips -->
            <div class="flex flex-wrap gap-2 mt-5 justify-center sm:justify-start">
              <span class="tool-chip chip-pink"><i class="fas fa-gamepad text-pink-400 text-xs"></i> Game Dev</span>
              <span class="tool-chip chip-cyan"><i class="fas fa-microchip text-cyan-400 text-xs"></i> IoT / Hardware</span>
              <span class="tool-chip chip-orange"><i class="fas fa-brain text-orange-400 text-xs"></i> AI & ML</span>
              <span class="tool-chip chip-green"><i class="fas fa-robot text-green-400 text-xs"></i> Robotics</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- =====================================================
         TOOLBOX — TABS
    ===================================================== -->
    <section class="scroll-reveal mb-20">
      <p class="text-xs font-bold text-purple-400 uppercase tracking-widest text-center mb-2">Skills & Tools</p>
      <h2 class="text-3xl sm:text-4xl font-bold text-center mb-8" style="font-family:'Kanit',sans-serif;">
        <span class="grad-purple">กล่องเครื่องมือของผม</span>
      </h2>

      <!-- Tab Buttons -->
      <div class="flex flex-wrap justify-center gap-2 mb-8">
        <button class="tab-btn active" data-tab="hardware" onclick="switchTab('hardware')">
          <i class="fas fa-microchip mr-1"></i> Hardware
        </button>
        <button class="tab-btn" data-tab="software" onclick="switchTab('software')">
          <i class="fas fa-code mr-1"></i> Software
        </button>
      </div>

      <!-- Tab: Hardware -->
      <div id="tab-hardware" class="tab-panel active scroll-reveal">
        <div class="glass rounded-2xl p-6 sm:p-8">
          <h3 class="text-lg font-bold text-cyan-300 mb-5 flex items-center gap-2">
            <i class="fas fa-microchip icon-pulse text-cyan-400"></i> Hardware & Platforms
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="tool-chip chip-cyan w-full justify-start">
              <i class="fas fa-wifi text-cyan-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-cyan-300 text-xs">ESP32 / ESP8266</p>
                <p class="text-gray-500 text-xs">พร้อม WiFi สำหรับ IoT</p>
              </div>
            </div>
            <div class="tool-chip chip-cyan w-full justify-start">
              <i class="fas fa-memory text-cyan-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-cyan-300 text-xs">Arduino UNO R3</p>
                <p class="text-gray-500 text-xs">บอร์ดเริ่มต้นยอดนิยม</p>
              </div>
            </div>
            <div class="tool-chip chip-cyan w-full justify-start">
              <i class="fas fa-server text-cyan-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-cyan-300 text-xs">Raspberry Pi 4</p>
                <p class="text-gray-500 text-xs">คอมพิวเตอร์จิ๋ว Smart Home</p>
              </div>
            </div>
            <div class="tool-chip chip-cyan w-full justify-start">
              <i class="fas fa-camera text-cyan-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-cyan-300 text-xs">EspCam</p>
                <p class="text-gray-500 text-xs">บอร์ดกล้องสำหรับงาน Vision</p>
              </div>
            </div>
            <div class="tool-chip chip-cyan w-full justify-start">
              <i class="fas fa-robot text-cyan-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-cyan-300 text-xs">POP32i</p>
                <p class="text-gray-500 text-xs">สำหรับหุ่นยนต์เคลื่อนที่</p>
              </div>
            </div>
            <div class="tool-chip chip-cyan w-full justify-start">
              <i class="fas fa-thermometer-half text-cyan-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-cyan-300 text-xs">Sensors</p>
                <p class="text-gray-500 text-xs">Ultrasonic, ความชื้น, Gas Detector</p>
              </div>
            </div>
            <div class="tool-chip chip-cyan w-full justify-start sm:col-span-2">
              <i class="fas fa-bolt text-cyan-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-cyan-300 text-xs">วงจรไฟฟ้าพื้นฐาน</p>
                <p class="text-gray-500 text-xs">Resistor, วงจรไฟกระพริบ, การอ่านค่าต้านทาน</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: Software -->
      <div id="tab-software" class="tab-panel">
        <div class="glass rounded-2xl p-6 sm:p-8">
          <h3 class="text-lg font-bold text-pink-300 mb-5 flex items-center gap-2">
            <i class="fas fa-code icon-pulse text-pink-400" style="animation-delay:.5s;"></i> Software & Languages
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="tool-chip chip-pink w-full justify-start">
              <i class="fas fa-gamepad text-pink-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-pink-300 text-xs">Lua</p>
                <p class="text-gray-500 text-xs">สคริปต์ & UI ใน Roblox</p>
              </div>
            </div>
            <div class="tool-chip chip-pink w-full justify-start">
              <i class="fas fa-cube text-pink-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-pink-300 text-xs">C# (Unity)</p>
                <p class="text-gray-500 text-xs">กลไกเกม & AI พื้นฐาน</p>
              </div>
            </div>
            <div class="tool-chip chip-cyan w-full justify-start">
              <i class="fas fa-tachometer-alt text-cyan-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-cyan-300 text-xs">Blynk API</p>
                <p class="text-gray-500 text-xs">Dashboard & ควบคุม IoT</p>
              </div>
            </div>
            <div class="tool-chip chip-orange w-full justify-start">
              <i class="fas fa-brain text-orange-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-orange-300 text-xs">Python</p>
                <p class="text-gray-500 text-xs">Train AI Model บน Anaconda</p>
              </div>
            </div>
            <div class="tool-chip chip-pink w-full justify-start">
              <i class="fas fa-globe text-pink-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-pink-300 text-xs">JavaScript / PHP</p>
                <p class="text-gray-500 text-xs">พื้นฐานเว็บ & backend</p>
              </div>
            </div>
            <div class="tool-chip chip-green w-full justify-start">
              <i class="fas fa-microchip text-green-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-green-300 text-xs">Makecode</p>
                <p class="text-gray-500 text-xs">สำหรับบอร์ด REKA:BIT</p>
              </div>
            </div>
            <div class="tool-chip chip-orange w-full justify-start sm:col-span-2">
              <i class="fas fa-robot text-orange-400 text-sm w-4 text-center"></i>
              <div>
                <p class="font-bold text-orange-300 text-xs">ChatGPT API</p>
                <p class="text-gray-500 text-xs">ประยุกต์ใช้ AI ในโปรเจกต์จริง</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="sec-line mb-20"></div>

    <!-- =====================================================
         FEATURED PROJECTS
    ===================================================== -->
    <section class="scroll-reveal mb-20">
      <p class="text-xs font-bold text-pink-400 uppercase tracking-widest text-center mb-2">Highlight Work</p>
      <h2 class="text-3xl sm:text-4xl font-bold text-center mb-10" style="font-family:'Kanit',sans-serif;">
        <span class="grad-purple">โปรเจกต์ที่โดดเด่น</span>
      </h2>

      <div class="grid md:grid-cols-3 gap-5">

        <!-- Smart Farm -->
        <div class="proj-card scroll-reveal">
          <div class="proj-card-accent" style="background:linear-gradient(90deg,#34d399,#059669);"></div>
          <div class="p-6">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(52,211,153,.15);border:1px solid rgba(52,211,153,.25);display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
              <i class="fas fa-seedling text-green-400 icon-pulse" style="animation-delay:1s;font-size:1.1rem;"></i>
            </div>
            <span style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.25);color:#6ee7b7;font-size:.65rem;font-weight:800;padding:2px 10px;border-radius:999px;letter-spacing:.06em;">IoT</span>
            <h3 class="text-lg font-bold text-white mt-3 mb-2" style="font-family:'Kanit',sans-serif;">Smart Farm System</h3>
            <p class="text-gray-400 leading-relaxed text-sm">
              ระบบรดน้ำต้นไม้อัตโนมัติ ใช้ Sensor วัดความชื้นกับ <strong class="syntax-tool">ESP32</strong> แสดงผล Real-time บน <strong class="syntax-tool">Blynk</strong>
            </p>
          </div>
        </div>

        <!-- AI Detection -->
        <div class="proj-card scroll-reveal scroll-reveal-delay-100">
          <div class="proj-card-accent" style="background:linear-gradient(90deg,#a855f7,#7c3aed);"></div>
          <div class="p-6">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(168,85,247,.15);border:1px solid rgba(168,85,247,.25);display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
              <i class="fas fa-brain text-purple-400 icon-pulse" style="animation-delay:1.5s;font-size:1.1rem;"></i>
            </div>
            <span style="background:rgba(168,85,247,.1);border:1px solid rgba(168,85,247,.25);color:#c4b5fd;font-size:.65rem;font-weight:800;padding:2px 10px;border-radius:999px;letter-spacing:.06em;">AI / ML</span>
            <h3 class="text-lg font-bold text-white mt-3 mb-2" style="font-family:'Kanit',sans-serif;">AI Object Detection</h3>
            <p class="text-gray-400 leading-relaxed text-sm">
              Train AI Model ตรวจจับวัตถุ เก็บภาพ 100+ รูป รัน Model ด้วย <strong class="syntax-string">Python</strong> บน <strong class="syntax-tool">Anaconda</strong>
            </p>
          </div>
        </div>

        <!-- ChatGPT Game AI -->
        <div class="proj-card scroll-reveal scroll-reveal-delay-200">
          <div class="proj-card-accent" style="background:linear-gradient(90deg,#ec4899,#db2777);"></div>
          <div class="p-6">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(236,72,153,.15);border:1px solid rgba(236,72,153,.25);display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
              <i class="fas fa-ghost text-pink-400 icon-pulse" style="animation-delay:2s;font-size:1.1rem;"></i>
            </div>
            <span style="background:rgba(236,72,153,.1);border:1px solid rgba(236,72,153,.25);color:#f9a8d4;font-size:.65rem;font-weight:800;padding:2px 10px;border-radius:999px;letter-spacing:.06em;">Game Dev</span>
            <h3 class="text-lg font-bold text-white mt-3 mb-2" style="font-family:'Kanit',sans-serif;">ChatGPT Game AI</h3>
            <p class="text-gray-400 leading-relaxed text-sm">
              ใช้ <strong class="syntax-tool">ChatGPT API</strong> ใน Roblox ด้วย <strong class="syntax-keyword">Lua</strong> สร้าง AI NPC ไล่ตามผู้เล่นอัตโนมัติ
            </p>
          </div>
        </div>

      </div>
    </section>

    <div class="sec-line mb-20"></div>

    <!-- =====================================================
         QUOTE + GOALS
    ===================================================== -->
    <section class="scroll-reveal mb-20">
      <p class="text-xs font-bold text-purple-400 uppercase tracking-widest text-center mb-2">Mindset & Vision</p>
      <h2 class="text-3xl sm:text-4xl font-bold text-center mb-10" style="font-family:'Kanit',sans-serif;">
        <span class="grad-purple">แรงผลักดันและเป้าหมาย</span>
      </h2>

      <!-- Quote -->
      <div class="glass rounded-3xl p-8 sm:p-10 mb-8 relative overflow-hidden scroll-reveal">
        <div aria-hidden="true" style="pointer-events:none;position:absolute;top:-40px;left:-40px;width:220px;height:220px;background:radial-gradient(circle,rgba(168,85,247,.12),transparent 70%);"></div>
        <span class="quote-mark">"</span>
        <p class="text-xl sm:text-2xl text-gray-200 leading-relaxed font-medium mt-2 mb-4" style="font-family:'Kanit',sans-serif;">
          ผมเชื่อว่าการ <strong class="text-purple-300">ลงมือทำจริง</strong> และเรียนรู้ผ่าน
          <strong class="text-pink-400">การทำงานเป็นทีม</strong>
          คือสิ่งที่จะทำให้เราเติบโตได้เร็วที่สุด
        </p>
        <div class="flex items-center gap-3">
          <div style="width:32px;height:2px;background:linear-gradient(90deg,#a855f7,#ec4899);border-radius:2px;"></div>
          <span class="text-sm text-gray-500 font-semibold">Wutthipat "Zumo" Sriyangnok</span>
        </div>
      </div>

      <!-- Goals grid -->
      <div class="grid sm:grid-cols-2 gap-4 scroll-reveal scroll-reveal-delay-100">

        <div class="goal-item">
          <div class="goal-icon" style="background:rgba(168,85,247,.15);border:1px solid rgba(168,85,247,.25);">🎮</div>
          <div>
            <p class="text-sm font-bold text-purple-300 mb-1">Game & App Development</p>
            <p class="text-xs text-gray-400 leading-relaxed">สร้างเกมและแอปที่ไม่เพียงแต่สนุก แต่ยังแก้ปัญหาชีวิตจริงได้</p>
          </div>
        </div>

        <div class="goal-item">
          <div class="goal-icon" style="background:rgba(34,211,238,.1);border:1px solid rgba(34,211,238,.2);">🔬</div>
          <div>
            <p class="text-sm font-bold text-cyan-300 mb-1">ค่ายวิศวะคอม & Research</p>
            <p class="text-xs text-gray-400 leading-relaxed">มองหาโอกาสเชื่อมความรู้คอมพิวเตอร์เข้ากับโลกแห่งความเป็นจริง</p>
          </div>
        </div>

        <div class="goal-item">
          <div class="goal-icon" style="background:rgba(251,146,60,.1);border:1px solid rgba(251,146,60,.2);">🧭</div>
          <div>
            <p class="text-sm font-bold text-orange-300 mb-1">ค้นหาสายที่ใช่</p>
            <p class="text-xs text-gray-400 leading-relaxed">ลองสัมผัสทั้ง Dev, Data, AI, Cybersecurity — เพื่อให้เห็นภาพอนาคตชัดขึ้น</p>
          </div>
        </div>

        <div class="goal-item">
          <div class="goal-icon" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.2);">✨</div>
          <div>
            <p class="text-sm font-bold text-green-300 mb-1">จุดเริ่มต้นที่สำคัญ</p>
            <p class="text-xs text-gray-400 leading-relaxed">ประสบการณ์ทุกชิ้นคือ stepping stone บนเส้นทางสายนี้ครับ</p>
          </div>
        </div>

      </div>

      <!-- Long paragraph -->
      <div class="glass rounded-2xl p-6 sm:p-8 mt-6 scroll-reveal scroll-reveal-delay-200">
        <p class="text-gray-300 leading-relaxed text-sm sm:text-base mb-4">
          เป้าหมายของผมคือการสร้างสรรค์เกมและแอปพลิเคชันที่ไม่เพียงแต่มีความสนุก แต่ยังสามารถช่วยเหลือและแก้ปัญหาในชีวิตจริงได้ ผมมองหาโอกาสอยู่เสมอ เช่น การเข้าค่ายวิศวะคอม ที่จะเชื่อมโยงความรู้ด้านคอมพิวเตอร์เข้ากับโลกแห่งความเป็นจริง
        </p>
        <p class="text-gray-400 leading-relaxed text-sm sm:text-base">
          ผมกำลังใช้โอกาสเหล่านี้ในการค้นหาตัวเองให้ชัดเจนยิ่งขึ้น ว่าความชอบที่แท้จริงของผมในโลกคอมพิวเตอร์อยู่ตรงไหน ไม่ว่าจะเป็นสายพัฒนาโปรแกรม, ข้อมูล, AI, หรือความปลอดภัยไซเบอร์ ผมอยากลองสัมผัสทั้งหมด เพื่อให้เห็นภาพอนาคตของตัวเองชัดเจนขึ้น และเชื่อว่าประสบการณ์เหล่านี้จะเป็น "จุดเริ่มต้น" ที่สำคัญบนเส้นทางสายนี้ครับ ✨
        </p>
      </div>
    </section>

  </div>
  </main>

  <!-- FOOTER -->
  <footer class="text-center py-6 text-gray-500 text-sm z1 border-t border-white/5">
    <p>&copy; <?= date("Y") ?> | Powered by Zumo Dev Portfolio</p>
  </footer>

  <!-- SCROLL TOP -->
  <button id="scrollTopBtn" title="Go to top">
    <i class="fas fa-arrow-up"></i>
  </button>

  <!-- LOGIN MODAL (เดิม) -->
  <div id="loginModal" class="fixed inset-0 flex items-center justify-center hidden modal-bg z-50 p-4">
    <div class="bg-white text-black p-8 rounded-2xl shadow-lg text-center w-11/12 max-w-xs">
      <h2 class="text-2xl font-bold mb-4">กรุณาเข้าสู่ระบบ</h2>
      <p class="mb-6">คุณต้องเข้าสู่ระบบเพื่อเข้าถึงหน้านี้</p>
      <div class="flex justify-center space-x-4">
        <a href="login.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">เข้าสู่ระบบ</a>
        <button onclick="hideModal()" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-lg">ย้อนกลับ</button>
      </div>
    </div>
  </div>

  <!-- COOKIE BANNER (เดิม) -->
  <div id="cookieBanner" class="cookie-banner fixed bottom-0 left-0 w-full p-4 flex flex-col md:flex-row justify-between items-center text-white text-sm z-50 hidden">
    <p class="mb-2 md:mb-0">เว็บไซต์นี้ใช้คุกกี้เพื่อพัฒนาประสบการณ์ของคุณ <a href="privacy.php" class="underline text-purple-300 ml-1">เรียนรู้เพิ่มเติม</a></p>
    <button onclick="acceptCookies()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg mt-2 md:mt-0">ยอมรับ</button>
  </div>

  <script>
    /* ===== PARTICLES ===== */
    (function(){
      const canvas = document.getElementById('about-canvas');
      const ctx = canvas.getContext('2d');
      let W, H, pts = [];
      function resize(){ W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
      window.addEventListener('resize', resize); resize();
      const COLS = ['rgba(168,85,247,','rgba(236,72,153,','rgba(34,211,238,'];
      for(let i=0;i<55;i++) pts.push({
        x:Math.random()*window.innerWidth, y:Math.random()*window.innerHeight,
        r:Math.random()*1.6+0.4,
        dx:(Math.random()-.5)*.3, dy:(Math.random()-.5)*.3,
        c:COLS[Math.floor(Math.random()*COLS.length)],
        a:Math.random()*.45+.15
      });
      function draw(){
        ctx.clearRect(0,0,W,H);
        pts.forEach(p=>{
          ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
          ctx.fillStyle=p.c+p.a+')'; ctx.fill();
          p.x+=p.dx; p.y+=p.dy;
          if(p.x<0||p.x>W)p.dx*=-1;
          if(p.y<0||p.y>H)p.dy*=-1;
        });
        requestAnimationFrame(draw);
      }
      draw();
    })();

    /* ===== TABS ===== */
    function switchTab(tab) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      document.querySelector('[data-tab="'+tab+'"]').classList.add('active');
      document.getElementById('tab-'+tab).classList.add('active');
    }

    /* ===== MODAL / COOKIE ===== */
    function showModal(){ document.getElementById("loginModal").classList.remove("hidden"); }
    function hideModal(){ document.getElementById("loginModal").classList.add("hidden"); }
    function acceptCookies(){
      localStorage.setItem("cookieAccepted","true");
      document.getElementById("cookieBanner").classList.add("hidden");
    }

    /* ===== MAIN ===== */
    document.addEventListener('DOMContentLoaded', function(){

      /* Cookie */
      if(!localStorage.getItem("cookieAccepted"))
        document.getElementById("cookieBanner").classList.remove("hidden");

      /* Scroll Reveal */
      const els = document.querySelectorAll('.scroll-reveal');
      const obs = new IntersectionObserver((entries)=>{
        entries.forEach(e=>{
          if(e.isIntersecting){ e.target.classList.add('is-visible'); obs.unobserve(e.target); }
        });
      },{ threshold:0.08 });
      els.forEach(el => obs.observe(el));

      /* Scroll-to-top */
      const btn = document.getElementById('scrollTopBtn');
      window.addEventListener('scroll', ()=> btn.classList.toggle('is-visible', window.scrollY > 400));
      btn.addEventListener('click', ()=> window.scrollTo(0,0));
    });
  </script>
</body>
</html>