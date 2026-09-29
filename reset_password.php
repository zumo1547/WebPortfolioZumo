<?php
session_start();
require 'config.php';

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION["reset_email"];

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

$password = trim($_POST["password"]);
$confirm = trim($_POST["confirm"]);

if(strlen($password) < 6){

$error = "รหัสผ่านต้องมีอย่างน้อย 6 ตัว";

}

elseif($password !== $confirm){

$error = "รหัสผ่านไม่ตรงกัน";

}

else{

$hash = password_hash($password,PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
UPDATE users
SET password = ?
WHERE email = ?
");

$stmt->execute([$hash,$email]);

$delete = $pdo->prepare("
DELETE FROM password_resets
WHERE email = ?
");

$delete->execute([$email]);

unset($_SESSION["reset_email"]);
unset($_SESSION["otp_verified"]);

$success = "เปลี่ยนรหัสผ่านสำเร็จ";

header("refresh:2;url=login.php");

}

}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ตั้งรหัสผ่านใหม่ - Portfolio</title>
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
    .form-input{width:100%;background:rgba(255,255,255,.04);border:1px solid rgba(168,85,247,.2);border-radius:12px;padding:12px 16px 12px 42px;color:#fff;font-size:.88rem;font-family:'Noto Sans Thai',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;}
    .form-input::placeholder{color:#4b5563;}
    .form-input:focus{border-color:rgba(168,85,247,.55);box-shadow:0 0 18px rgba(168,85,247,.12);}
    .form-input.error{border-color:rgba(239,68,68,.5);}
    .form-input.ok{border-color:rgba(52,211,153,.5);}
    .btn-primary{width:100%;padding:13px;border:none;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#db2777);color:#fff;font-weight:700;font-size:.95rem;font-family:'Kanit',sans-serif;cursor:pointer;transition:transform .2s,box-shadow .2s;box-shadow:0 4px 20px rgba(124,58,237,.35);}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(124,58,237,.5);}
    .error-box{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:10px 14px;color:#fca5a5;font-size:.82rem;display:flex;align-items:center;gap:8px;}
    .success-box{background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);border-radius:10px;padding:10px 14px;color:#6ee7b7;font-size:.82rem;display:flex;align-items:center;gap:8px;}
    #strengthBar{height:4px;border-radius:4px;transition:all .3s ease;width:0%;}
  </style>
</head>
<body class="flex items-center justify-center min-h-screen px-4 py-10">
<canvas id="bg-canvas"></canvas>
<div class="w-full max-w-sm z1">

  <div class="text-center mb-8 fu d1">
    <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#059669,#10b981);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 0 24px rgba(16,185,129,.5);font-size:1.6rem;">🔓</div>
    <h1 style="font-family:'Kanit',sans-serif;font-weight:800;font-size:1.6rem;background:linear-gradient(135deg,#6ee7b7,#34d399,#38bdf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">ตั้งรหัสผ่านใหม่</h1>
    <p style="font-family:'Share Tech Mono',monospace;font-size:.65rem;color:rgba(52,211,153,.5);letter-spacing:.14em;margin-top:4px;">// RESET_PASSWORD.PHP</p>
  </div>

  <div class="panel fu d2" style="border-color:rgba(52,211,153,.18);">
    <div style="height:2px;background:linear-gradient(90deg,#059669,#34d399,#38bdf8,#34d399,#059669);background-size:200% 100%;animation:shimmerBar 3s linear infinite;"></div>
    <div style="padding:28px 26px;position:relative;z-index:3;">

      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
        <i class="fas fa-check-circle" style="color:#34d399;font-size:.8rem;"></i>
        <span style="font-family:'Share Tech Mono',monospace;font-size:.68rem;color:#34d399;letter-spacing:.08em;">OTP VERIFIED</span>
      </div>
      <p style="color:#9ca3af;font-size:.82rem;margin-bottom:22px;">ตั้งรหัสผ่านใหม่สำหรับ <span style="color:#c4b5fd;"><?= htmlspecialchars($email) ?></span></p>

      <?php if($error): ?>
      <div class="error-box" style="margin-bottom:16px;">
        <i class="fas fa-exclamation-triangle" style="color:#f87171;flex-shrink:0;"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <?php if($success): ?>
      <div class="success-box" style="margin-bottom:16px;">
        <i class="fas fa-check-circle" style="color:#34d399;flex-shrink:0;"></i>
        <span><?= htmlspecialchars($success) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" style="display:flex;flex-direction:column;gap:14px;">
        <!-- New password -->
        <div>
          <div style="position:relative;">
            <i class="fas fa-lock" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.8rem;z-index:1;"></i>
            <input type="password" name="password" id="passNew" class="form-input" placeholder="รหัสผ่านใหม่ (อย่างน้อย 6 ตัว)" required oninput="checkStrength(this.value)">
            <button type="button" onclick="togglePass('passNew','eye1')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;">
              <i class="fas fa-eye" id="eye1" style="font-size:.8rem;"></i>
            </button>
          </div>
          <div style="margin-top:6px;background:rgba(255,255,255,.06);border-radius:4px;height:4px;">
            <div id="strengthBar"></div>
          </div>
          <p id="strengthText" style="font-size:.7rem;color:#6b7280;margin-top:4px;font-family:'Share Tech Mono',monospace;"></p>
        </div>

        <!-- Confirm -->
        <div style="position:relative;">
          <i class="fas fa-lock" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.8rem;z-index:1;"></i>
          <input type="password" name="confirm" id="passConfirm" class="form-input" placeholder="ยืนยันรหัสผ่านใหม่" required oninput="checkMatch()">
          <button type="button" onclick="togglePass('passConfirm','eye2')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;">
            <i class="fas fa-eye" id="eye2" style="font-size:.8rem;"></i>
          </button>
        </div>
        <p id="matchText" style="font-size:.7rem;color:#6b7280;margin-top:-8px;font-family:'Share Tech Mono',monospace;"></p>

        <button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#059669,#0d9488);box-shadow:0 4px 20px rgba(5,150,105,.35);">
          <i class="fas fa-save" style="margin-right:8px;"></i>บันทึกรหัสผ่านใหม่
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function togglePass(id,eyeId){const inp=document.getElementById(id),icon=document.getElementById(eyeId);if(inp.type==='password'){inp.type='text';icon.className='fas fa-eye-slash';}else{inp.type='password';icon.className='fas fa-eye';}}
function checkStrength(v){const bar=document.getElementById('strengthBar'),txt=document.getElementById('strengthText');if(!v){bar.style.width='0%';txt.textContent='';return;}let s=0;if(v.length>=6)s++;if(v.length>=10)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;const levels=[{w:'20%',c:'#ef4444',t:'WEAK'},{w:'40%',c:'#f97316',t:'FAIR'},{w:'60%',c:'#eab308',t:'GOOD'},{w:'80%',c:'#22c55e',t:'STRONG'},{w:'100%',c:'#10b981',t:'VERY STRONG'}];const l=levels[Math.min(s-1,4)]||levels[0];bar.style.width=l.w;bar.style.background=l.c;txt.textContent=l.t;txt.style.color=l.c;}
function checkMatch(){const p=document.getElementById('passNew').value,c=document.getElementById('passConfirm'),mt=document.getElementById('matchText');if(!c.value){mt.textContent='';c.className='form-input';return;}if(p===c.value){mt.textContent='✓ รหัสผ่านตรงกัน';mt.style.color='#34d399';c.className='form-input ok';}else{mt.textContent='✗ รหัสผ่านไม่ตรงกัน';mt.style.color='#f87171';c.className='form-input error';}}
(function(){const c=document.getElementById('bg-canvas'),ctx=c.getContext('2d');let W,H,pts=[],mx=null,my=null;const COLS=['rgba(52,211,153,','rgba(16,185,129,','rgba(56,189,248,'];function resize(){W=c.width=innerWidth;H=c.height=innerHeight;}window.addEventListener('resize',()=>{resize();init();});window.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;});resize();function init(){pts=[];const n=Math.min(Math.floor(W*H/12000),50);for(let i=0;i<n;i++)pts.push({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.2,vy:(Math.random()-.5)*.2,r:Math.random()*1.3+.3,c:COLS[Math.floor(Math.random()*COLS.length)],a:Math.random()*.22+.08});}function draw(){ctx.clearRect(0,0,W,H);pts.forEach(p=>{if(mx!==null){const dx=mx-p.x,dy=my-p.y,d=Math.sqrt(dx*dx+dy*dy);if(d<70){const f=(70-d)/70;p.x-=dx/d*f*2.5;p.y-=dy/d*f*2.5;}}p.x+=p.vx;p.y+=p.vy;if(p.x<0||p.x>W)p.vx*=-1;if(p.y<0||p.y>H)p.vy*=-1;ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.c+p.a+')';ctx.fill();});requestAnimationFrame(draw);}init();draw();})();
</script>
</body>
</html>
