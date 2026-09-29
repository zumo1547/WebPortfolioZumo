<?php
/* ╔══════════════════════════════════════════════════════╗
   ║  SECURITY HEADERS — ส่งก่อน output ทุกอย่าง         ║
   ╚══════════════════════════════════════════════════════╝ */
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

session_start();
include 'config.php';

/* ── FIX 1: Auth Guard ── */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* ── FIX 2: Session Fingerprint — ป้องกัน Session Hijacking ── */
if (empty($_SESSION['_fp'])) {
    $_SESSION['_fp'] = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? ''));
} elseif ($_SESSION['_fp'] !== hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? ''))) {
    session_destroy();
    header('Location: login.php');
    exit;
}

/* ── FIX 3: CSRF Token ── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function verifyCSRF(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ── FIX 4: Rate Limit — ป้องกัน brute-force current_password ──
   ใช้ session นับจำนวนครั้งที่ submit ผิด
   ล็อค 10 นาที หลังผิด 5 ครั้ง                               */
define('PASS_MAX_FAIL',    5);
define('PASS_LOCKOUT_SEC', 600);

function isPassRateLimited(): bool {
    $now = time();
    if (!isset($_SESSION['_pass_fails'])) return false;
    if ($_SESSION['_pass_fails'] >= PASS_MAX_FAIL) {
        $since = $now - ($_SESSION['_pass_fail_ts'] ?? 0);
        if ($since < PASS_LOCKOUT_SEC) return true;
        // หมดเวลา lockout — รีเซ็ต
        unset($_SESSION['_pass_fails'], $_SESSION['_pass_fail_ts']);
    }
    return false;
}
function recordPassFail(): void {
    $_SESSION['_pass_fails']   = ($_SESSION['_pass_fails'] ?? 0) + 1;
    $_SESSION['_pass_fail_ts'] = time();
}
function clearPassFail(): void {
    unset($_SESSION['_pass_fails'], $_SESSION['_pass_fail_ts']);
}
function passLockoutLeft(): int {
    $left = PASS_LOCKOUT_SEC - (time() - ($_SESSION['_pass_fail_ts'] ?? 0));
    return max(0, (int) $left);
}

$user_id  = (int) $_SESSION["user_id"];
$user_msg = "";
$pass_msg = "";
$user_ok  = false;
$pass_ok  = false;

/* ══════════════════════════════════════════════════════
   1. เปลี่ยนชื่อผู้ใช้
══════════════════════════════════════════════════════ */
if (isset($_POST['update_username'])) {

    /* FIX 3: ตรวจ CSRF */
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $user_msg = "คำขอไม่ถูกต้อง (CSRF) กรุณาโหลดหน้าใหม่";
    } else {
        $new_username = trim(filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

        /* FIX 5: Server-side validation — ความยาว + รูปแบบ */
        if (empty($new_username)) {
            $user_msg = "กรุณากรอกชื่อผู้ใช้ใหม่";
        } elseif (mb_strlen($new_username) > 50) {
            $user_msg = "ชื่อผู้ใช้ยาวเกินไป (สูงสุด 50 ตัวอักษร)";
        } elseif (!preg_match('/^[a-zA-Z0-9ก-๙_\-\.]{3,50}$/u', $new_username)) {
            $user_msg = "ชื่อผู้ใช้ใช้ได้เฉพาะ a-z, 0-9, ไทย, _ - . และต้องมีอย่างน้อย 3 ตัวอักษร";
        } elseif ($new_username === $_SESSION["username"]) {
            $user_msg = "นี่คือชื่อผู้ใช้ปัจจุบันของคุณอยู่แล้ว";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
                $stmt->execute(['username' => $new_username, 'id' => $user_id]);
                if ($stmt->rowCount() > 0) {
                    $user_msg = "ชื่อผู้ใช้นี้ถูกใช้แล้ว กรุณาเลือกชื่ออื่น";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = :username WHERE id = :id");
                    if ($stmt->execute(['username' => $new_username, 'id' => $user_id])) {
                        $_SESSION["username"] = $new_username;
                        $user_msg = "อัปเดตชื่อผู้ใช้สำเร็จ";
                        $user_ok  = true;
                    } else {
                        $user_msg = "เกิดข้อผิดพลาดในการอัปเดตชื่อ";
                    }
                }
            } catch (Exception $e) {
                error_log('[Settings Username] ' . $e->getMessage());
                $user_msg = "เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่";
            }
        }
    }
}

