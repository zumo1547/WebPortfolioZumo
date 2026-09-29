<?php
session_start();

// ============================================================
//  TIMEZONE — ตั้งเวลาไทย (UTC+7) ก่อนทุกอย่าง
// ============================================================
date_default_timezone_set('Asia/Bangkok');

require 'config.php';

// ============================================================
//  SECURITY HEADERS
// ============================================================
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:;");

// ============================================================
//  CONFIG
// ============================================================
define('MAX_ATTEMPTS',   5);      // จำนวนครั้งที่ login ผิดสูงสุด
define('LOCKOUT_TIME',   900);    // ล็อค 15 นาที (วินาที)
define('ATTEMPT_WINDOW', 600);    // นับรอบ 10 นาที (วินาที)
define('RATE_PER_IP',    10);     // request สูงสุดต่อ IP ต่อ 1 นาที

// ============================================================
//  HELPER: ดึง IP จริง (รองรับ proxy/nginx)
// ============================================================
function getClientIP(): string {
    $keys = ['HTTP_CF_CONNECTING_IP','HTTP_X_REAL_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ============================================================
//  RATE LIMIT (ป้องกัน spam/DDoS ระดับ IP)
//  ใช้ $_SESSION แทน DB — เบา ไม่ต้องตาราง
// ============================================================
function checkRateLimit(string $ip): bool {
    $key = 'rl_' . md5($ip);
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'window_start' => $now];
    }

    // รีเซ็ตถ้าเกินหน้าต่างเวลา
    if ($now - $_SESSION[$key]['window_start'] > 60) {
        $_SESSION[$key] = ['count' => 0, 'window_start' => $now];
    }

    $_SESSION[$key]['count']++;

    return $_SESSION[$key]['count'] <= RATE_PER_IP;
}

// ============================================================
//  BRUTE-FORCE LOCKOUT (ระดับ email + IP)
//  เก็บใน DB ตาราง login_attempts
//  SQL สร้างตาราง:
//    CREATE TABLE login_attempts (
//      id         INT AUTO_INCREMENT PRIMARY KEY,
//      identifier VARCHAR(255) NOT NULL,
//      ip         VARCHAR(45)  NOT NULL,
//      attempted_at DATETIME   NOT NULL,
//      INDEX(identifier, attempted_at)
//    );
// ============================================================
function countRecentAttempts(PDO $pdo, string $identifier, string $ip): int {
    $since = date('Y-m-d H:i:s', time() - ATTEMPT_WINDOW);
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_attempts
        WHERE (identifier = ? OR ip = ?)
          AND attempted_at > ?
    ");
    $stmt->execute([$identifier, $ip, $since]);
    return (int) $stmt->fetchColumn();
}

function recordAttempt(PDO $pdo, string $identifier, string $ip): void {
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (identifier, ip, attempted_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$identifier, $ip]);
}

