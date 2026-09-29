<?php
session_start();
require 'config.php';

// ============================================================
//  SECURITY HEADERS
// ============================================================
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ============================================================
//  CONFIG
// ============================================================
define('REG_RATE_LIMIT',  3);    // สมัครได้สูงสุด 3 ครั้ง / 10 นาที ต่อ IP
define('REG_RATE_WINDOW', 600);  // หน้าต่างเวลา 10 นาที (วินาที)
define('MIN_USERNAME',    3);    // ชื่อผู้ใช้ขั้นต่ำ
define('MAX_USERNAME',    30);   // ชื่อผู้ใช้สูงสุด
define('MIN_PASSWORD',    8);    // รหัสผ่านขั้นต่ำ (เพิ่มจาก 6 → 8)

// ============================================================
//  HELPER: ดึง IP จริง
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
//  RATE LIMIT สำหรับการสมัคร (ป้องกัน bot สมัครซ้ำ)
//  ใช้ DB ตาราง login_attempts (ตารางเดิมที่สร้างไว้แล้ว)
// ============================================================
function checkRegRateLimit(PDO $pdo, string $ip): bool {
    $since = date('Y-m-d H:i:s', time() - REG_RATE_WINDOW);
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_attempts
        WHERE ip = ? AND identifier LIKE 'REG_%' AND attempted_at > ?
    ");
    $stmt->execute([$ip, $since]);
    return (int)$stmt->fetchColumn() < REG_RATE_LIMIT;
}

function recordRegAttempt(PDO $pdo, string $ip, string $email): void {
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (identifier, ip, attempted_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute(['REG_' . $email, $ip]);
}

// ============================================================
//  CSRF TOKEN
// ============================================================
function generateCSRF(): string {
    if (empty($_SESSION['csrf_reg'])) {
        $_SESSION['csrf_reg'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_reg'];
}

function verifyCSRF(string $token): bool {
    return isset($_SESSION['csrf_reg'])
        && hash_equals($_SESSION['csrf_reg'], $token);
}

// ============================================================
//  VALIDATE USERNAME (ป้องกัน XSS / SQL injection / emoji spam)
//  กฎ: ตัวแรกต้องเป็นพิมพ์ใหญ่, ที่เหลือ a-z A-Z 0-9 _ -
// ============================================================
function validateUsername(string $u): string|false {
    $len = mb_strlen($u);
    if ($len < MIN_USERNAME || $len > MAX_USERNAME) return false;
    // ตัวแรกต้องเป็น A-Z (พิมพ์ใหญ่) เท่านั้น
    if (!preg_match('/^[A-Z]/', $u)) return false;
    // ที่เหลืออนุญาต a-z A-Z 0-9 _ -
    if (!preg_match('/^[A-Za-z0-9_\-]+$/', $u)) return false;
    return $u;
}

// ============================================================
//  REDIRECT ถ้า login แล้ว
// ============================================================
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error   = "";
$success = "";
$ip      = getClientIP();

// ============================================================
//  POST — ประมวลผล Register
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) ตรวจ CSRF
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = "คำขอไม่ถูกต้อง (CSRF) กรุณาโหลดหน้าใหม่";
        unset($_SESSION['csrf_reg']);

    // 2) Rate Limit ป้องกัน bot สมัครสแปม
    } elseif (!checkRegRateLimit($pdo, $ip)) {
        $mins = ceil(REG_RATE_WINDOW / 60);
        $error = "สมัครบัญชีเร็วเกินไป กรุณารอ {$mins} นาทีแล้วลองใหม่";

    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm']  ?? '');

        // 3) ตรวจ Honeypot field (ถ้า bot กรอก field นี้ = ให้ผ่านเงียบๆ แต่ไม่บันทึก)
        $honeypot = $_POST['website'] ?? '';
        if (!empty($honeypot)) {
            // แกล้งทำเป็นสำเร็จ แต่ไม่ทำอะไร
            $success = "สมัครสมาชิกสำเร็จ";
            header("refresh:2;url=login.php");
            goto render;
        }

        // 4) ตรวจ input ว่าง
        if (!$username || !$email || !$password || !$confirm) {
            $error = "กรุณากรอกข้อมูลให้ครบ";

        // 5) ตรวจรูปแบบ username
        } elseif (!validateUsername($username)) {
            $error = "ถ้าใช้ภาษาอังกฤษ ตัวแรกต้องพิมพ์ใหญ่ เช่น Zumo, มานี, Admin99 (" . MIN_USERNAME . "-" . MAX_USERNAME . " ตัว)";

        // 6) ตรวจรูปแบบ email
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $error = "รูปแบบอีเมลไม่ถูกต้อง";

        // 7) ตรวจความยาวรหัสผ่าน
        } elseif (strlen($password) < MIN_PASSWORD) {
            $error = "รหัสผ่านต้องอย่างน้อย " . MIN_PASSWORD . " ตัวอักษร";

        // 8) ตรวจรหัสผ่านซ้ำกับชื่อผู้ใช้/อีเมล (ป้องกันรหัสอ่อน)
        } elseif (stripos($password, $username) !== false) {
            $error = "รหัสผ่านต้องไม่มีชื่อผู้ใช้อยู่ในนั้น";

        // 9) ตรวจรหัสผ่านตรงกัน
        } elseif ($password !== $confirm) {
            $error = "รหัสผ่านไม่ตรงกัน";

        } else {
            // 10) บันทึก attempt ก่อนตรวจ DB (ป้องกัน timing attack)
            recordRegAttempt($pdo, $ip, $email);

            // 11) ตรวจ email ซ้ำ
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "อีเมลนี้ถูกใช้งานแล้ว";

            } else {
                // 12) ตรวจ username ซ้ำ
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    $error = "ชื่อผู้ใช้นี้ถูกใช้งานแล้ว";

                } else {
                    // ✅ บันทึก user ใหม่
                    // PASSWORD_BCRYPT cost=12 (แรงกว่า default cost=10)
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password, role)
                        VALUES (?, ?, ?, 'user')
                    ");
                    $stmt->execute([$username, $email, $hash]);

                    // ล้าง CSRF token เก่า
                    unset($_SESSION['csrf_reg']);

                    $success = "สมัครสมาชิกสำเร็จ! กำลังพาไปหน้าเข้าสู่ระบบ...";
                    header("refresh:2;url=login.php");
                }
            }
        }
    }
}

