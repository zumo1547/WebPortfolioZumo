<?php
/* ╔═══════════════════════════════════════════════════════════╗
   ║  SECURITY HEADERS — ส่งก่อน output ทุกอย่าง              ║
   ╚═══════════════════════════════════════════════════════════╝ */
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

session_start();

/* ── CSRF Token ── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

/* ── Auth Guard: ต้อง login + เป็น admin เท่านั้น ── */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header('Location: index.php');
    exit;
}

/* ── Session Fingerprint — ป้องกัน Session Fixation / Hijacking ── */
if (empty($_SESSION['_fp'])) {
    $_SESSION['_fp'] = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? ''));
} elseif ($_SESSION['_fp'] !== hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? ''))) {
    session_destroy();
    header('Location: index.php');
    exit;
}

include 'config.php';

$notification = $_SESSION['notification'] ?? '';
unset($_SESSION['notification']);

/* ── FIX BUG 1: Chart 30 วัน → fallback ข้อมูลล่าสุดที่มีอยู่จริง ── */
try {
    $stmt = $pdo->prepare("
        SELECT DATE(login_time) AS date, COUNT(*) AS cnt
        FROM user_logs
        WHERE login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(login_time)
        ORDER BY date ASC
    ");
    $stmt->execute();
    $chartRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $chartLabel = '30 วันล่าสุด';
    if (empty($chartRows)) {
        $stmt = $pdo->prepare("
            SELECT DATE(login_time) AS date, COUNT(*) AS cnt
            FROM user_logs
            GROUP BY DATE(login_time)
            ORDER BY date DESC
            LIMIT 14
        ");
        $stmt->execute();
        $chartRows  = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        $chartLabel = 'ข้อมูลล่าสุดที่มีในระบบ';
    }
} catch (Exception $e) {
    error_log('[Dashboard Chart] ' . $e->getMessage());
    $chartRows = []; $chartLabel = 'โหลดไม่ได้';
}

$chartDates  = array_column($chartRows, 'date');
$chartCounts = array_column($chartRows, 'cnt');

/* ── Stat Cards ── */
try {
    $totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalProjects = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $totalAdmins   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();

    $totalLogins = (int)$pdo->query("SELECT COUNT(*) FROM user_logs WHERE login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    $loginLabel  = '30 วัน';
    if ($totalLogins === 0) {
        $totalLogins = (int)$pdo->query("SELECT COUNT(*) FROM user_logs")->fetchColumn();
        $loginLabel  = 'ทั้งหมด';
    }
} catch (Exception $e) {
    error_log('[Dashboard Stats] ' . $e->getMessage());
    $totalUsers = $totalProjects = $totalAdmins = $totalLogins = 0;
    $loginLabel = '-';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="icon" type="image/png" href="assets/Icon portfolio.png">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700;800&family=Share+Tech+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg:#07040f; --surface:rgba(255,255,255,.04); --border:rgba(168,85,247,.15);
      --purple:#a855f7; --pink:#ec4899; --blue:#38bdf8; --green:#34d399;
      --red:#f87171; --text:#e2e8f0; --muted:#64748b; --sidebar-w:260px;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    html{scroll-behavior:smooth;}
    body{background:var(--bg);color:var(--text);font-family:'Kanit',sans-serif;min-height:100vh;overflow-x:hidden;}
    body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
      background-image:linear-gradient(rgba(168,85,247,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(168,85,247,.025) 1px,transparent 1px);
      background-size:48px 48px;}

    /* ═══ SIDEBAR ═══ */
    .sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:rgba(10,7,22,.95);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;transform:translateX(0);transition:transform .3s cubic-bezier(.4,0,.2,1);backdrop-filter:blur(20px);}
    .sidebar-overlay{display:none;position:fixed;inset:0;z-index:99;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);}
    @media(max-width:768px){.sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}.sidebar-overlay.open{display:block;}.main{margin-left:0!important;}}
    .sidebar-logo{padding:24px 20px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
    .logo-icon{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#db2777);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;box-shadow:0 0 20px rgba(168,85,247,.4);}
    .logo-text{font-weight:800;font-size:.95rem;line-height:1.2;}
    .logo-sub{font-family:'Share Tech Mono',monospace;font-size:.6rem;color:var(--muted);letter-spacing:.1em;}
    .sidebar-nav{flex:1;padding:16px 12px;overflow-y:auto;}
    .nav-section-title{font-family:'Share Tech Mono',monospace;font-size:.58rem;color:var(--muted);letter-spacing:.12em;padding:8px 8px 6px;text-transform:uppercase;}
    .nav-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;margin-bottom:2px;color:var(--muted);cursor:pointer;text-decoration:none;font-size:.88rem;font-weight:500;transition:background .2s,color .2s;position:relative;}
    .nav-item:hover{background:rgba(168,85,247,.08);color:var(--text);}
    .nav-item.active{background:rgba(168,85,247,.15);color:var(--purple);}
    .nav-item.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;border-radius:3px;background:linear-gradient(180deg,var(--purple),var(--pink));}
    .nav-item i{width:18px;text-align:center;font-size:.85rem;}
    .nav-badge{margin-left:auto;background:rgba(168,85,247,.2);color:var(--purple);font-size:.65rem;padding:2px 7px;border-radius:20px;font-family:'Share Tech Mono',monospace;}
    .sidebar-footer{padding:16px 12px;border-top:1px solid var(--border);}
    .user-chip{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:var(--surface);border:1px solid var(--border);}
    .user-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#db2777);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0;}
    .user-name{font-size:.83rem;font-weight:600;}
    .user-role{font-size:.68rem;color:var(--purple);font-family:'Share Tech Mono',monospace;}

    /* ═══ TOPBAR ═══ */
    .topbar{position:sticky;top:0;z-index:50;background:rgba(7,4,15,.85);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:14px 24px;display:flex;align-items:center;gap:16px;}
    .menu-btn{display:none;background:none;border:1px solid var(--border);color:var(--text);width:36px;height:36px;border-radius:9px;cursor:pointer;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s;}
    .menu-btn:hover{background:rgba(168,85,247,.1);}
    @media(max-width:768px){.menu-btn{display:flex;}}
    .topbar-title{font-weight:700;font-size:1.05rem;}
    .topbar-breadcrumb{font-family:'Share Tech Mono',monospace;font-size:.62rem;color:var(--muted);margin-top:1px;}
    .topbar-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
    .topbar-btn{background:var(--surface);border:1px solid var(--border);color:var(--muted);width:36px;height:36px;border-radius:9px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.82rem;transition:all .2s;text-decoration:none;}
    .topbar-btn:hover{border-color:var(--purple);color:var(--purple);}
    .dot-live{width:8px;height:8px;border-radius:50%;background:var(--green);animation:blink 2s ease-in-out infinite;}
    @keyframes blink{0%,100%{opacity:1;}50%{opacity:.3;}}

    /* ═══ MAIN ═══ */
    .main{margin-left:var(--sidebar-w);min-height:100vh;position:relative;z-index:1;}
    .content{padding:24px;max-width:1200px;}
    @media(max-width:640px){.content{padding:16px;}}

    /* ═══ NOTIFICATION ═══ */
    .notif{display:flex;align-items:center;gap:10px;background:rgba(52,211,153,.08);border:1px solid rgba(52,211,153,.25);border-radius:12px;padding:12px 16px;margin-bottom:24px;color:#6ee7b7;font-size:.85rem;}

    /* ═══ STAT CARDS ═══ */
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
    @media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,1fr);}}
    @media(max-width:480px){.stats-grid{grid-template-columns:repeat(2,1fr);gap:12px;}}
    .stat-card{background:rgba(10,7,22,.8);border:1px solid var(--border);border-radius:16px;padding:20px;position:relative;overflow:hidden;transition:border-color .3s,transform .3s;cursor:default;}
    .stat-card:hover{border-color:rgba(168,85,247,.4);transform:translateY(-2px);}
    .stat-card::after{content:'';position:absolute;bottom:-30px;right:-20px;width:80px;height:80px;border-radius:50%;opacity:.07;}
    .stat-card.c1::after{background:var(--purple);}.stat-card.c2::after{background:var(--pink);}
    .stat-card.c3::after{background:var(--blue);}.stat-card.c4::after{background:var(--green);}
    .stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:.9rem;margin-bottom:14px;}
    .stat-card.c1 .stat-icon{background:rgba(168,85,247,.15);color:var(--purple);}
    .stat-card.c2 .stat-icon{background:rgba(236,72,153,.15);color:var(--pink);}
    .stat-card.c3 .stat-icon{background:rgba(56,189,248,.15);color:var(--blue);}
    .stat-card.c4 .stat-icon{background:rgba(52,211,153,.15);color:var(--green);}
    .stat-label{font-size:.75rem;color:var(--muted);margin-bottom:4px;font-weight:400;}
    .stat-value{font-size:2rem;font-weight:800;line-height:1;background:linear-gradient(135deg,#e2e8f0,#94a3b8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
    .stat-sub{font-size:.7rem;color:var(--muted);margin-top:6px;font-family:'Share Tech Mono',monospace;}

    /* ═══ PANEL ═══ */
    .panel{background:rgba(10,7,22,.8);border:1px solid var(--border);border-radius:18px;overflow:hidden;margin-bottom:20px;}
    .panel-head{padding:18px 20px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;}
    .panel-head-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.82rem;flex-shrink:0;}
    .panel-title{font-weight:700;font-size:.95rem;}
    .panel-sub{font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;}
    .panel-action{margin-left:auto;background:rgba(168,85,247,.1);border:1px solid rgba(168,85,247,.2);color:var(--purple);padding:6px 14px;border-radius:8px;font-size:.75rem;font-weight:600;cursor:pointer;text-decoration:none;transition:background .2s;font-family:'Kanit',sans-serif;}
    .panel-action:hover{background:rgba(168,85,247,.2);}
    .panel-body{padding:20px;}

    /* ═══ LIST ROWS ═══ */
    .item-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);}
    .item-row:last-child{border-bottom:none;}
    .item-avatar{width:36px;height:36px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;}
    .item-name{font-size:.85rem;font-weight:600;}
    .item-meta{font-size:.7rem;color:var(--muted);font-family:'Share Tech Mono',monospace;}
    .item-actions{margin-left:auto;display:flex;gap:6px;}
    .btn-icon{width:30px;height:30px;border-radius:8px;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.75rem;transition:all .2s;}
    .btn-del{background:rgba(248,113,113,.1);color:var(--red);}
    .btn-del:hover{background:rgba(248,113,113,.25);}

    /* ═══ ADMIN ROW ═══ */
    .admin-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04);flex-wrap:wrap;}
    .admin-row:last-child{border-bottom:none;}
    .role-badge{font-family:'Share Tech Mono',monospace;font-size:.62rem;padding:3px 9px;border-radius:20px;background:rgba(168,85,247,.12);border:1px solid rgba(168,85,247,.2);color:var(--purple);}
    .admin-actions{margin-left:auto;display:flex;gap:8px;align-items:center;flex-wrap:wrap;}

    .select-styled{
      background: #0e0b1e;
      border: 1px solid rgba(168,85,247,.35);
      color: var(--text);
      padding: 7px 32px 7px 10px;
      border-radius: 9px;
      font-family: 'Kanit', sans-serif;
      font-size: .8rem;
      outline: none;
      cursor: pointer;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23a855f7' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      min-width: 120px;
      transition: border-color .2s, box-shadow .2s;
    }
    .select-styled:focus{border-color:var(--purple);box-shadow:0 0 0 2px rgba(168,85,247,.2);}
    .select-styled option{background:#0e0b1e;color:var(--text);}

    /* ═══ BUTTONS ═══ */
    .btn{padding:7px 16px;border-radius:9px;border:none;font-family:'Kanit',sans-serif;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:6px;}
    .btn-primary{background:linear-gradient(135deg,#7c3aed,#db2777);color:#fff;box-shadow:0 4px 14px rgba(124,58,237,.3);}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(124,58,237,.45);}
    .btn-danger{background:rgba(248,113,113,.12);color:var(--red);border:1px solid rgba(248,113,113,.2);}
    .btn-danger:hover{background:rgba(248,113,113,.22);}
    .btn-blue{background:rgba(56,189,248,.1);color:var(--blue);border:1px solid rgba(56,189,248,.2);}
    .btn-blue:hover{background:rgba(56,189,248,.2);}
    .btn-green{background:rgba(52,211,153,.1);color:var(--green);border:1px solid rgba(52,211,153,.2);}
    .btn-green:hover{background:rgba(52,211,153,.2);}
    .btn:disabled{opacity:.45;cursor:not-allowed;transform:none!important;}

    .add-admin-wrap{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:14px;background:rgba(255,255,255,.02);border-radius:12px;border:1px dashed var(--border);}

    /* ═══ TABLE ═══ */
    .table-wrap{overflow-x:auto;border-radius:12px;}
    table{width:100%;border-collapse:collapse;font-size:.82rem;}
    thead tr{background:rgba(168,85,247,.06);}
    thead th{padding:11px 14px;text-align:left;font-family:'Share Tech Mono',monospace;font-size:.62rem;color:var(--muted);letter-spacing:.08em;border-bottom:1px solid var(--border);white-space:nowrap;}
    tbody tr{border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s;}
    tbody tr:hover{background:rgba(168,85,247,.04);}
    tbody tr:last-child{border-bottom:none;}
    tbody td{padding:11px 14px;color:var(--text);}
    .td-mono{font-family:'Share Tech Mono',monospace;font-size:.78rem;color:var(--muted);}

    /* ═══ CHART ═══ */
    .chart-wrap{position:relative;height:240px;}
    @media(max-width:640px){.chart-wrap{height:190px;}}

    /* ═══ TWO COL ═══ */
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
    @media(max-width:768px){.two-col{grid-template-columns:1fr;}}

    /* ═══ SCROLL LIST ═══ */
    .scroll-list{max-height:280px;overflow-y:auto;}
    .scroll-list::-webkit-scrollbar{width:4px;}
    .scroll-list::-webkit-scrollbar-track{background:transparent;}
    .scroll-list::-webkit-scrollbar-thumb{background:rgba(168,85,247,.3);border-radius:4px;}

    /* ═══ EMPTY STATE ═══ */
    .empty-state{text-align:center;padding:32px 16px;color:var(--muted);font-family:'Share Tech Mono',monospace;font-size:.72rem;letter-spacing:.08em;}
    .empty-state i{font-size:1.8rem;margin-bottom:10px;display:block;opacity:.3;}

    /* ═══ ANIMATIONS ═══ */
    @keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);}}
    .fu{opacity:0;animation:fadeUp .5s cubic-bezier(.16,1,.3,1) forwards;}
    .d0{animation-delay:.05s;}.d1{animation-delay:.1s;}.d2{animation-delay:.16s;}
    .d3{animation-delay:.22s;}.d4{animation-delay:.28s;}.d5{animation-delay:.34s;}
    @media(max-width:768px){.content{padding-bottom:32px;}}

    @keyframes shimmer{0%{background-position:0% 0;}100%{background-position:200% 0;}}
    .shimmer-bar{height:2px;background:linear-gradient(90deg,#7c3aed,#ec4899,#38bdf8,#ec4899,#7c3aed);background-size:200% 100%;animation:shimmer 3s linear infinite;}

    .sec-badge{display:inline-flex;align-items:center;gap:4px;font-family:'Share Tech Mono',monospace;font-size:.52rem;color:rgba(52,211,153,.7);letter-spacing:.1em;background:rgba(52,211,153,.06);border:1px solid rgba(52,211,153,.15);padding:2px 7px;border-radius:20px;margin-left:8px;vertical-align:middle;}
    .self-tag{font-size:.58rem;color:var(--muted);font-family:'Share Tech Mono',monospace;padding:2px 6px;border-radius:4px;background:rgba(255,255,255,.05);}
  </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar" id="sidebar">
  <div class="shimmer-bar"></div>
  <div class="sidebar-logo">
    <div class="logo-icon">⚡</div>
    <div>
      <div class="logo-text">ZUMO PORTFOLIO</div>
      <div class="logo-sub">// ADMIN PANEL</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-title">เมนูหลัก</div>
    <a href="dashboard.php" class="nav-item active"><i class="fas fa-chart-pie"></i> แดชบอร์ด</a>
    <a href="index.php"     class="nav-item"><i class="fas fa-globe"></i> ดูเว็บไซต์</a>
    <div class="nav-section-title" style="margin-top:12px;">จัดการ</div>
    <a href="#users"    class="nav-item" onclick="closeSidebar()"><i class="fas fa-users"></i> ผู้ใช้งาน<span class="nav-badge"><?= $totalUsers ?></span></a>
    <a href="#projects" class="nav-item" onclick="closeSidebar()"><i class="fas fa-folder-open"></i> โปรเจกต์<span class="nav-badge"><?= $totalProjects ?></span></a>
    <a href="#admins"   class="nav-item" onclick="closeSidebar()"><i class="fas fa-shield-halved"></i> แอดมิน<span class="nav-badge"><?= $totalAdmins ?></span></a>
    <a href="#logs"     class="nav-item" onclick="closeSidebar()"><i class="fas fa-list-ul"></i> Log ระบบ</a>
    <div class="nav-section-title" style="margin-top:12px;">บัญชี</div>
    <a href="logout.php" class="nav-item" style="color:#f87171;"><i class="fas fa-right-from-bracket"></i> ออกจากระบบ</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-avatar"><?= strtoupper(mb_substr(htmlspecialchars($_SESSION['username']), 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="user-role">ADMINISTRATOR</div>
      </div>
      <div style="margin-left:auto;"><div class="dot-live"></div></div>
    </div>
  </div>
</aside>

<!-- ═══ MAIN ═══ -->
<main class="main">
  <div class="topbar">
    <button class="menu-btn" onclick="openSidebar()"><i class="fas fa-bars" style="font-size:.82rem;"></i></button>
    <div>
      <div class="topbar-title">
        แดชบอร์ด
        <span class="sec-badge"><i class="fas fa-lock" style="font-size:.46rem;"></i>CSRF ON</span>
      </div>
      <div class="topbar-breadcrumb">ADMIN / DASHBOARD.PHP</div>
    </div>
    <div class="topbar-right">
      <a href="index.php"  class="topbar-btn" title="ดูเว็บไซต์"><i class="fas fa-arrow-up-right-from-square"></i></a>
      <a href="logout.php" class="topbar-btn" title="ออกจากระบบ"><i class="fas fa-right-from-bracket"></i></a>
    </div>
  </div>

  <div class="content">

    <?php if ($notification): ?>
    <div class="notif fu d0">
      <i class="fas fa-circle-check" style="color:var(--green);"></i>
      <?= htmlspecialchars($notification) ?>
    </div>
    <?php endif; ?>

    <!-- ═══ STAT CARDS ═══ -->
    <div class="stats-grid">
      <div class="stat-card c1 fu d0">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-label">ผู้ใช้งานทั้งหมด</div>
        <div class="stat-value" data-count="<?= $totalUsers ?>">0</div>
        <div class="stat-sub">TOTAL USERS</div>
      </div>
      <div class="stat-card c2 fu d1">
        <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
        <div class="stat-label">โปรเจกต์ทั้งหมด</div>
        <div class="stat-value" data-count="<?= $totalProjects ?>">0</div>
        <div class="stat-sub">TOTAL PROJECTS</div>
      </div>
      <div class="stat-card c3 fu d2">
        <div class="stat-icon"><i class="fas fa-shield-halved"></i></div>
        <div class="stat-label">ผู้ดูแลระบบ</div>
        <div class="stat-value" data-count="<?= $totalAdmins ?>">0</div>
        <div class="stat-sub">ADMINISTRATORS</div>
      </div>
      <div class="stat-card c4 fu d3">
        <div class="stat-icon"><i class="fas fa-arrow-right-to-bracket"></i></div>
        <div class="stat-label">Login (<?= htmlspecialchars($loginLabel) ?>)</div>
        <div class="stat-value" data-count="<?= $totalLogins ?>">0</div>
        <div class="stat-sub">LOGINS <?= strtoupper(htmlspecialchars($loginLabel)) ?></div>
      </div>
    </div>

    <!-- ═══ CHART ═══ -->
    <div class="panel fu d2">
      <div class="panel-head">
        <div class="panel-head-icon" style="background:rgba(56,189,248,.1);color:var(--blue);">
          <i class="fas fa-chart-line" style="font-size:.8rem;"></i>
        </div>
        <div>
          <div class="panel-title">สถิติการเข้าสู่ระบบ</div>
          <div class="panel-sub"><?= htmlspecialchars($chartLabel) ?></div>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($chartDates)): ?>
        <div class="empty-state">
          <i class="fas fa-chart-line"></i>
          ยังไม่มีข้อมูล Login ในระบบ<br>
          <span style="color:var(--red);margin-top:6px;display:block;font-size:.6rem;">
            ตรวจสอบว่า login.php INSERT ลงตาราง user_logs แล้วหรือยัง
          </span>
        </div>
        <?php else: ?>
        <div class="chart-wrap">
          <canvas id="userStatsChart"></canvas>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ═══ USERS + PROJECTS ═══ -->
    <div class="two-col">

      <!-- Users -->
      <div class="panel fu d3" id="users">
        <div class="panel-head">
          <div class="panel-head-icon" style="background:rgba(168,85,247,.1);color:var(--purple);">
            <i class="fas fa-users" style="font-size:.8rem;"></i>
          </div>
          <div>
            <div class="panel-title">ผู้ใช้งาน</div>
            <div class="panel-sub"><?= $totalUsers ?> ACCOUNTS</div>
          </div>
        </div>
        <div class="panel-body">
          <div class="scroll-list">
          <?php
          $colors = ['rgba(168,85,247,.2)','rgba(236,72,153,.2)','rgba(56,189,248,.2)','rgba(52,211,153,.2)'];
          $ci = 0;
          try {
              $stmt = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
              $userRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (Exception $e) { $userRows = []; error_log('[Dashboard Users] '.$e->getMessage()); }

          if (empty($userRows)): ?>
            <div class="empty-state"><i class="fas fa-users"></i>ยังไม่มีผู้ใช้</div>
          <?php else: foreach ($userRows as $row):
            $c = $colors[$ci++ % count($colors)];
            $initial = strtoupper(mb_substr($row['username'], 0, 1));
          ?>
          <div class="item-row">
            <div class="item-avatar" style="background:<?= $c ?>;"><?= htmlspecialchars($initial) ?></div>
            <div>
              <div class="item-name"><?= htmlspecialchars($row['username']) ?></div>
              <div class="item-meta"><?= htmlspecialchars($row['email']) ?></div>
            </div>
            <div class="item-actions">
              <?php if ((int)$row['id'] !== (int)$_SESSION['user_id']): ?>
              <form method="POST" action="delete_user.php"
                    onsubmit="return safeConfirm(this,'ลบ <?= htmlspecialchars($row['username'], ENT_QUOTES) ?> ออกจากระบบ?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="user_id"   value="<?= (int)$row['id'] ?>">
                <button class="btn-icon btn-del" type="submit" title="ลบผู้ใช้">
                  <i class="fas fa-trash-can"></i>
                </button>
              </form>
              <?php else: ?>
              <span class="self-tag">ตัวเอง</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; endif; ?>
          </div>
        </div>
      </div>

      <!-- Projects -->
      <div class="panel fu d4" id="projects">
        <div class="panel-head">
          <div class="panel-head-icon" style="background:rgba(236,72,153,.1);color:var(--pink);">
            <i class="fas fa-folder-open" style="font-size:.8rem;"></i>
          </div>
          <div>
            <div class="panel-title">โปรเจกต์</div>
            <div class="panel-sub"><?= $totalProjects ?> PROJECTS</div>
          </div>
        </div>
        <div class="panel-body">
          <div class="scroll-list">
          <?php
          $pcolors = ['rgba(236,72,153,.2)','rgba(168,85,247,.2)','rgba(56,189,248,.2)','rgba(52,211,153,.2)'];
          $pi = 0;
          try {
              $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY id DESC");
              $projRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (Exception $e) { $projRows = []; error_log('[Dashboard Projects] '.$e->getMessage()); }

          if (empty($projRows)): ?>
            <div class="empty-state"><i class="fas fa-folder-open"></i>ยังไม่มีโปรเจกต์</div>
          <?php else: foreach ($projRows as $row):
            $c = $pcolors[$pi++ % count($pcolors)];
          ?>
          <div class="item-row">
            <div class="item-avatar" style="background:<?= $c ?>;border-radius:10px;">
              <i class="fas fa-code" style="font-size:.7rem;color:#e2e8f0;"></i>
            </div>
            <div>
              <div class="item-name"><?= htmlspecialchars($row['name']) ?></div>
              <div class="item-meta">ID #<?= (int)$row['id'] ?></div>
            </div>
            <div class="item-actions">
              <form method="POST" action="delete_project.php"
                    onsubmit="return safeConfirm(this,'ลบโปรเจกต์ <?= htmlspecialchars($row['name'], ENT_QUOTES) ?>?')">
                <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="project_id"  value="<?= (int)$row['id'] ?>">
                <button class="btn-icon btn-del" type="submit">
                  <i class="fas fa-trash-can"></i>
                </button>
              </form>
            </div>
          </div>
          <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ ADMIN MANAGEMENT ═══ -->
    <div class="panel fu d4" id="admins">
      <div class="panel-head">
        <div class="panel-head-icon" style="background:rgba(52,211,153,.1);color:var(--green);">
          <i class="fas fa-shield-halved" style="font-size:.8rem;"></i>
        </div>
        <div>
          <div class="panel-title">จัดการผู้ดูแลระบบ</div>
          <div class="panel-sub">ADMIN MANAGEMENT</div>
        </div>
      </div>
      <div class="panel-body">

        <!-- Add Admin -->
        <div style="margin-bottom:20px;">
          <div style="font-size:.78rem;color:var(--muted);margin-bottom:10px;font-family:'Share Tech Mono',monospace;letter-spacing:.05em;">
            + เพิ่มผู้ดูแลระบบใหม่
          </div>
          <form action="add_admin.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="add-admin-wrap">
              <select name="user_id" class="select-styled" required>
                <option value="" disabled selected>— เลือกผู้ใช้งาน —</option>
                <?php
                try {
                    $stmt = $pdo->query("SELECT id, username FROM users WHERE role != 'admin' ORDER BY username ASC");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                <option value="<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['username']) ?></option>
                <?php endwhile; } catch (Exception $e) { error_log('[Add Admin List] '.$e->getMessage()); } ?>
              </select>
              <button type="submit" class="btn btn-green">
                <i class="fas fa-user-shield"></i> เพิ่มแอดมิน
              </button>
            </div>
          </form>
        </div>

        <!-- Admin List -->
        <div style="font-size:.78rem;color:var(--muted);margin-bottom:10px;font-family:'Share Tech Mono',monospace;letter-spacing:.05em;">
          รายชื่อผู้ดูแลระบบปัจจุบัน
        </div>
        <?php
        try {
            $stmt = $pdo->query("SELECT id, username, role FROM users WHERE role = 'admin' ORDER BY id ASC");
            $adminRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $adminRows = []; error_log('[Admin List] '.$e->getMessage()); }

        if (empty($adminRows)): ?>
        <div class="empty-state"><i class="fas fa-shield-halved"></i>ยังไม่มีแอดมิน</div>
        <?php else: foreach ($adminRows as $row):
          $initial = strtoupper(mb_substr($row['username'], 0, 1));
          $isSelf  = ((int)$row['id'] === (int)$_SESSION['user_id']);
        ?>
        <div class="admin-row">
          <div class="item-avatar" style="background:rgba(52,211,153,.15);"><?= htmlspecialchars($initial) ?></div>
          <div>
            <div class="item-name">
              <?= htmlspecialchars($row['username']) ?>
              <?php if ($isSelf): ?><span class="self-tag">คุณ</span><?php endif; ?>
            </div>
            <div class="item-meta">UID #<?= (int)$row['id'] ?></div>
          </div>
          <span class="role-badge">ADMIN</span>
          <div class="admin-actions">
            <?php if (!$isSelf): ?>
            <form method="POST" action="change_role.php" style="display:flex;gap:6px;align-items:center;">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="user_id"    value="<?= (int)$row['id'] ?>">
              <select name="new_role" class="select-styled">
                <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="user"  <?= $row['role'] === 'user'  ? 'selected' : '' ?>>User</option>
              </select>
              <button type="submit" class="btn btn-blue"
                      onclick="return confirm('เปลี่ยน Role ของ <?= htmlspecialchars($row['username'], ENT_QUOTES) ?>?')">
                <i class="fas fa-arrows-rotate"></i> เปลี่ยน
              </button>
            </form>

            <form method="POST" action="delete_admin.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="user_id"    value="<?= (int)$row['id'] ?>">
              <button type="submit" class="btn btn-danger"
                      onclick="return safeConfirmBtn(this,'ลบแอดมิน <?= htmlspecialchars($row['username'], ENT_QUOTES) ?>?')">
                <i class="fas fa-trash-can"></i> ลบ
              </button>
            </form>
            <?php else: ?>
            <span style="font-size:.7rem;color:var(--muted);font-family:'Share Tech Mono',monospace;">ไม่สามารถแก้ไขตัวเองได้</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- ═══ LOGIN LOGS ═══ -->
    <div class="panel fu d5" id="logs">
      <div class="panel-head">
        <div class="panel-head-icon" style="background:rgba(248,113,113,.1);color:var(--red);">
          <i class="fas fa-list-ul" style="font-size:.8rem;"></i>
        </div>
        <div>
          <div class="panel-title">Log การเข้าสู่ระบบ</div>
          <div class="panel-sub">20 รายการล่าสุด — ทุก record</div>
        </div>
      </div>
      <div class="panel-body">
        <?php
        try {
            $logStmt = $pdo->query("
                SELECT
                    ul.id,
                    ul.user_id,
                    COALESCE(u.username, '[ถูกลบแล้ว]') AS username,
                    ul.login_time
                FROM user_logs ul
                LEFT JOIN users u ON ul.user_id = u.id
                ORDER BY ul.login_time DESC
                LIMIT 20
            ");
            $logRows = $logStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $logRows = [];
            error_log('[Dashboard Logs] ' . $e->getMessage());
        }
        ?>
        <?php if (empty($logRows)): ?>
        <div class="empty-state">
          <i class="fas fa-list-ul"></i>
          ยังไม่มีข้อมูลใน user_logs<br>
          <span style="color:var(--red);margin-top:8px;display:block;font-size:.6rem;">
            ตรวจสอบว่า login.php INSERT ลงตาราง user_logs แล้วหรือยัง
          </span>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>LOG ID</th>
                <th>USER ID</th>
                <th>Username</th>
                <th>วันที่เข้าระบบ</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($logRows as $i => $log): ?>
            <tr>
              <td class="td-mono"><?= $i + 1 ?></td>
              <td class="td-mono">#<?= (int)$log['id'] ?></td>
              <td class="td-mono">#<?= (int)$log['user_id'] ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="item-avatar" style="width:26px;height:26px;font-size:.65rem;background:rgba(168,85,247,.15);flex-shrink:0;">
                    <?= strtoupper(mb_substr($log['username'] ?? '?', 0, 1)) ?>
                  </div>
                  <?= htmlspecialchars($log['username'] ?? '-') ?>
                </div>
              </td>
              <td class="td-mono"><?= htmlspecialchars($log['login_time']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /content -->
</main>

<script>
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('open');
  document.body.style.overflow = '';
}

function safeConfirm(form, msg) {
  if (!confirm(msg)) return false;
  var btn = form.querySelector('button[type=submit]');
  if (btn) { btn.disabled = true; btn.style.opacity = '.45'; }
  return true;
}
function safeConfirmBtn(btn, msg) {
  if (!confirm(msg)) return false;
  btn.disabled = true; btn.style.opacity = '.45';
  return true;
}

document.addEventListener('DOMContentLoaded', function() {

  document.querySelectorAll('[data-count]').forEach(function(el) {
    var target = parseInt(el.dataset.count, 10);
    if (isNaN(target) || target === 0) { el.textContent = '0'; return; }
    var cur = 0, step = Math.max(1, Math.floor(target / 60));
    var ms  = Math.max(16, Math.floor(1200 / Math.ceil(target / step)));
    var t = setInterval(function() {
      cur = Math.min(cur + step, target);
      el.textContent = cur;
      if (cur >= target) clearInterval(t);
    }, ms);
  });

  var chartCanvas = document.getElementById('userStatsChart');
  if (chartCanvas) {
    var ctx  = chartCanvas.getContext('2d');
    var grad = ctx.createLinearGradient(0, 0, 0, 240);
    grad.addColorStop(0, 'rgba(168,85,247,.35)');
    grad.addColorStop(1, 'rgba(168,85,247,.0)');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels:   <?= json_encode($chartDates,  JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
          label: 'Login',
          data:  <?= json_encode($chartCounts) ?>,
          backgroundColor: grad,
          borderColor: 'rgba(168,85,247,1)',
          borderWidth: 2, tension: 0.4, fill: true,
          pointBackgroundColor: '#07040f',
          pointBorderColor:     'rgba(168,85,247,1)',
          pointBorderWidth: 2, pointRadius: 5, pointHoverRadius: 7,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(10,7,22,.95)',
            borderColor: 'rgba(168,85,247,.3)', borderWidth: 1,
            titleColor: '#a855f7', bodyColor: '#e2e8f0', padding: 10,
          }
        },
        scales: {
          x: { ticks:{color:'#475569',font:{size:11}}, grid:{color:'rgba(255,255,255,.04)'} },
          y: { beginAtZero:true, ticks:{color:'#475569',font:{size:11},stepSize:1}, grid:{color:'rgba(255,255,255,.04)'} }
        }
      }
    });
  }

  var obs = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
      if (e.isIntersecting) { e.target.style.opacity = '1'; obs.unobserve(e.target); }
    });
  }, { threshold: 0.08 });
  document.querySelectorAll('.fu').forEach(function(el) { obs.observe(el); });

});
</script>
</body>
</html>