function clearAttempts(PDO $pdo, string $identifier, string $ip): void {
    $stmt = $pdo->prepare("
        DELETE FROM login_attempts
        WHERE identifier = ? OR ip = ?
    ");
    $stmt->execute([$identifier, $ip]);
}

// ============================================================
//  CSRF TOKEN
// ============================================================
function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================
//  REDIRECT ถ้า login แล้ว
// ============================================================
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error        = "";
$lockout_left = 0;
$ip           = getClientIP();

// ============================================================
//  POST — ประมวลผล Login
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Rate limit ระดับ IP
    if (!checkRateLimit($ip)) {
        $error = "คุณส่งคำขอเร็วเกินไป กรุณารอสักครู่แล้วลองใหม่";

    // 2) ตรวจ CSRF
    } elseif (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = "คำขอไม่ถูกต้อง (CSRF) กรุณาโหลดหน้าใหม่";
        unset($_SESSION['csrf_token']); // สร้าง token ใหม่

    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$email || !$password) {
            $error = "กรุณากรอกข้อมูลให้ครบ";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "รูปแบบอีเมลไม่ถูกต้อง";

        } else {
            // 3) ตรวจ Brute-force lockout
            $attempts = countRecentAttempts($pdo, $email, $ip);

            if ($attempts >= MAX_ATTEMPTS) {
                // คำนวณเวลาที่เหลือ
                $stmt = $pdo->prepare("
                    SELECT attempted_at FROM login_attempts
                    WHERE (identifier = ? OR ip = ?)
                    ORDER BY attempted_at DESC LIMIT 1
                ");
                $stmt->execute([$email, $ip]);
                $last = $stmt->fetchColumn();
                $lockout_left = LOCKOUT_TIME - (time() - strtotime($last));
                $lockout_left = max(0, (int) $lockout_left);

                $mins = ceil($lockout_left / 60);
                $error = "บัญชีถูกล็อคชั่วคราว กรุณารอ {$mins} นาทีแล้วลองใหม่";

            } else {
                // 4) ดึง user จาก DB
                $stmt = $pdo->prepare("
                    SELECT * FROM users WHERE email = ? LIMIT 1
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // ✅ Login สำเร็จ

                    // ล้างประวัติ attempt
                    clearAttempts($pdo, $email, $ip);

                    // Session Fixation Protection — สร้าง session ID ใหม่
                    session_regenerate_id(true);

                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role']     = $user['role'];

                    // ════════════════════════════════════════════════
                    // ✅ FIX: บันทึก Login Log ลงตาราง user_logs
                    //    (โค้ดเดิมหายไป ทำให้ dashboard แสดง Log ว่าง)
                    // ════════════════════════════════════════════════
                    try {
                        // ใช้ PHP date() แทน NOW() เพื่อให้ได้เวลาไทย (Asia/Bangkok)
                        $logStmt = $pdo->prepare("
                            INSERT INTO user_logs (user_id, login_time)
                            VALUES (?, ?)
                        ");
                        $logStmt->execute([$user['id'], date('Y-m-d H:i:s')]);
                    } catch (Exception $e) {
                        error_log('[Login Log INSERT] ' . $e->getMessage());
                        // ไม่หยุดการ login แม้ log บันทึกไม่ได้
                    }

                    // ล้าง CSRF token เก่า
                    unset($_SESSION['csrf_token']);

                    header("Location: index.php");
                    exit;

                } else {
                    // ❌ Login ผิด — บันทึก attempt
                    recordAttempt($pdo, $email, $ip);

                    $remaining = MAX_ATTEMPTS - ($attempts + 1);
                    if ($remaining > 0) {
                        $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง (เหลืออีก {$remaining} ครั้งก่อนถูกล็อค)";
                    } else {
                        $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง บัญชีถูกล็อค กรุณารอ " . (LOCKOUT_TIME/60) . " นาที";
                    }
                }
            }
        }
    }
}

$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ - Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/Icon portfolio.png">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600;700;800&family=Noto+Sans+Thai:wght@400;500;600&family=Share+Tech+Mono&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{
      background:#07040f;
      font-family:'Noto Sans Thai','Kanit',sans-serif;
      min-height:100vh;overflow-x:hidden;color:#fff;
    }
    body::before{
      content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
      background-image:linear-gradient(rgba(168,85,247,.035)1px,transparent 1px),linear-gradient(90deg,rgba(168,85,247,.035)1px,transparent 1px);
      background-size:52px 52px;
    }
    #bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none;}
    .z1{position:relative;z-index:1;}

    @keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
    .fu{opacity:0;animation:fadeUp .6s cubic-bezier(.165,.84,.44,1) forwards;}
    .d1{animation-delay:.05s;}.d2{animation-delay:.12s;}.d3{animation-delay:.2s;}.d4{animation-delay:.28s;}

    @keyframes shimmerBar{0%{background-position:0% 0;}100%{background-position:200% 0;}}

    .panel{
      background:rgba(10,7,22,.88);
      border:1px solid rgba(168,85,247,.18);
      border-radius:22px;overflow:hidden;
      box-shadow:0 0 60px rgba(124,58,237,.12),inset 0 1px 0 rgba(255,255,255,.04);
      position:relative;
    }
    .panel::before,.panel::after{content:'';position:absolute;width:12px;height:12px;z-index:5;}
    .panel::before{top:9px;left:9px;border-top:2px solid rgba(168,85,247,.4);border-left:2px solid rgba(168,85,247,.4);}
    .panel::after{bottom:9px;right:9px;border-bottom:2px solid rgba(168,85,247,.4);border-right:2px solid rgba(168,85,247,.4);}

    .form-input{
      width:100%;background:rgba(255,255,255,.04);
      border:1px solid rgba(168,85,247,.2);border-radius:12px;
      padding:12px 16px 12px 42px;color:#fff;font-size:.88rem;
      font-family:'Noto Sans Thai',sans-serif;outline:none;
      transition:border-color .2s,box-shadow .2s;
    }
    .form-input::placeholder{color:#4b5563;}
    .form-input:focus{border-color:rgba(168,85,247,.55);box-shadow:0 0 18px rgba(168,85,247,.12);}

    .btn-primary{
      width:100%;padding:13px;border:none;border-radius:12px;
      background:linear-gradient(135deg,#7c3aed,#db2777);
      color:#fff;font-weight:700;font-size:.95rem;
      font-family:'Kanit',sans-serif;cursor:pointer;
      transition:transform .2s,box-shadow .2s,opacity .2s;
      box-shadow:0 4px 20px rgba(124,58,237,.35);
    }
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(124,58,237,.5);}
    .btn-primary:active{transform:translateY(0);opacity:.9;}

    .error-box{
      background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);
      border-radius:10px;padding:10px 14px;
      color:#fca5a5;font-size:.82rem;display:flex;align-items:center;gap:8px;
    }

    @keyframes sblink{0%,100%{opacity:1;}50%{opacity:.2;}}
    .sdot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#34d399;animation:sblink 2s ease-in-out infinite;}
  </style>