render:
$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>สมัครสมาชิก - Portfolio</title>
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
    .d1{animation-delay:.05s;}.d2{animation-delay:.12s;}.d3{animation-delay:.2s;}
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
    .btn-primary:active{transform:translateY(0);}
    .btn-primary:disabled{opacity:.6;cursor:not-allowed;transform:none;}
    .error-box{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:10px 14px;color:#fca5a5;font-size:.82rem;display:flex;align-items:center;gap:8px;}
    .success-box{background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);border-radius:10px;padding:10px 14px;color:#6ee7b7;font-size:.82rem;display:flex;align-items:center;gap:8px;}
    #strengthBar{height:4px;border-radius:4px;transition:all .3s ease;width:0%;}
    @keyframes sblink{0%,100%{opacity:1;}50%{opacity:.2;}}
    .sdot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#34d399;animation:sblink 2s ease-in-out infinite;}
    /* Honeypot — ซ่อนจาก user จริง */
    .hp-field{position:absolute;left:-9999px;top:-9999px;opacity:0;pointer-events:none;tab-index:-1;}
  </style>
</head>
<body class="flex items-center justify-center min-h-screen px-4 py-10">
<canvas id="bg-canvas"></canvas>

<div class="w-full max-w-sm z1">

  <div class="text-center mb-8 fu d1">
    <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#db2777);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 0 24px rgba(168,85,247,.5);font-size:1.6rem;">✨</div>
    <h1 style="font-family:'Kanit',sans-serif;font-weight:800;font-size:1.8rem;background:linear-gradient(135deg,#e879f9,#f472b6,#38bdf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">สมัครสมาชิก</h1>
    <p style="font-family:'Share Tech Mono',monospace;font-size:.65rem;color:rgba(168,85,247,.5);letter-spacing:.14em;margin-top:4px;">// REGISTER.PHP</p>
  </div>

  <div class="panel fu d2">
    <div style="height:2px;background:linear-gradient(90deg,#7c3aed,#ec4899,#38bdf8,#ec4899,#7c3aed);background-size:200% 100%;animation:shimmerBar 3s linear infinite;"></div>
    <div style="padding:28px 26px;position:relative;z-index:3;">

      <div style="display:flex;align-items:center;gap:8px;margin-bottom:22px;">
        <span class="sdot"></span>
        <span style="font-family:'Share Tech Mono',monospace;font-size:.68rem;color:#34d399;letter-spacing:.08em;">CREATE ACCOUNT</span>
      </div>

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

      <form method="POST" style="display:flex;flex-direction:column;gap:14px;" id="regForm" autocomplete="off">

        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <!-- Honeypot (ซ่อน — bot จะกรอก, คนจริงไม่เห็น) -->
        <div class="hp-field" aria-hidden="true">
          <label for="website">Leave this empty</label>
          <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
        </div>

        <!-- Username -->
        <div>
          <div style="position:relative;">
            <i class="fas fa-user" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.8rem;z-index:1;"></i>
            <input type="text" name="username" id="usernameInput" class="form-input"
                   placeholder="เช่น Zumo, ซูโม่" required
                   maxlength="<?= MAX_USERNAME ?>"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   oninput="checkUsername(this)">
          </div>
          <p id="usernameTip" style="font-size:.68rem;color:#6b7280;margin-top:4px;font-family:'Share Tech Mono',monospace;padding-left:4px;">รองรับภาษาไทย · ถ้าใช้ภาษาอังกฤษ ตัวแรกต้องเป็นพิมพ์ใหญ่</p>
        </div>

        <!-- Email -->
        <div style="position:relative;">
          <i class="fas fa-at" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.82rem;z-index:1;"></i>
          <input type="email" name="email" id="emailInput" class="form-input"
                 placeholder="อีเมล" required maxlength="254"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 oninput="checkEmail(this)">
        </div>

        <!-- Password + strength -->
        <div>
          <div style="position:relative;">
            <i class="fas fa-lock" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.8rem;z-index:1;"></i>
            <input type="password" name="password" id="passInput" class="form-input"
                   placeholder="รหัสผ่าน (อย่างน้อย <?= MIN_PASSWORD ?> ตัว)" required
                   maxlength="128"
                   oninput="checkStrength(this.value);checkMatch()">
            <button type="button" onclick="togglePass('passInput','eye1')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;">
              <i class="fas fa-eye" id="eye1" style="font-size:.8rem;"></i>
            </button>
          </div>
          <div style="margin-top:6px;background:rgba(255,255,255,.06);border-radius:4px;height:4px;">
            <div id="strengthBar"></div>
          </div>
          <p id="strengthText" style="font-size:.7rem;color:#6b7280;margin-top:4px;font-family:'Share Tech Mono',monospace;"></p>
        </div>

        <!-- Confirm password -->
        <div style="position:relative;">
          <i class="fas fa-lock" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.8rem;z-index:1;"></i>
          <input type="password" name="confirm" id="confirmInput" class="form-input"
                 placeholder="ยืนยันรหัสผ่าน" required maxlength="128"
                 oninput="checkMatch()">
          <button type="button" onclick="togglePass('confirmInput','eye2')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;">
            <i class="fas fa-eye" id="eye2" style="font-size:.8rem;"></i>
          </button>
        </div>
        <p id="matchText" style="font-size:.7rem;color:#6b7280;margin-top:-8px;font-family:'Share Tech Mono',monospace;"></p>

        <button type="submit" class="btn-primary" id="submitBtn" style="margin-top:4px;" disabled>
          <i class="fas fa-user-plus" style="margin-right:8px;"></i>สมัครสมาชิก
        </button>
      </form>

      <div style="height:1px;background:linear-gradient(90deg,transparent,rgba(168,85,247,.18),transparent);margin:20px 0;"></div>
      <p style="text-align:center;font-size:.82rem;color:#6b7280;">
        มีบัญชีแล้ว?
        <a href="login.php" style="color:#a855f7;font-weight:600;text-decoration:none;">เข้าสู่ระบบ</a>
      </p>
    </div>
  </div>
</div>

<script>
const MIN_PASS = <?= MIN_PASSWORD ?>;

/* Toggle password */
function togglePass(id,eyeId){
  const inp=document.getElementById(id),icon=document.getElementById(eyeId);
  inp.type = inp.type==='password' ? 'text' : 'password';
  icon.className = inp.type==='password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

/* Username validation — ถ้าขึ้นต้นอังกฤษ ตัวแรกต้องพิมพ์ใหญ่ */
function checkUsername(el){
  const v=el.value, tip=document.getElementById('usernameTip');
  if(!v){el.className='form-input';tip.textContent='รองรับภาษาไทย · ถ้าใช้ภาษาอังกฤษ ตัวแรกต้องเป็นพิมพ์ใหญ่';tip.style.color='#6b7280';updateSubmit();return;}
  const startsEng=/^[a-zA-Z]/.test(v);
  const firstUpper=/^[A-Z]/.test(v);
  const validChars=/^[A-Za-z0-9_\-\u0E00-\u0E7F]+$/.test(v);
  if(startsEng && !firstUpper){
    el.className='form-input error';tip.textContent='✗ ถ้าขึ้นต้นอังกฤษ ตัวแรกต้องพิมพ์ใหญ่ เช่น Zumo';tip.style.color='#f87171';
  } else if(!validChars){
    el.className='form-input error';tip.textContent='✗ ใช้ได้เฉพาะ a-z A-Z 0-9 _ - และภาษาไทย';tip.style.color='#f87171';
  } else if(v.length<3){
    el.className='form-input error';tip.textContent='✗ ต้องมีอย่างน้อย 3 ตัวอักษร';tip.style.color='#f87171';
  } else {
    el.className='form-input ok';tip.textContent='✓ ชื่อผู้ใช้ถูกต้อง';tip.style.color='#34d399';
  }
  updateSubmit();
}

/* Email validation */
function checkEmail(el){
  const ok=/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value);
  el.className = el.value ? (ok ? 'form-input ok' : 'form-input error') : 'form-input';
  updateSubmit();
}

/* Password strength */
function checkStrength(v){
  const bar=document.getElementById('strengthBar'),txt=document.getElementById('strengthText');
  if(!v){bar.style.width='0%';txt.textContent='';updateSubmit();return;}
  let s=0;
  if(v.length>=MIN_PASS)s++;if(v.length>=12)s++;
  if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const levels=[
    {w:'20%',c:'#ef4444',t:'WEAK — ไม่ปลอดภัย'},
    {w:'40%',c:'#f97316',t:'FAIR — พอใช้ได้'},
    {w:'60%',c:'#eab308',t:'GOOD — ดีพอสมควร'},
    {w:'80%',c:'#22c55e',t:'STRONG — แข็งแกร่ง'},
    {w:'100%',c:'#10b981',t:'VERY STRONG — ยอดเยี่ยม'}
  ];
  const l=levels[Math.min(s-1,4)]||levels[0];
  bar.style.width=l.w;bar.style.background=l.c;
  txt.textContent=l.t;txt.style.color=l.c;
  updateSubmit();
}

/* Match check */
function checkMatch(){
  const p=document.getElementById('passInput').value;
  const c=document.getElementById('confirmInput');
  const mt=document.getElementById('matchText');
  if(!c.value){mt.textContent='';c.className='form-input';updateSubmit();return;}
  if(p===c.value){mt.textContent='✓ รหัสผ่านตรงกัน';mt.style.color='#34d399';c.className='form-input ok';}
  else{mt.textContent='✗ รหัสผ่านไม่ตรงกัน';mt.style.color='#f87171';c.className='form-input error';}
  updateSubmit();
}

/* เปิดปุ่มสมัครเมื่อทุก field ผ่าน */
function updateSubmit(){
  const u=document.getElementById('usernameInput');
  const e=document.getElementById('emailInput');
  const p=document.getElementById('passInput');
  const c=document.getElementById('confirmInput');
  const btn=document.getElementById('submitBtn');
  const ok =
    u.className.includes('ok') &&
    e.className.includes('ok') &&
    p.value.length >= MIN_PASS &&
    p.value === c.value;
  btn.disabled = !ok;
}

/* ป้องกัน double submit */
document.getElementById('regForm').addEventListener('submit', function(){
  const btn=document.getElementById('submitBtn');
  btn.disabled=true;
  btn.innerHTML='<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i>กำลังสมัคร...';
});

/* BG */
(function(){const c=document.getElementById('bg-canvas'),ctx=c.getContext('2d');let W,H,pts=[],mx=null,my=null;const COLS=['rgba(168,85,247,','rgba(236,72,153,','rgba(56,189,248,'];function resize(){W=c.width=innerWidth;H=c.height=innerHeight;}window.addEventListener('resize',()=>{resize();init();});window.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;});resize();function init(){pts=[];const n=Math.min(Math.floor(W*H/12000),50);for(let i=0;i<n;i++)pts.push({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.2,vy:(Math.random()-.5)*.2,r:Math.random()*1.3+.3,c:COLS[Math.floor(Math.random()*COLS.length)],a:Math.random()*.25+.08});}function draw(){ctx.clearRect(0,0,W,H);for(let i=0;i<pts.length;i++)for(let j=i+1;j<pts.length;j++){const dx=pts[i].x-pts[j].x,dy=pts[i].y-pts[j].y,d2=dx*dx+dy*dy,md=(W/6)*(H/6);if(d2<md){ctx.strokeStyle=`rgba(168,85,247,${(1-d2/md)*.08})`;ctx.lineWidth=.5;ctx.beginPath();ctx.moveTo(pts[i].x,pts[i].y);ctx.lineTo(pts[j].x,pts[j].y);ctx.stroke();}}pts.forEach(p=>{if(mx!==null){const dx=mx-p.x,dy=my-p.y,d=Math.sqrt(dx*dx+dy*dy);if(d<70){const f=(70-d)/70;p.x-=dx/d*f*2.5;p.y-=dy/d*f*2.5;}}p.x+=p.vx;p.y+=p.vy;if(p.x<0||p.x>W)p.vx*=-1;if(p.y<0||p.y>H)p.vy*=-1;ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.c+p.a+')';ctx.fill();});requestAnimationFrame(draw);}init();draw();})();
</script>
</body>
</html>