/* ══════════════════════════════════════════════════════
   2. เปลี่ยนรหัสผ่าน
══════════════════════════════════════════════════════ */
if (isset($_POST['update_password'])) {

    /* FIX 3: ตรวจ CSRF */
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $pass_msg = "คำขอไม่ถูกต้อง (CSRF) กรุณาโหลดหน้าใหม่";

    /* FIX 4: Rate limit */
    } elseif (isPassRateLimited()) {
        $mins     = ceil(passLockoutLeft() / 60);
        $pass_msg = "ลองผิดบ่อยเกินไป กรุณารอ {$mins} นาทีแล้วลองใหม่";

    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $pass_msg = "กรุณากรอกข้อมูลรหัสผ่านให้ครบถ้วน";

        /* FIX 6: เพิ่ม min length เป็น 8 + server-side max */
        } elseif (strlen($new_password) < 8) {
            $pass_msg = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
        } elseif (strlen($new_password) > 128) {
            $pass_msg = "รหัสผ่านยาวเกินไป";
        } elseif ($new_password !== $confirm_password) {
            $pass_msg = "รหัสผ่านใหม่และการยืนยันไม่ตรงกัน";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
                $stmt->execute(['id' => $user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($current_password, $user['password'])) {
                    clearPassFail(); // รีเซ็ต fail counter
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt   = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                    if ($stmt->execute(['password' => $hashed, 'id' => $user_id])) {
                        $pass_msg = "อัปเดตรหัสผ่านสำเร็จ";
                        $pass_ok  = true;
                        /* สร้าง CSRF token ใหม่หลัง action สำเร็จ */
                        unset($_SESSION['csrf_token']);
                        $csrf = $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $pass_msg = "เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน";
                    }
                } else {
                    /* FIX 4: บันทึก fail */
                    recordPassFail();
                    $remaining = PASS_MAX_FAIL - ($_SESSION['_pass_fails'] ?? 0);
                    $pass_msg  = $remaining > 0
                        ? "รหัสผ่านปัจจุบันไม่ถูกต้อง (เหลืออีก {$remaining} ครั้ง)"
                        : "รหัสผ่านปัจจุบันไม่ถูกต้อง บัญชีถูกล็อคชั่วคราว " . (PASS_LOCKOUT_SEC/60) . " นาที";
                }
            } catch (Exception $e) {
                error_log('[Settings Password] ' . $e->getMessage());
                $pass_msg = "เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่";
            }
        }
    }
}