</head>
<body class="flex items-center justify-center min-h-screen px-4 py-10">
<canvas id="bg-canvas"></canvas>

<div class="w-full max-w-sm z1">

  <!-- Logo / title -->
  <div class="text-center mb-8 fu d1">
    <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#db2777);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 0 24px rgba(168,85,247,.5);font-size:1.6rem;">⚡</div>
    <h1 style="font-family:'Kanit',sans-serif;font-weight:800;font-size:1.8rem;background:linear-gradient(135deg,#e879f9,#f472b6,#38bdf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">ZUMO PORTFOLIO</h1>
    <p style="font-family:'Share Tech Mono',monospace;font-size:.65rem;color:rgba(168,85,247,.5);letter-spacing:.14em;margin-top:4px;">// LOGIN.PHP</p>
  </div>

  <div class="panel fu d2">
    <div style="height:2px;background:linear-gradient(90deg,#7c3aed,#ec4899,#38bdf8,#ec4899,#7c3aed);background-size:200% 100%;animation:shimmerBar 3s linear infinite;"></div>

    <div style="padding:28px 26px;position:relative;z-index:3;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:22px;">
        <span class="sdot"></span>
        <span style="font-family:'Share Tech Mono',monospace;font-size:.68rem;color:#34d399;letter-spacing:.08em;">SECURE LOGIN</span>
      </div>

      <?php if($error): ?>
      <div class="error-box fu d1" style="margin-bottom:16px;">
        <i class="fas fa-exclamation-triangle" style="color:#f87171;flex-shrink:0;"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" style="display:flex;flex-direction:column;gap:14px;" autocomplete="off">

        <!-- CSRF Token (hidden) -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <!-- Email -->
        <div style="position:relative;">
          <i class="fas fa-at" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.82rem;z-index:1;"></i>
          <input type="email" name="email" class="form-input" placeholder="อีเมล" required
                 maxlength="254"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <!-- Password -->
        <div style="position:relative;">
          <i class="fas fa-lock" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.82rem;z-index:1;"></i>
          <input type="password" name="password" id="passwordInput" class="form-input"
                 placeholder="รหัสผ่าน" required maxlength="128">
          <button type="button" onclick="togglePass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;padding:4px;" id="eyeBtn">
            <i class="fas fa-eye" id="eyeIcon" style="font-size:.82rem;"></i>
          </button>
        </div>

        <!-- Forgot password link -->
        <div style="text-align:right;margin-top:-6px;">
          <a href="forgot_password.php" style="font-size:.78rem;color:#a855f7;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#c084fc'" onmouseout="this.style.color='#a855f7'">
            <i class="fas fa-key" style="margin-right:4px;font-size:.7rem;"></i>ลืมรหัสผ่าน?
          </a>
        </div>

        <button type="submit" class="btn-primary" id="loginBtn">
          <i class="fas fa-sign-in-alt" style="margin-right:8px;"></i>เข้าสู่ระบบ
        </button>
      </form>

      <div style="height:1px;background:linear-gradient(90deg,transparent,rgba(168,85,247,.18),transparent);margin:20px 0;"></div>

      <p style="text-align:center;font-size:.82rem;color:#6b7280;">
        ยังไม่มีบัญชี?
        <a href="register.php" style="color:#a855f7;font-weight:600;text-decoration:none;" onmouseover="this.style.color='#c084fc'" onmouseout="this.style.color='#a855f7'">สมัครสมาชิก</a>
      </p>
    </div>
  </div>

