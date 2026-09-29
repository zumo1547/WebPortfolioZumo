<?php
session_start();
include 'config.php';
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
$username = $_SESSION["username"];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ติดต่อฉัน - Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/Icon portfolio.png">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700;800;900&family=Noto+Sans+Thai:wght@400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">

  <style>
    *{box-sizing:border-box;margin:0;padding:0;}

    body{
      background:#07040f;
      font-family:'Noto Sans Thai','Kanit',sans-serif;
      min-height:100vh;overflow-x:hidden;color:#fff;
    }
    body::before{
      content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
      background-image:
        linear-gradient(rgba(168,85,247,.035) 1px,transparent 1px),
        linear-gradient(90deg,rgba(168,85,247,.035) 1px,transparent 1px);
      background-size:52px 52px;
    }
    #bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none;}
    .z1{position:relative;z-index:1;}

    /* ── FADE UP ── */
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
    .fu{opacity:0;animation:fadeUp .6s cubic-bezier(.165,.84,.44,1) forwards;}
    .d1{animation-delay:.05s;}.d2{animation-delay:.12s;}.d3{animation-delay:.2s;}
    .d4{animation-delay:.28s;}.d5{animation-delay:.36s;}.d6{animation-delay:.44s;}
    .d7{animation-delay:.52s;}.d8{animation-delay:.6s;}.d9{animation-delay:.68s;}

    /* ── SHIMMER ── */
    @keyframes shimmerBar{0%{background-position:0% 0;}100%{background-position:200% 0;}}

    /* ── GLITCH TITLE ── */
    .gtitle{
      font-family:'Kanit',sans-serif;font-weight:900;
      font-size:clamp(2.5rem,6vw,4rem);line-height:1;
      background:linear-gradient(135deg,#e879f9,#f472b6,#38bdf8);
      -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
      position:relative;display:inline-block;
    }
    .gtitle::before,.gtitle::after{
      content:attr(data-text);position:absolute;top:0;left:0;
      font-family:'Kanit',sans-serif;font-weight:900;font-size:inherit;
      -webkit-text-fill-color:transparent;background-clip:text;
    }
    .gtitle::before{background:linear-gradient(135deg,#38bdf8,#818cf8);-webkit-background-clip:text;animation:gA 5s infinite;}
    .gtitle::after{background:linear-gradient(135deg,#f472b6,#fb923c);-webkit-background-clip:text;animation:gB 5s infinite;}
    @keyframes gA{0%,87%,100%{opacity:0;}88%{opacity:.8;transform:translate(-3px,1px);clip-path:polygon(0 18%,100% 18%,100% 40%,0 40%);}90%{opacity:.8;transform:translate(3px,-2px);clip-path:polygon(0 58%,100% 58%,100% 76%,0 76%);}92%{opacity:0;}}
    @keyframes gB{0%,89%,100%{opacity:0;}90%{opacity:.7;transform:translate(4px,2px);clip-path:polygon(0 44%,100% 44%,100% 62%,0 62%);}93%{opacity:.7;transform:translate(-3px,0);clip-path:polygon(0 8%,100% 8%,100% 22%,0 22%);}95%{opacity:0;}}

    /* ── PANEL ── */
    .panel{
      background:rgba(10,7,22,.85);
      border:1px solid rgba(168,85,247,.15);
      border-radius:20px;overflow:hidden;
      box-shadow:0 0 50px rgba(124,58,237,.1),inset 0 1px 0 rgba(255,255,255,.04);
      position:relative;
    }
    .panel-bar{height:2px;background:linear-gradient(90deg,#7c3aed,#ec4899,#38bdf8,#ec4899,#7c3aed);background-size:200% 100%;animation:shimmerBar 3s linear infinite;}
    .panel::before,.panel::after{content:'';position:absolute;width:12px;height:12px;z-index:5;}
    .panel::before{top:9px;left:9px;border-top:2px solid rgba(168,85,247,.4);border-left:2px solid rgba(168,85,247,.4);}
    .panel::after{bottom:9px;right:9px;border-bottom:2px solid rgba(168,85,247,.4);border-right:2px solid rgba(168,85,247,.4);}
    .scanlines-inner{pointer-events:none;position:absolute;inset:0;border-radius:inherit;z-index:2;background:repeating-linear-gradient(0deg,transparent,transparent 3px,rgba(0,0,0,.05) 3px,rgba(0,0,0,.05) 4px);}

    /* ── SIGNAL CARDS ── */
    .sig-card{
      display:flex;align-items:center;gap:14px;
      padding:14px 16px;border-radius:14px;
      border:1px solid rgba(255,255,255,.06);
      background:rgba(255,255,255,.025);
      text-decoration:none;color:#fff;
      transition:all .25s cubic-bezier(.175,.885,.32,1.275);
      position:relative;overflow:hidden;
    }
    .sig-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--nc,#a855f7);opacity:0;transition:opacity .22s;box-shadow:0 0 14px var(--nc),0 0 28px var(--nc);border-radius:3px 0 0 3px;}
    .sig-card::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.04),transparent);transform:translateX(-130%);transition:transform .45s ease;pointer-events:none;}
    .sig-card:hover{border-color:var(--nc);background:rgba(168,85,247,.05);transform:translateX(7px);}
    .sig-card:hover::before{opacity:1;}
    .sig-card:hover::after{transform:translateX(130%);}
    .sig-icon{width:44px;height:44px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;border:1px solid rgba(255,255,255,.07);transition:transform .22s;}
    .sig-card:hover .sig-icon{transform:scale(1.1) rotate(-6deg);}
    .sig-arrow{margin-left:auto;font-size:.65rem;color:#374151;transition:all .22s;}
    .sig-card:hover .sig-arrow{color:#fff;transform:translateX(5px);}

    /* ── EMAIL ── */
    .email-box{display:flex;align-items:center;gap:8px;background:rgba(0,0,0,.25);border:1px solid rgba(168,85,247,.2);border-radius:12px;padding:11px 14px;transition:border-color .2s,box-shadow .2s;}
    .email-box:hover{border-color:rgba(168,85,247,.5);box-shadow:0 0 18px rgba(168,85,247,.12);}
    .copy-btn{background:linear-gradient(135deg,#7c3aed,#db2777);border:none;border-radius:9px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;flex-shrink:0;transition:transform .2s,box-shadow .2s;}
    .copy-btn:hover{transform:scale(1.1);box-shadow:0 0 16px rgba(168,85,247,.55);}
    .copy-btn:active{transform:scale(.9);}

    /* ── STATUS DOT ── */
    @keyframes sblink{0%,100%{opacity:1;}50%{opacity:.2;}}
    .sdot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#34d399;animation:sblink 2s ease-in-out infinite;}

    /* ════════════════════════════════
       FLOATING AI MESSENGER
    ════════════════════════════════ */

    /* Toggle button */
    #aiToggle{
      position:fixed;bottom:1.5rem;right:1.2rem;z-index:200;
      width:48px;height:48px;border-radius:50%;border:none;
      background:linear-gradient(135deg,#7c3aed,#db2777);
      color:#fff;cursor:pointer;
      box-shadow:0 0 20px rgba(168,85,247,.5),0 4px 16px rgba(0,0,0,.4);
      display:flex;align-items:center;justify-content:center;
      transition:transform .3s cubic-bezier(.175,.885,.32,1.275),box-shadow .3s;
      font-size:1.15rem;
    }
    #aiToggle:hover{transform:scale(1.1);box-shadow:0 0 30px rgba(168,85,247,.65),0 6px 20px rgba(0,0,0,.5);}

    @media(min-width:640px){
      #aiToggle{width:54px;height:54px;font-size:1.3rem;bottom:2rem;right:2rem;}
    }
    #aiToggle.open{transform:rotate(0deg);}

    /* Notification badge */
    #aiBadge{
      position:absolute;top:-2px;right:-2px;
      width:16px;height:16px;border-radius:50%;
      background:#ec4899;color:#fff;
      font-size:.55rem;font-weight:800;
      display:flex;align-items:center;justify-content:center;
      border:2px solid #07040f;
      animation:badgePop .4s cubic-bezier(.175,.885,.32,1.275);
    }
    @keyframes badgePop{from{transform:scale(0);}to{transform:scale(1);}}

    /* Chat window */
    #aiWindow{
      position:fixed;bottom:6.5rem;right:2rem;z-index:199;
      width:340px;
      border-radius:20px;
      overflow:hidden;
      box-shadow:0 0 50px rgba(124,58,237,.25),0 20px 60px rgba(0,0,0,.6);
      border:1px solid rgba(168,85,247,.2);
      background:rgba(8,5,20,.95);
      backdrop-filter:blur(20px);
      transform:scale(.85) translateY(20px);
      opacity:0;
      transition:transform .3s cubic-bezier(.175,.885,.32,1.275),opacity .3s ease;
      pointer-events:none;
    }
    #aiWindow.open{transform:scale(1) translateY(0);opacity:1;pointer-events:all;}

    /* Window header */
    .chat-header{
      padding:12px 16px;
      background:rgba(0,0,0,.3);
      border-bottom:1px solid rgba(168,85,247,.12);
      display:flex;align-items:center;gap:10px;
    }
    .ai-avatar{
      width:34px;height:34px;border-radius:50%;flex-shrink:0;
      background:linear-gradient(135deg,#7c3aed,#db2777);
      display:flex;align-items:center;justify-content:center;
      font-size:.9rem;box-shadow:0 0 12px rgba(168,85,247,.45);
    }

    /* Messages */
    .chat-msgs{
      height:280px;overflow-y:auto;padding:14px;
      display:flex;flex-direction:column;gap:10px;
      scrollbar-width:thin;scrollbar-color:rgba(168,85,247,.25) transparent;
    }
    .chat-msgs::-webkit-scrollbar{width:3px;}
    .chat-msgs::-webkit-scrollbar-thumb{background:rgba(168,85,247,.3);border-radius:3px;}

    .bubble{max-width:84%;padding:9px 13px;border-radius:14px;font-size:.8rem;line-height:1.55;animation:fadeUp .3s ease forwards;white-space:pre-wrap;}
    .bubble.ai{align-self:flex-start;background:rgba(124,58,237,.2);border:1px solid rgba(168,85,247,.22);border-bottom-left-radius:4px;color:#e9d5ff;}
    .bubble.user{align-self:flex-end;background:linear-gradient(135deg,rgba(124,58,237,.4),rgba(219,39,119,.3));border:1px solid rgba(236,72,153,.25);border-bottom-right-radius:4px;color:#fff;font-family:'Noto Sans Thai',sans-serif;}

    /* Typing dots */
    .typing-dots span{display:inline-block;width:5px;height:5px;border-radius:50%;background:#a855f7;margin:0 2px;animation:dotB 1.2s infinite ease-in-out;}
    .typing-dots span:nth-child(2){animation-delay:.2s;}
    .typing-dots span:nth-child(3){animation-delay:.4s;}
    @keyframes dotB{0%,60%,100%{transform:translateY(0);}30%{transform:translateY(-5px);}}

    /* Quick replies */
    .qr-row{padding:8px 12px;display:flex;gap:6px;flex-wrap:wrap;border-top:1px solid rgba(168,85,247,.08);}
    .qr-btn{padding:5px 11px;border-radius:999px;border:1px solid rgba(168,85,247,.3);background:rgba(168,85,247,.08);color:#c4b5fd;font-size:.7rem;cursor:pointer;transition:all .18s ease;white-space:nowrap;font-family:'Noto Sans Thai',sans-serif;}
    .qr-btn:hover{background:rgba(168,85,247,.22);border-color:rgba(168,85,247,.55);transform:translateY(-2px);}

    /* Input row */
    .chat-input-row{display:flex;gap:8px;padding:10px 12px;border-top:1px solid rgba(168,85,247,.1);background:rgba(0,0,0,.2);}
    .chat-input{flex:1;background:rgba(255,255,255,.04);border:1px solid rgba(168,85,247,.2);border-radius:10px;padding:8px 12px;color:#fff;font-size:.78rem;font-family:'Noto Sans Thai',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;}
    .chat-input::placeholder{color:#4b5563;}
    .chat-input:focus{border-color:rgba(168,85,247,.5);box-shadow:0 0 12px rgba(168,85,247,.1);}
    .chat-send{width:36px;height:36px;border-radius:10px;border:none;background:linear-gradient(135deg,#7c3aed,#db2777);color:#fff;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:transform .2s,box-shadow .2s;}
    .chat-send:hover{transform:scale(1.08);box-shadow:0 0 14px rgba(168,85,247,.5);}
    .chat-send:active{transform:scale(.92);}

    /* Mobile: window fullscreen-ish */
    @media(max-width:480px){
      #aiWindow{right:.8rem;width:calc(100vw - 1.6rem);bottom:5rem;}
      #aiToggle{right:1.2rem;bottom:1.5rem;}
    }

    .modal-bg{background-color:rgba(0,0,0,.65);backdrop-filter:blur(4px);}
    .cookie-banner{background:rgba(7,4,15,.97);border-top:1px solid rgba(168,85,247,.2);}
  </style>
</head>
<body class="flex flex-col min-h-screen">
<canvas id="bg-canvas"></canvas>
<?php include 'navbar.php'; ?>

<main class="flex-grow z1 px-4 py-10 sm:py-14">
<div class="max-w-lg mx-auto">

  <!-- PAGE TITLE -->
  <div class="text-center mb-8 fu d1">
    <p style="font-family:'Share Tech Mono',monospace;font-size:.62rem;color:rgba(168,85,247,.5);letter-spacing:.16em;" class="mb-2">// CONTACT.PHP</p>
    <h1 class="gtitle" data-text="CONTACT ME">CONTACT ME</h1>
    <p style="font-family:'Kanit',sans-serif;font-size:.95rem;color:rgba(255,255,255,.38);letter-spacing:.06em;margin-top:4px;">ติดต่อผม</p>
    <div class="flex items-center justify-center gap-2 mt-4">
      <span class="sdot"></span>
      <span style="font-family:'Share Tech Mono',monospace;font-size:.66rem;color:#34d399;letter-spacing:.07em;">SIGNAL ACTIVE · READY TO RECEIVE</span>
    </div>
  </div>

  <!-- CONTACT PANEL (centered) -->
  <div class="panel fu d3">
    <div class="scanlines-inner"></div>
    <div class="panel-bar"></div>

    <div style="padding:20px 22px;position:relative;z-index:3;">

      <p style="font-family:'Share Tech Mono',monospace;font-size:.6rem;color:rgba(168,85,247,.5);letter-spacing:.15em;margin-bottom:14px;">// CHANNELS</p>

      <div style="display:flex;flex-direction:column;gap:10px;">
        <a href="https://www.instagram.com/zumo_1547/" target="_blank" rel="noopener"
           class="sig-card fu d4" style="--nc:#f472b6;">
          <div class="sig-icon" style="background:linear-gradient(135deg,rgba(236,72,153,.2),rgba(251,146,60,.12));border-color:rgba(236,72,153,.22);">
            <i class="fab fa-instagram" style="color:#f472b6;"></i>
          </div>
          <div>
            <p style="font-weight:700;font-size:.88rem;">Instagram</p>
            <p style="color:#6b7280;font-size:.7rem;font-family:'Share Tech Mono',monospace;">@zumo_1547</p>
          </div>
          <i class="fas fa-arrow-right sig-arrow"></i>
        </a>

        <a href="https://www.facebook.com/profile.php?id=100020911959223" target="_blank" rel="noopener"
           class="sig-card fu d5" style="--nc:#60a5fa;">
          <div class="sig-icon" style="background:linear-gradient(135deg,rgba(59,130,246,.2),rgba(99,102,241,.12));border-color:rgba(59,130,246,.22);">
            <i class="fab fa-facebook" style="color:#60a5fa;"></i>
          </div>
          <div>
            <p style="font-weight:700;font-size:.88rem;">Facebook</p>
            <p style="color:#6b7280;font-size:.7rem;font-family:'Share Tech Mono',monospace;">Wutthipat Sriyangnok</p>
          </div>
          <i class="fas fa-arrow-right sig-arrow"></i>
        </a>

        <a href="https://github.com/zumo1547" target="_blank" rel="noopener"
           class="sig-card fu d6" style="--nc:#d1d5db;">
          <div class="sig-icon" style="background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1);">
            <i class="fab fa-github" style="color:#d1d5db;"></i>
          </div>
          <div>
            <p style="font-weight:700;font-size:.88rem;">GitHub</p>
            <p style="color:#6b7280;font-size:.7rem;font-family:'Share Tech Mono',monospace;">zumo1547</p>
          </div>
          <i class="fas fa-arrow-right sig-arrow"></i>
        </a>
      </div>

      <div style="height:1px;background:linear-gradient(90deg,transparent,rgba(168,85,247,.18),transparent);margin:16px 0;"></div>

      <p style="font-family:'Share Tech Mono',monospace;font-size:.6rem;color:rgba(168,85,247,.5);letter-spacing:.15em;margin-bottom:10px;">// EMAIL</p>
      <div class="email-box fu d7">
        <i class="fas fa-at" style="color:#a855f7;font-size:.78rem;flex-shrink:0;"></i>
        <span id="emailAddress" style="font-family:'Share Tech Mono',monospace;color:#c4b5fd;font-size:.78rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          pakkawan.zumo@gmail.com
        </span>
        <button id="copyEmailBtn" class="copy-btn" title="Copy">
          <i class="far fa-copy" id="copyIcon" style="font-size:.72rem;"></i>
        </button>
      </div>

    </div>
  </div>

  <p class="fu d9 text-center mt-5" style="font-family:'Share Tech Mono',monospace;font-size:.58rem;color:rgba(168,85,247,.22);letter-spacing:.1em;">
    ZUMO DEV · <?= date('Y') ?>
  </p>

</div>
</main>

<footer class="text-center py-4 z1" style="border-top:1px solid rgba(255,255,255,.04);">
  <p style="font-size:.65rem;color:rgba(255,255,255,.1);font-family:'Share Tech Mono',monospace;letter-spacing:.06em;">&copy; <?= date('Y') ?> ZUMO DEV PORTFOLIO</p>
</footer>

<!-- ════════════ FLOATING AI MESSENGER ════════════ -->
<!-- Chat Window -->
<div id="aiWindow">
  <div style="height:2px;background:linear-gradient(90deg,#7c3aed,#ec4899,#38bdf8,#ec4899,#7c3aed);background-size:200% 100%;animation:shimmerBar 3s linear infinite;"></div>

  <!-- Header -->
  <div class="chat-header">
    <div class="ai-avatar">🤖</div>
    <div>
      <p style="font-family:'Share Tech Mono',monospace;font-size:.8rem;color:#c4b5fd;font-weight:700;">ZUMO·AI</p>
      <div style="display:flex;align-items:center;gap:5px;">
        <span class="sdot" style="width:6px;height:6px;"></span>
        <span style="font-family:'Share Tech Mono',monospace;font-size:.58rem;color:#34d399;">ONLINE</span>
      </div>
    </div>
    <button onclick="toggleAI()" style="margin-left:auto;background:none;border:none;color:#6b7280;cursor:pointer;padding:4px;border-radius:6px;transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#6b7280'">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <!-- Messages -->
  <div class="chat-msgs" id="chatMessages"></div>

  <!-- Quick replies -->
  <div class="qr-row" id="quickReplies">
    <button class="qr-btn" onclick="sendQuick('สวัสดี 👋')">สวัสดี 👋</button>
    <button class="qr-btn" onclick="sendQuick('ทำอะไรได้บ้าง?')">ทำอะไรได้บ้าง?</button>
    <button class="qr-btn" onclick="sendQuick('เก่งอะไร?')">เก่งอะไร?</button>
    <button class="qr-btn" onclick="sendQuick('โปรเจกต์')">โปรเจกต์</button>
  </div>

  <!-- Input -->
  <div class="chat-input-row">
    <input id="chatInput" class="chat-input" type="text" placeholder="พิมพ์ข้อความ..." maxlength="150"
           onkeydown="if(event.key==='Enter') sendMsg()">
    <button class="chat-send" onclick="sendMsg()">
      <i class="fas fa-paper-plane" style="font-size:.72rem;"></i>
    </button>
  </div>
</div>

<!-- Toggle Button -->
<button id="aiToggle" onclick="toggleAI()" title="ZUMO·AI">
  <span id="aiToggleIcon">🤖</span>
  <span id="aiBadge">1</span>
</button>

<!-- LOGIN MODAL -->
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

<!-- COOKIE -->
<div id="cookieBanner" class="cookie-banner fixed bottom-0 left-0 w-full p-4 flex flex-col md:flex-row justify-between items-center text-white text-sm z-50 hidden" style="z-index:198;">
  <p class="mb-2 md:mb-0">เว็บไซต์นี้ใช้คุกกี้ <a href="privacy.php" class="underline text-purple-300 ml-1">เรียนรู้เพิ่มเติม</a></p>
  <button onclick="acceptCookies()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg mt-2 md:mt-0">ยอมรับ</button>
</div>

<script>
/* ── BG PARTICLES ── */
(function(){
  const c=document.getElementById('bg-canvas'),ctx=c.getContext('2d');
  let W,H,pts=[],mx=null,my=null;
  const COLS=['rgba(168,85,247,','rgba(236,72,153,','rgba(56,189,248,'];
  function resize(){W=c.width=innerWidth;H=c.height=innerHeight;}
  window.addEventListener('resize',()=>{resize();init();});
  window.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;});
  window.addEventListener('mouseout',()=>{mx=null;my=null;});
  resize();
  function init(){pts=[];const n=Math.min(Math.floor(W*H/10000),65);for(let i=0;i<n;i++)pts.push({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.25,vy:(Math.random()-.5)*.25,r:Math.random()*1.4+.4,c:COLS[Math.floor(Math.random()*COLS.length)],a:Math.random()*.28+.1});}
  function draw(){
    ctx.clearRect(0,0,W,H);
    for(let i=0;i<pts.length;i++)for(let j=i+1;j<pts.length;j++){const dx=pts[i].x-pts[j].x,dy=pts[i].y-pts[j].y,d2=dx*dx+dy*dy,md=(W/5.5)*(H/5.5);if(d2<md){ctx.strokeStyle=`rgba(168,85,247,${(1-d2/md)*.09})`;ctx.lineWidth=.6;ctx.beginPath();ctx.moveTo(pts[i].x,pts[i].y);ctx.lineTo(pts[j].x,pts[j].y);ctx.stroke();}}
    pts.forEach(p=>{if(mx!==null){const dx=mx-p.x,dy=my-p.y,d=Math.sqrt(dx*dx+dy*dy);if(d<85){const f=(85-d)/85;p.x-=dx/d*f*3;p.y-=dy/d*f*3;}}p.x+=p.vx;p.y+=p.vy;if(p.x<0||p.x>W)p.vx*=-1;if(p.y<0||p.y>H)p.vy*=-1;ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.c+p.a+')';ctx.fill();});
    requestAnimationFrame(draw);
  }
  init();draw();
})();

/* ── AI CHAT ── */
let aiOpen = false;
let firstOpen = true;

const AI_REPLIES = {
  'สวัสดี':       'สวัสดีครับ! 👋\nผมคือ ZUMO·AI ผู้ช่วยของ Wutthipat\nอยากรู้อะไรเกี่ยวกับเขาบ้างครับ?',
  'ติดต่อ':       'ช่องทางติดต่อ Wutthipat 📡\n• Instagram: @zumo_1547\n• Facebook: Wutthipat Sriyangnok\n• GitHub: zumo1547\n• Email: pakkawan.zumo@gmail.com',
  'ทำอะไร':       'Wutthipat พัฒนาได้หลายด้านครับ 🚀\n• 🎮 Game Dev (Roblox / Unity)\n• 🔌 IoT & Hardware (ESP32, Blynk)\n• 🤖 Robotics (POP32i, EspCam)\n• 🧠 AI / ML (Python, Object Detection)',
  'เก่งอะไร':     'จุดแข็งของ Wutthipat ครับ 💪\n• Lua & C# สำหรับเกม\n• ESP32 / Raspberry Pi\n• Train AI Model ด้วย Python\n• IoT Dashboard บน Blynk',
  'โปรเจกต์':     'โปรเจกต์เด่นๆ ครับ 🛠️\n• Smart Farm (ESP32 + Blynk)\n• AI Object Detection (Python)\n• ChatGPT Game AI (Roblox + Lua)\n• Robotic Arm (EspCam + Blynk)',
  'ชื่อ':         'ชื่อ Wutthipat Sriyangnok ครับ\nนายวุฒิภัทร ศรียางนอก\nNickname: Zumo | ซูโม่ ⚡',
  'ขอบคุณ':       'ยินดีครับ! 😊 มีอะไรให้ช่วยอีกได้เลยนะครับ',
  'default': [
    'น่าสนใจครับ! 🤔\nลองถามเกี่ยวกับ ผลงาน ทักษะ หรือการติดต่อได้เลยครับ',
    'ยินดีช่วยเสมอครับ! 🚀\nสอบถามเพิ่มเติมได้เลยนะครับ',
    'โอเคครับ! 😄\nถ้าอยากรู้เพิ่ม ลองถาม "เก่งอะไร?" หรือ "โปรเจกต์" ดูนะครับ'
  ]
};
let defIdx = 0;

function toggleAI(){
  aiOpen = !aiOpen;
  const win = document.getElementById('aiWindow');
  const icon = document.getElementById('aiToggleIcon');
  const badge = document.getElementById('aiBadge');

  if(aiOpen){
    win.classList.add('open');
    icon.textContent = '✕';
    if(badge) badge.remove();

    if(firstOpen){
      firstOpen = false;
      setTimeout(()=>{
        showTyping();
        setTimeout(()=>{
          hideTyping();
          addBubble('สวัสดีครับ! 👋 ผมคือ ZUMO·AI\nถามผมเรื่อง Wutthipat ได้เลยครับ\nหรือกดปุ่มด้านล่างเพื่อเริ่มต้น!','ai');
        },800);
      },200);
    }
  } else {
    win.classList.remove('open');
    icon.textContent = '🤖';
  }
}

const chatEl = document.getElementById('chatMessages');

function addBubble(text, role){
  const wrap = document.createElement('div');
  wrap.style.cssText = `display:flex;align-items:flex-end;gap:7px;${role==='user'?'flex-direction:row-reverse;':''}`;
  if(role==='ai'){
    const av=document.createElement('div');av.className='ai-avatar';av.style.width='26px';av.style.height='26px';av.style.fontSize='.72rem';av.textContent='🤖';
    wrap.appendChild(av);
  }
  const b=document.createElement('div');
  b.className=`bubble ${role}`;
  b.textContent=text;
  wrap.appendChild(b);
  chatEl.appendChild(wrap);
  chatEl.scrollTop=chatEl.scrollHeight;
}

function showTyping(){
  const wrap=document.createElement('div');wrap.id='typingIndicator';
  wrap.style.cssText='display:flex;align-items:flex-end;gap:7px;';
  const av=document.createElement('div');av.className='ai-avatar';av.style.width='26px';av.style.height='26px';av.style.fontSize='.72rem';av.textContent='🤖';
  const b=document.createElement('div');b.className='bubble ai typing-dots';
  b.innerHTML='<span></span><span></span><span></span>';
  wrap.appendChild(av);wrap.appendChild(b);chatEl.appendChild(wrap);chatEl.scrollTop=chatEl.scrollHeight;
}
function hideTyping(){const t=document.getElementById('typingIndicator');if(t)t.remove();}

function getReply(msg){
  const m=msg.toLowerCase();
  for(const key in AI_REPLIES){
    if(key!=='default'&&m.includes(key)) return AI_REPLIES[key];
  }
  const d=AI_REPLIES['default'];return d[defIdx++%d.length];
}

function sendMsg(){
  const inp=document.getElementById('chatInput');
  const msg=inp.value.trim();if(!msg)return;inp.value='';
  addBubble(msg,'user');showTyping();
  setTimeout(()=>{hideTyping();addBubble(getReply(msg),'ai');},850);
}
function sendQuick(msg){document.getElementById('chatInput').value=msg;sendMsg();}

/* ── MODAL / COOKIE ── */
function showModal(){document.getElementById("loginModal").classList.remove("hidden");}
function hideModal(){document.getElementById("loginModal").classList.add("hidden");}
function acceptCookies(){localStorage.setItem("cookieAccepted","true");document.getElementById("cookieBanner").classList.add("hidden");}

document.addEventListener('DOMContentLoaded',function(){
  if(!localStorage.getItem("cookieAccepted"))document.getElementById("cookieBanner").classList.remove("hidden");

  /* Email copy */
  const btn=document.getElementById('copyEmailBtn'),icon=document.getElementById('copyIcon');
  const email=document.getElementById('emailAddress').innerText.trim();
  let t;
  btn.addEventListener('click',()=>{
    navigator.clipboard.writeText(email).then(()=>{
      icon.className='fas fa-check';
      btn.style.background='linear-gradient(135deg,#059669,#10b981)';
      clearTimeout(t);t=setTimeout(()=>{icon.className='far fa-copy';btn.style.background='linear-gradient(135deg,#7c3aed,#db2777)';},2000);
    }).catch(()=>{const ta=document.createElement('textarea');ta.value=email;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);});
  });
});
</script>
</body>
</html>