$initial = strtoupper(mb_substr($_SESSION['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings — Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/Icon portfolio.png">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Share+Tech+Mono&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#07040f;
      --surface:rgba(255,255,255,.035);
      --border:rgba(168,85,247,.15);
      --border-h:rgba(168,85,247,.4);
      --purple:#a855f7;
      --pink:#ec4899;
      --blue:#38bdf8;
      --green:#34d399;
      --red:#f87171;
      --yellow:#fbbf24;
      --text:#e2e8f0;
      --muted:#64748b;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    html{scroll-behavior:smooth;}

    body{
      background:var(--bg);
      color:var(--text);
      font-family:'Kanit',sans-serif;
      min-height:100vh;
      overflow-x:hidden;
    }

    /* Grid BG */
    body::before{
      content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
      background-image:
        linear-gradient(rgba(168,85,247,.028) 1px,transparent 1px),
        linear-gradient(90deg,rgba(168,85,247,.028) 1px,transparent 1px);
      background-size:48px 48px;
    }

    /* Ambient glow blobs */
    .blob{position:fixed;border-radius:50%;filter:blur(120px);pointer-events:none;z-index:0;}
    .blob-1{width:600px;height:600px;background:rgba(124,58,237,.07);top:-200px;left:-150px;}
    .blob-2{width:500px;height:500px;background:rgba(219,39,119,.06);bottom:-100px;right:-100px;}

    /* Shimmer bar */
    @keyframes shimmer{0%{background-position:0% 0;}100%{background-position:200% 0;}}
    .shimmer-bar{height:2px;background:linear-gradient(90deg,#7c3aed,#ec4899,#38bdf8,#ec4899,#7c3aed);background-size:200% 100%;animation:shimmer 3s linear infinite;}

    /* Wrapper */
    .page-wrap{position:relative;z-index:1;max-width:860px;margin:0 auto;padding:40px 20px 60px;}

    /* ── PAGE HEADER ── */
    .page-header{margin-bottom:36px;}
    .back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);font-size:.78rem;font-family:'Share Tech Mono',monospace;text-decoration:none;letter-spacing:.06em;transition:color .2s;margin-bottom:20px;}
    .back-link:hover{color:var(--purple);}
    .header-top{display:flex;align-items:center;gap:16px;}
    .user-orb{
      width:58px;height:58px;border-radius:50%;flex-shrink:0;
      background:linear-gradient(135deg,#7c3aed,#db2777);
      display:flex;align-items:center;justify-content:center;
      font-size:1.3rem;font-weight:800;
      box-shadow:0 0 0 3px rgba(168,85,247,.2),0 0 30px rgba(124,58,237,.35);
    }
    .header-text h1{font-size:1.6rem;font-weight:800;line-height:1.1;}
    .header-text h1 span{background:linear-gradient(135deg,#e879f9,#f472b6,#38bdf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
    .header-text p{font-family:'Share Tech Mono',monospace;font-size:.62rem;color:var(--muted);letter-spacing:.1em;margin-top:4px;}

    /* ── TABS ── */
    .tabs{display:flex;gap:4px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;padding:4px;margin-bottom:24px;}
    .tab-btn{
      flex:1;display:flex;align-items:center;justify-content:center;gap:8px;
      padding:10px 16px;border-radius:9px;border:none;
      background:transparent;color:var(--muted);
      font-family:'Kanit',sans-serif;font-size:.85rem;font-weight:600;
      cursor:pointer;transition:all .25s;
    }
    .tab-btn.active{background:rgba(168,85,247,.15);color:var(--purple);box-shadow:inset 0 0 0 1px rgba(168,85,247,.25);}
    .tab-btn:not(.active):hover{background:rgba(255,255,255,.04);color:var(--text);}
    .tab-btn i{font-size:.8rem;}

    /* ── PANEL ── */
    .panel{
      background:rgba(10,7,22,.85);
      border:1px solid var(--border);
      border-radius:20px;
      overflow:hidden;
      display:none;
    }
    .panel.active{display:block;}

    /* Panel top accent */
    .panel-accent{height:1px;background:linear-gradient(90deg,transparent,rgba(168,85,247,.35),rgba(236,72,153,.2),transparent);}

    .panel-body{padding:32px;}
    @media(max-width:600px){.panel-body{padding:20px;}}

    /* Section title */
    .section-title{
      display:flex;align-items:center;gap:10px;
      font-size:1rem;font-weight:700;margin-bottom:24px;
    }
    .section-icon{
      width:36px;height:36px;border-radius:10px;
      display:flex;align-items:center;justify-content:center;
      font-size:.82rem;flex-shrink:0;
    }
    .section-desc{font-size:.75rem;color:var(--muted);font-family:'Share Tech Mono',monospace;margin-top:2px;}

    /* ── FORM ELEMENTS ── */
    .form-group{margin-bottom:20px;}
    .form-label{
      display:block;font-size:.78rem;font-weight:600;
      color:#94a3b8;margin-bottom:8px;letter-spacing:.03em;
    }
    .form-label span{color:var(--muted);font-family:'Share Tech Mono',monospace;font-size:.65rem;font-weight:400;margin-left:6px;}
    .input-wrap{position:relative;}
    .input-icon{
      position:absolute;left:14px;top:50%;transform:translateY(-50%);
      color:var(--muted);font-size:.8rem;pointer-events:none;
      transition:color .2s;
    }
    .form-input{
      width:100%;
      background:rgba(255,255,255,.04);
      border:1px solid rgba(168,85,247,.18);
      border-radius:11px;
      padding:12px 14px 12px 40px;
      color:var(--text);
      font-family:'Kanit',sans-serif;
      font-size:.88rem;
      outline:none;
      transition:border-color .2s,box-shadow .2s,background .2s;
    }
    .form-input::placeholder{color:#374151;}
    .form-input:focus{
      border-color:rgba(168,85,247,.5);
      background:rgba(168,85,247,.04);
      box-shadow:0 0 0 3px rgba(168,85,247,.1);
    }
    .form-input:focus ~ .input-icon,
    .input-wrap:focus-within .input-icon{color:var(--purple);}

    /* Password toggle btn */
    .eye-btn{
      position:absolute;right:12px;top:50%;transform:translateY(-50%);
      background:none;border:none;color:var(--muted);cursor:pointer;
      padding:4px 6px;font-size:.8rem;transition:color .2s;
    }
    .eye-btn:hover{color:var(--purple);}

    /* Strength bar */
    .strength-wrap{margin-top:8px;display:none;}
    .strength-wrap.show{display:block;}
    .strength-track{height:3px;background:rgba(255,255,255,.06);border-radius:4px;overflow:hidden;}
    .strength-fill{height:100%;border-radius:4px;transition:width .3s,background .3s;}
    .strength-label{font-size:.62rem;color:var(--muted);font-family:'Share Tech Mono',monospace;margin-top:4px;}

    /* ── SUBMIT BUTTON ── */
    .btn-submit{
      width:100%;padding:13px;border:none;border-radius:12px;
      background:linear-gradient(135deg,#7c3aed,#db2777);
      color:#fff;font-weight:700;font-size:.9rem;
      font-family:'Kanit',sans-serif;cursor:pointer;
      transition:transform .2s,box-shadow .2s,opacity .2s;
      box-shadow:0 4px 20px rgba(124,58,237,.3);
      display:flex;align-items:center;justify-content:center;gap:8px;
      margin-top:8px;
    }
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(124,58,237,.45);}
    .btn-submit:active{transform:translateY(0);opacity:.9;}

    /* ── ALERT ── */
    .alert{
      display:flex;align-items:center;gap:10px;
      border-radius:11px;padding:12px 16px;
      font-size:.82rem;margin-bottom:22px;
      animation:fadeUp .35s cubic-bezier(.16,1,.3,1);
    }
    .alert-ok{background:rgba(52,211,153,.08);border:1px solid rgba(52,211,153,.25);color:#6ee7b7;}
    .alert-err{background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.25);color:#fca5a5;}
    .alert i{flex-shrink:0;font-size:.9rem;}

    /* ── DIVIDER ── */
    .divider{height:1px;background:linear-gradient(90deg,transparent,rgba(168,85,247,.12),transparent);margin:28px 0;}

    /* ── INFO ROW (current username display) ── */
    .info-row{
      display:flex;align-items:center;gap:10px;
      background:rgba(168,85,247,.05);border:1px solid rgba(168,85,247,.1);
      border-radius:11px;padding:12px 16px;margin-bottom:22px;
    }
    .info-dot{width:7px;height:7px;border-radius:50%;background:var(--purple);flex-shrink:0;
      box-shadow:0 0 8px rgba(168,85,247,.6);}
    .info-label{font-family:'Share Tech Mono',monospace;font-size:.65rem;color:var(--muted);letter-spacing:.06em;}
    .info-val{font-weight:700;font-size:.9rem;color:var(--text);}

    /* ── REQUIREMENT LIST ── */
    .req-list{margin-top:10px;display:flex;flex-direction:column;gap:5px;}
    .req-item{display:flex;align-items:center;gap:7px;font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;transition:color .2s;}
    .req-item i{font-size:.6rem;width:14px;text-align:center;transition:color .2s;}
    .req-item.pass{color:#6ee7b7;}
    .req-item.pass i{color:var(--green);}

    /* ── ANIMATIONS ── */
    @keyframes fadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
    .fu{opacity:0;animation:fadeUp .5s cubic-bezier(.16,1,.3,1) forwards;}
    .d1{animation-delay:.06s;}.d2{animation-delay:.12s;}.d3{animation-delay:.18s;}

    @keyframes pulse-ring{0%{box-shadow:0 0 0 0 rgba(168,85,247,.4);}70%{box-shadow:0 0 0 8px rgba(168,85,247,0);}100%{box-shadow:0 0 0 0 rgba(168,85,247,0);}}
    .pulse{animation:pulse-ring 2s ease-out;}
  </style>
</head>
<body>

  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>

  <?php include 'navbar.php'; ?>

  <div class="page-wrap">

    <!-- ── PAGE HEADER ── -->
    <div class="page-header fu">
      <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> BACK TO PORTFOLIO
      </a>
      <div class="header-top">
        <div class="user-orb"><?= htmlspecialchars($initial) ?></div>
        <div class="header-text">
          <h1>User <span>Settings</span></h1>
          <p>// <?= htmlspecialchars($_SESSION['username']) ?> · ACCOUNT PREFERENCES</p>
        </div>
      </div>
    </div>

    <!-- ── TABS ── -->
    <div class="tabs fu d1">
      <button class="tab-btn active" onclick="switchTab('username', this)">
        <i class="fas fa-user-pen"></i> เปลี่ยนชื่อผู้ใช้
      </button>
      <button class="tab-btn" onclick="switchTab('password', this)">
        <i class="fas fa-shield-halved"></i> เปลี่ยนรหัสผ่าน
      </button>
    </div>

    <!-- ══════════════════════════════
         PANEL 1 — USERNAME
    ══════════════════════════════ -->
    <div class="panel active fu d2" id="tab-username">
      <div class="shimmer-bar"></div>
      <div class="panel-accent"></div>
      <div class="panel-body">

        <div class="section-title">
          <div class="section-icon" style="background:rgba(168,85,247,.12);color:var(--purple);">
            <i class="fas fa-user-pen"></i>
          </div>
          <div>
            <div>เปลี่ยนชื่อผู้ใช้</div>
            <div class="section-desc">CHANGE USERNAME</div>
          </div>
        </div>

        <!-- Current username info -->
        <div class="info-row">
          <div class="info-dot"></div>
          <div>
            <div class="info-label">ชื่อผู้ใช้ปัจจุบัน</div>
            <div class="info-val"><?= htmlspecialchars($_SESSION['username']) ?></div>
          </div>
        </div>

        <!-- Alert -->
        <?php if ($user_msg): ?>
        <div class="alert <?= $user_ok ? 'alert-ok' : 'alert-err' ?>">
          <i class="fas <?= $user_ok ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
          <span><?= htmlspecialchars($user_msg) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <div class="form-group">
            <label class="form-label" for="new_username">
              ชื่อผู้ใช้ใหม่
              <span>3-50 CHARS · a-z 0-9 ไทย _ - .</span>
            </label>
            <div class="input-wrap">
              <i class="fas fa-at input-icon"></i>
              <input type="text" name="new_username" id="new_username" required
                     class="form-input"
                     placeholder="กรอกชื่อผู้ใช้ใหม่"
                     value="<?= htmlspecialchars($_SESSION['username']) ?>"
                     minlength="3" maxlength="50" autocomplete="off">
            </div>
          </div>

          <button type="submit" name="update_username" class="btn-submit"
                  onclick="disableBtn(this)">
            <i class="fas fa-floppy-disk"></i> บันทึกชื่อผู้ใช้
          </button>
        </form>

      </div>
    </div>

    <!-- ══════════════════════════════
         PANEL 2 — PASSWORD
    ══════════════════════════════ -->
    <div class="panel fu d2" id="tab-password">
      <div class="shimmer-bar"></div>
      <div class="panel-accent"></div>
      <div class="panel-body">

        <div class="section-title">
          <div class="section-icon" style="background:rgba(236,72,153,.12);color:var(--pink);">
            <i class="fas fa-shield-halved"></i>
          </div>
          <div>
            <div>เปลี่ยนรหัสผ่าน</div>
            <div class="section-desc">CHANGE PASSWORD</div>
          </div>
        </div>

        <!-- Alert -->
        <?php if ($pass_msg): ?>
        <div class="alert <?= $pass_ok ? 'alert-ok' : 'alert-err' ?>">
          <i class="fas <?= $pass_ok ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
          <span><?= htmlspecialchars($pass_msg) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

          <!-- Current password -->
          <div class="form-group">
            <label class="form-label" for="current_password">รหัสผ่านปัจจุบัน</label>
            <div class="input-wrap">
              <i class="fas fa-lock input-icon"></i>
              <input type="password" name="current_password" id="current_password" required
                     class="form-input" placeholder="••••••••" maxlength="128">
              <button type="button" class="eye-btn" onclick="togglePass('current_password',this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="divider"></div>

          <!-- New password -->
          <div class="form-group">
            <label class="form-label" for="new_password">
              รหัสผ่านใหม่
              <span>MIN 8 CHARS</span>
            </label>
            <div class="input-wrap">
              <i class="fas fa-key input-icon"></i>
              <input type="password" name="new_password" id="new_password" required
                     class="form-input" placeholder="••••••••" maxlength="128"
                     oninput="checkStrength(this.value); checkReqs(this.value);">
              <button type="button" class="eye-btn" onclick="togglePass('new_password',this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <!-- Strength bar -->
            <div class="strength-wrap" id="strengthWrap">
              <div class="strength-track">
                <div class="strength-fill" id="strengthFill" style="width:0%;"></div>
              </div>
              <div class="strength-label" id="strengthLabel">—</div>
            </div>
            <!-- Requirements -->
            <div class="req-list">
              <div class="req-item" id="req-len"><i class="fas fa-circle"></i> อย่างน้อย 8 ตัวอักษร</div>
              <div class="req-item" id="req-num"><i class="fas fa-circle"></i> มีตัวเลขอย่างน้อย 1 ตัว</div>
              <div class="req-item" id="req-upper"><i class="fas fa-circle"></i> มีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว</div>
            </div>
          </div>

          <!-- Confirm password -->
          <div class="form-group">
            <label class="form-label" for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
            <div class="input-wrap">
              <i class="fas fa-check-double input-icon"></i>
              <input type="password" name="confirm_password" id="confirm_password" required
                     class="form-input" placeholder="••••••••" maxlength="128"
                     oninput="checkMatch()">
              <button type="button" class="eye-btn" onclick="togglePass('confirm_password',this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div id="matchHint" style="font-family:'Share Tech Mono',monospace;font-size:.62rem;margin-top:6px;display:none;"></div>
          </div>

          <button type="submit" name="update_password" class="btn-submit" style="background:linear-gradient(135deg,#db2777,#7c3aed);"
                  onclick="disableBtn(this)">
            <i class="fas fa-shield-halved"></i> อัปเดตรหัสผ่าน
          </button>
        </form>

      </div>
    </div>

  </div><!-- /page-wrap -->

  <footer style="position:relative;z-index:1;text-align:center;padding:20px;color:var(--muted);font-family:'Share Tech Mono',monospace;font-size:.6rem;letter-spacing:.1em;border-top:1px solid var(--border);">
    &copy; <?= date("Y") ?> &nbsp;|&nbsp; ZUMO DEV PORTFOLIO
  </footer>

<script>
/* ── Tab switching ── */
function switchTab(name, btn) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  btn.classList.add('active');
}

/* ── Toggle password visibility ── */
function togglePass(id, btn) {
  const inp  = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (inp.type === 'password') { inp.type = 'text';     icon.className = 'fas fa-eye-slash'; }
  else                         { inp.type = 'password'; icon.className = 'fas fa-eye'; }
}

/* ── Password strength ── */
function checkStrength(val) {
  const wrap  = document.getElementById('strengthWrap');
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  if (!val) { wrap.classList.remove('show'); return; }
  wrap.classList.add('show');
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    { w:'20%',  bg:'#f87171', txt:'อ่อนมาก' },
    { w:'40%',  bg:'#fbbf24', txt:'อ่อน' },
    { w:'60%',  bg:'#38bdf8', txt:'ปานกลาง' },
    { w:'80%',  bg:'#34d399', txt:'แข็งแกร่ง' },
    { w:'100%', bg:'#a855f7', txt:'แข็งแกร่งมาก ✓' },
  ];
  const lv = levels[Math.min(score - 1, 4)] || levels[0];
  fill.style.width      = lv.w;
  fill.style.background = lv.bg;
  label.textContent     = lv.txt;
  label.style.color     = lv.bg;
}

/* ── Req checklist ── */
function checkReqs(val) {
  setReq('req-len',   val.length >= 8);
  setReq('req-num',   /[0-9]/.test(val));
  setReq('req-upper', /[A-Z]/.test(val));
}
function setReq(id, ok) {
  const el   = document.getElementById(id);
  const icon = el.querySelector('i');
  if (ok) { el.classList.add('pass');    icon.className = 'fas fa-circle-check'; }
  else    { el.classList.remove('pass'); icon.className = 'fas fa-circle'; }
}

/* ── Password match hint ── */
function checkMatch() {
  const np = document.getElementById('new_password').value;
  const cp = document.getElementById('confirm_password').value;
  const hint = document.getElementById('matchHint');
  if (!cp) { hint.style.display = 'none'; return; }
  hint.style.display = 'block';
  if (np === cp) {
    hint.textContent = '✓ รหัสผ่านตรงกัน';
    hint.style.color = 'var(--green)';
  } else {
    hint.textContent = '✗ รหัสผ่านไม่ตรงกัน';
    hint.style.color = 'var(--red)';
  }
}

/* ── FIX: Double-submit prevention ── */
function disableBtn(btn) {
  setTimeout(function(){
    btn.disabled = true;
    btn.style.opacity = '.5';
    btn.style.cursor  = 'not-allowed';
  }, 0);
  return true;
}

/* ── Auto-switch to password tab if pass_msg exists ── */
<?php if ($pass_msg): ?>
(function(){
  const btn = document.querySelectorAll('.tab-btn')[1];
  switchTab('password', btn);
})();
<?php endif; ?>
</script>
</body>
</html>