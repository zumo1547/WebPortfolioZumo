<?php
session_start();
require 'config.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_email'];
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $otp = trim($_POST["otp"]);

    if (!preg_match("/^[0-9]{6}$/",$otp)) {

        $error = "กรุณากรอกรหัส OTP 6 หลัก";

    } else {

        $stmt = $pdo->prepare("
        SELECT id FROM password_resets
        WHERE email = ?
        AND token = ?
        AND used = 0
        AND expires_at > NOW()
        ");

        $stmt->execute([$email,$otp]);

        if ($stmt->rowCount() > 0) {

            $update = $pdo->prepare("
            UPDATE password_resets
            SET used = 1
            WHERE email = ? AND token = ?
            ");

            $update->execute([$email,$otp]);

            $_SESSION["otp_verified"] = true;

            header("Location: reset_password.php");
            exit;

        } else {

            $error = "OTP ไม่ถูกต้องหรือหมดอายุแล้ว";

        }

    }

}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ยืนยัน OTP - Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/Icon portfolio.png">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600;700;800&family=Noto+Sans+Thai:wght@400;500;600&family=Share+Tech+Mono&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:#07040f;font-family:'Noto Sans Thai','Kanit',sans-serif;min-height:100vh;overflow-x:hidden;color:#fff;}
    body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(168,85,247,.035)1px,transparent 1px),linear-gradient(90deg,rgba(168,85,247,.035)1px,transparent 1px);background-size:52px 52px;}
    #bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none;}
    .z1{position:relative;z-index:1;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
    .fu{opacity:0;animation:fadeUp .6s cubic-bezier(.165,.84,.44,1) forwards;}
    .d1{animation-delay:.05s;}.d2{animation-delay:.12s;}
    @keyframes shimmerBar{0%{background-position:0% 0;}100%{background-position:200% 0;}}
    .panel{background:rgba(10,7,22,.88);border:1px solid rgba(168,85,247,.18);border-radius:22px;overflow:hidden;box-shadow:0 0 60px rgba(124,58,237,.12),inset 0 1px 0 rgba(255,255,255,.04);position:relative;}
    .panel::before,.panel::after{content:'';position:absolute;width:12px;height:12px;z-index:5;}
    .panel::before{top:9px;left:9px;border-top:2px solid rgba(168,85,247,.4);border-left:2px solid rgba(168,85,247,.4);}
    .panel::after{bottom:9px;right:9px;border-bottom:2px solid rgba(168,85,247,.4);border-right:2px solid rgba(168,85,247,.4);}
    .btn-primary{width:100%;padding:13px;border:none;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#db2777);color:#fff;font-weight:700;font-size:.95rem;font-family:'Kanit',sans-serif;cursor:pointer;transition:transform .2s,box-shadow .2s;box-shadow:0 4px 20px rgba(124,58,237,.35);}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(124,58,237,.5);}
    .error-box{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:10px 14px;color:#fca5a5;font-size:.82rem;display:flex;align-items:center;gap:8px;}

    /* OTP boxes */
    .otp-boxes{display:flex;gap:10px;justify-content:center;margin:20px 0;}
    .otp-box{
      width:48px;height:58px;border-radius:12px;
      background:rgba(255,255,255,.04);
      border:1px solid rgba(168,85,247,.25);
      color:#e9d5ff;font-size:1.5rem;font-weight:800;text-align:center;
      font-family:'Share Tech Mono',monospace;outline:none;
      transition:border-color .2s,box-shadow .2s;caret-color:transparent;
    }
    .otp-box:focus{border-color:rgba(168,85,247,.7);box-shadow:0 0 18px rgba(168,85,247,.2);}
    .otp-box.filled{border-color:rgba(168,85,247,.5);background:rgba(168,85,247,.1);}

    /* Countdown */
    #countdown{font-family:'Share Tech Mono',monospace;font-size:.75rem;color:#6b7280;letter-spacing:.06em;}
    #countdown.urgent{color:#f87171;}
  </style>
</head>
<body class="flex items-center justify-center min-h-screen px-4 py-10">
<canvas id="bg-canvas"></canvas>
<div class="w-full max-w-sm z1">

  <div class="text-center mb-8 fu d1">
    <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#db2777);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 0 24px rgba(168,85,247,.5);font-size:1.6rem;">📨</div>
    <h1 style="font-family:'Kanit',sans-serif;font-weight:800;font-size:1.6rem;background:linear-gradient(135deg,#e879f9,#f472b6,#38bdf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">ยืนยัน OTP</h1>
    <p style="font-family:'Share Tech Mono',monospace;font-size:.65rem;color:rgba(168,85,247,.5);letter-spacing:.14em;margin-top:4px;">// VERIFY_OTP.PHP</p>
  </div>

  <div class="panel fu d2">
    <div style="height:2px;background:linear-gradient(90deg,#7c3aed,#ec4899,#38bdf8,#ec4899,#7c3aed);background-size:200% 100%;animation:shimmerBar 3s linear infinite;"></div>
    <div style="padding:28px 26px;position:relative;z-index:3;">

      <p style="color:#9ca3af;font-size:.85rem;margin-bottom:6px;text-align:center;">ส่งรหัส OTP ไปยัง</p>
      <p style="color:#c4b5fd;font-size:.9rem;font-family:'Share Tech Mono',monospace;text-align:center;margin-bottom:20px;"><?= htmlspecialchars($email) ?></p>

      <?php if($error): ?>
      <div class="error-box" style="margin-bottom:16px;">
        <i class="fas fa-exclamation-triangle" style="color:#f87171;flex-shrink:0;"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" id="otpForm">
        <!-- Hidden input stores combined OTP -->
        <input type="hidden" name="otp" id="otpHidden">

        <!-- 6 visual boxes -->
        <div class="otp-boxes">
          <?php for($i=0;$i<6;$i++): ?>
          <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]"
                 id="otp<?=$i?>" oninput="otpInput(this,<?=$i?>)" onkeydown="otpKey(event,<?=$i?>)" onpaste="otpPaste(event)">
          <?php endfor; ?>
        </div>

        <!-- Countdown -->
        <p style="text-align:center;margin-bottom:18px;">
          <span id="countdown">หมดอายุใน 10:00</span>
        </p>

        <button type="submit" class="btn-primary" onclick="combineOTP()">
          <i class="fas fa-shield-alt" style="margin-right:8px;"></i>ยืนยัน OTP
        </button>
      </form>

      <div style="height:1px;background:linear-gradient(90deg,transparent,rgba(168,85,247,.18),transparent);margin:20px 0;"></div>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <a href="forgot_password.php" style="font-size:.8rem;color:#6b7280;text-decoration:none;">
          <i class="fas fa-arrow-left" style="margin-right:4px;font-size:.72rem;"></i>กลับ
        </a>
        <a href="forgot_password.php" style="font-size:.8rem;color:#a855f7;text-decoration:none;font-weight:600;">
          <i class="fas fa-redo" style="margin-right:4px;font-size:.7rem;"></i>ส่ง OTP ใหม่
        </a>
      </div>
    </div>
  </div>
</div>

<script>
/* OTP boxes logic */
function otpInput(el, idx){
  el.value = el.value.replace(/[^0-9]/g,'').slice(-1);
  el.classList.toggle('filled', el.value !== '');
  if(el.value && idx < 5) document.getElementById('otp'+(idx+1)).focus();
}
function otpKey(e, idx){
  if(e.key==='Backspace' && !e.target.value && idx>0){
    document.getElementById('otp'+(idx-1)).focus();
  }
}
function otpPaste(e){
  e.preventDefault();
  const digits=(e.clipboardData.getData('text').replace(/\D/g,'')).slice(0,6);
  for(let i=0;i<digits.length;i++){
    const box=document.getElementById('otp'+i);
    if(box){box.value=digits[i];box.classList.add('filled');}
  }
  const last=document.getElementById('otp'+(Math.min(digits.length,5)));
  if(last)last.focus();
}
function combineOTP(){
  let v='';for(let i=0;i<6;i++)v+=document.getElementById('otp'+i).value;
  document.getElementById('otpHidden').value=v;
}

/* Countdown 10 min */
let seconds = 600;
const cdEl = document.getElementById('countdown');
const timer = setInterval(()=>{
  seconds--;
  if(seconds<=0){clearInterval(timer);cdEl.textContent='OTP หมดอายุแล้ว กรุณาขอใหม่';cdEl.className='urgent';return;}
  const m=Math.floor(seconds/60),s=seconds%60;
  cdEl.textContent='หมดอายุใน '+m+':'+(s<10?'0':'')+s;
  if(seconds<=60)cdEl.classList.add('urgent');
},1000);

/* BG */
(function(){const c=document.getElementById('bg-canvas'),ctx=c.getContext('2d');let W,H,pts=[],mx=null,my=null;const COLS=['rgba(168,85,247,','rgba(236,72,153,','rgba(56,189,248,'];function resize(){W=c.width=innerWidth;H=c.height=innerHeight;}window.addEventListener('resize',()=>{resize();init();});window.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;});resize();function init(){pts=[];const n=Math.min(Math.floor(W*H/12000),50);for(let i=0;i<n;i++)pts.push({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.2,vy:(Math.random()-.5)*.2,r:Math.random()*1.3+.3,c:COLS[Math.floor(Math.random()*COLS.length)],a:Math.random()*.25+.08});}function draw(){ctx.clearRect(0,0,W,H);pts.forEach(p=>{if(mx!==null){const dx=mx-p.x,dy=my-p.y,d=Math.sqrt(dx*dx+dy*dy);if(d<70){const f=(70-d)/70;p.x-=dx/d*f*2.5;p.y-=dy/d*f*2.5;}}p.x+=p.vx;p.y+=p.vy;if(p.x<0||p.x>W)p.vx*=-1;if(p.y<0||p.y>H)p.vy*=-1;ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.c+p.a+')';ctx.fill();});requestAnimationFrame(draw);}init();draw();})();
</script>
</body>
</html>