</div>

<script>
/* Toggle password visibility */
function togglePass(){
  const inp=document.getElementById('passwordInput');
  const icon=document.getElementById('eyeIcon');
  if(inp.type==='password'){inp.type='text';icon.className='fas fa-eye-slash';}
  else{inp.type='password';icon.className='fas fa-eye';}
}

/* Double-submit prevention */
document.querySelector('form').addEventListener('submit', function(){
  const btn = document.getElementById('loginBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i>กำลังเข้าสู่ระบบ...';
  setTimeout(()=>{ btn.disabled=false; btn.innerHTML='<i class="fas fa-sign-in-alt" style="margin-right:8px;"></i>เข้าสู่ระบบ'; }, 5000);
});

/* BG particles */
(function(){
  const c=document.getElementById('bg-canvas'),ctx=c.getContext('2d');
  let W,H,pts=[],mx=null,my=null;
  const COLS=['rgba(168,85,247,','rgba(236,72,153,','rgba(56,189,248,'];
  function resize(){W=c.width=innerWidth;H=c.height=innerHeight;}
  window.addEventListener('resize',()=>{resize();init();});
  window.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;});
  resize();
  function init(){pts=[];const n=Math.min(Math.floor(W*H/12000),50);for(let i=0;i<n;i++)pts.push({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.2,vy:(Math.random()-.5)*.2,r:Math.random()*1.3+.3,c:COLS[Math.floor(Math.random()*COLS.length)],a:Math.random()*.25+.08});}
  function draw(){ctx.clearRect(0,0,W,H);for(let i=0;i<pts.length;i++)for(let j=i+1;j<pts.length;j++){const dx=pts[i].x-pts[j].x,dy=pts[i].y-pts[j].y,d2=dx*dx+dy*dy,md=(W/6)*(H/6);if(d2<md){ctx.strokeStyle=`rgba(168,85,247,${(1-d2/md)*.08})`;ctx.lineWidth=.5;ctx.beginPath();ctx.moveTo(pts[i].x,pts[i].y);ctx.lineTo(pts[j].x,pts[j].y);ctx.stroke();}}
  pts.forEach(p=>{if(mx!==null){const dx=mx-p.x,dy=my-p.y,d=Math.sqrt(dx*dx+dy*dy);if(d<70){const f=(70-d)/70;p.x-=dx/d*f*2.5;p.y-=dy/d*f*2.5;}}p.x+=p.vx;p.y+=p.vy;if(p.x<0||p.x>W)p.vx*=-1;if(p.y<0||p.y>H)p.vy*=-1;ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.c+p.a+')';ctx.fill();});requestAnimationFrame(draw);}
  init();draw();
})();
</script>
</body>
</html>