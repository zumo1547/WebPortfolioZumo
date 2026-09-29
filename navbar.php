<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600;700;800&family=Share+Tech+Mono&display=swap" rel="stylesheet">

<style>
/* ─────────────────────────────────────────
   CSS VARIABLES — ใช้ใน projects.php ได้
   --nb-height : ความสูง navbar
   --nb-z      : z-index navbar (ปรับได้จาก JS)
───────────────────────────────────────── */
:root {
  --nb-height : 80px;
  --nb-z      : 400;
}

/* ─────────────────────────────────────────
   BASE
───────────────────────────────────────── */
#navbar {
  position: sticky; top: 0;
  z-index: var(--nb-z);
  font-family: 'Kanit', sans-serif;
  /* transition z-index ไม่ได้ แต่ใช้ class แทน */
}

/* ── เมื่อ modal/overlay เปิด → ลด z-index navbar ─── */
#navbar.nb-behind { z-index: 50 !important; }

.nb-bar {
  background: rgba(7,4,15,.85);
  backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
  border-bottom: 1px solid rgba(168,85,247,.13);
  transition: background .3s, box-shadow .3s;
}
.nb-bar.scrolled {
  background: rgba(7,4,15,.97);
  box-shadow: 0 4px 30px rgba(0,0,0,.55);
  border-color: rgba(168,85,247,.22);
}
.nb-line {
  height: 2px;
  background: linear-gradient(90deg,#7c3aed,#ec4899,#38bdf8,#ec4899,#7c3aed);
  background-size: 200%;
  animation: lineMove 3s linear infinite;
}
@keyframes lineMove { to { background-position: 200%; } }

.nb-row {
  max-width: 1280px; margin: 0 auto;
  padding: 0 36px; height: 80px;
  display: flex; align-items: center;
}

/* ─────────────────────────────────────────
   LOGO
───────────────────────────────────────── */
.nb-logo {
  display: flex; align-items: center; gap: 11px;
  text-decoration: none; flex-shrink: 0;
  transition: opacity .2s;
}
.nb-logo:hover { opacity: .85; }
.nb-logo-img {
  width: 48px; height: 48px; border-radius: 13px;
  overflow: hidden; flex-shrink: 0;
  box-shadow: 0 0 20px rgba(168,85,247,.4);
}
.nb-logo-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.nb-logo-name {
  font-weight: 800; font-size: 1.22rem; letter-spacing: .01em;
  background: linear-gradient(135deg,#e879f9,#f472b6,#a78bfa);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

/* ─────────────────────────────────────────
   DESKTOP LINKS
───────────────────────────────────────── */
.nb-links {
  display: flex; align-items: center; gap: 2px;
  list-style: none; margin-left: auto; margin-right: 14px;
}
.nb-a {
  position: relative; text-decoration: none;
  color: #94a3b8; font-size: 1.05rem; font-weight: 500;
  padding: 9px 16px; border-radius: 10px;
  transition: color .2s, background .2s; white-space: nowrap;
}
.nb-a:hover  { color: #e2e8f0; background: rgba(255,255,255,.05); }
.nb-a.on     { color: #a855f7; }
.nb-a::after {
  content: ''; position: absolute;
  bottom: 4px; left: 12px; right: 12px; height: 2px;
  background: linear-gradient(90deg,#7c3aed,#db2777);
  border-radius: 2px; transform: scaleX(0);
  transform-origin: left; transition: transform .25s cubic-bezier(.4,0,.2,1);
}
.nb-a.on::after, .nb-a:hover::after { transform: scaleX(1); }

/* ─────────────────────────────────────────
   DESKTOP ACTIONS
───────────────────────────────────────── */
.nb-sep { width: 1px; height: 20px; background: rgba(168,85,247,.15); flex-shrink: 0; margin-right: 4px; }
.nb-acts { display: flex; align-items: center; gap: 7px; }

.nb-chip {
  display: flex; align-items: center; gap: 8px;
  padding: 5px 13px 5px 5px;
  background: rgba(255,255,255,.04); border: 1px solid rgba(168,85,247,.15); border-radius: 30px;
}
.nb-av {
  width: 38px; height: 38px; border-radius: 50%;
  background: linear-gradient(135deg,#7c3aed,#db2777);
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.nb-uname { font-size: .95rem; color: #e2e8f0; font-weight: 600; max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.nb-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 20px; border-radius: 11px;
  font-family: 'Kanit', sans-serif; font-size: .96rem; font-weight: 600;
  text-decoration: none; border: none; cursor: pointer;
  transition: all .2s; white-space: nowrap;
}
.nb-btn i { font-size: .78rem; }
.nb-gold   { background: linear-gradient(135deg,#d97706,#f59e0b); color: #07040f; box-shadow: 0 3px 12px rgba(217,119,6,.25); }
.nb-gold:hover   { transform: translateY(-1px); box-shadow: 0 5px 18px rgba(217,119,6,.4); }
.nb-purple { background: rgba(168,85,247,.12); border: 1px solid rgba(168,85,247,.22); color: #a855f7; }
.nb-purple:hover { background: rgba(168,85,247,.22); }
.nb-red    { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.18); color: #f87171; }
.nb-red:hover    { background: rgba(248,113,113,.2); }

/* icon-only btn */
.nb-ibtn {
  width: 44px; height: 44px; border-radius: 11px;
  display: inline-flex; align-items: center; justify-content: center;
  text-decoration: none; border: none; cursor: pointer; font-size: .92rem;
  transition: all .2s;
}

/* ─────────────────────────────────────────
   HAMBURGER
───────────────────────────────────────── */
.nb-ham {
  display: none;
  width: 44px; height: 44px; border-radius: 12px;
  background: rgba(168,85,247,.08); border: 1px solid rgba(168,85,247,.2);
  color: #e2e8f0; cursor: pointer;
  align-items: center; justify-content: center;
  margin-left: auto; flex-shrink: 0;
  transition: background .2s, opacity .2s;
  position: relative; z-index: 410;
}
.nb-ham:hover { background: rgba(168,85,247,.18); }
.hb { display: flex; flex-direction: column; gap: 5px; }
.hb span {
  display: block; height: 2px; border-radius: 2px;
  background: currentColor;
  transition: all .3s cubic-bezier(.4,0,.2,1);
}
.hb span:nth-child(1) { width: 22px; }
.hb span:nth-child(2) { width: 16px; }
.hb span:nth-child(3) { width: 22px; }
.nb-ham.is-open .hb span:nth-child(1) { width: 22px; transform: translateY(7px) rotate(45deg); }
.nb-ham.is-open .hb span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.nb-ham.is-open .hb span:nth-child(3) { width: 22px; transform: translateY(-7px) rotate(-45deg); }

/* ─────────────────────────────────────────
   FULLSCREEN MOBILE MENU
───────────────────────────────────────── */
.nb-mobile {
  display: flex;
  position: fixed; inset: 0; z-index: 500;
  background: rgba(7,4,15,.97);
  backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
  flex-direction: column;
  overflow-y: auto;
  transform: translateY(-100%);
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: transform .42s cubic-bezier(.4,0,.2,1),
              opacity .38s cubic-bezier(.4,0,.2,1),
              visibility 0s linear .42s;
}
.nb-mobile.open {
  transform: translateY(0);
  opacity: 1;
  visibility: visible;
  pointer-events: all;
  transition: transform .42s cubic-bezier(.16,1,.3,1),
              opacity .35s cubic-bezier(.16,1,.3,1),
              visibility 0s linear 0s;
}

/* top strip */
.nbm-top {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 22px 16px;
  border-bottom: 1px solid rgba(168,85,247,.1);
  flex-shrink: 0;
}
.nbm-close {
  width: 42px; height: 42px; border-radius: 12px;
  background: rgba(255,255,255,.05); border: 1px solid rgba(168,85,247,.15);
  color: #94a3b8; cursor: pointer; font-size: .9rem;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s, color .2s, transform .3s;
  flex-shrink: 0;
}
.nbm-close:hover, .nbm-close:active { background: rgba(168,85,247,.15); color: #e2e8f0; transform: rotate(90deg); }

/* user strip */
.nbm-user {
  margin: 16px 20px 4px;
  display: flex; align-items: center; gap: 14px;
  padding: 14px 18px; border-radius: 16px;
  background: linear-gradient(135deg,rgba(124,58,237,.1),rgba(219,39,119,.06));
  border: 1px solid rgba(168,85,247,.13);
}
.nbm-av {
  width: 46px; height: 46px; border-radius: 50%;
  background: linear-gradient(135deg,#7c3aed,#db2777);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.05rem; font-weight: 800; color: #fff; flex-shrink: 0;
  box-shadow: 0 0 18px rgba(168,85,247,.4);
}
.nbm-uname { font-size: 1rem; font-weight: 700; color: #e2e8f0; }
.nbm-role  { font-family: 'Share Tech Mono', monospace; font-size: .6rem; color: #a855f7; margin-top: 2px; }
.nbm-dot   {
  margin-left: auto; width: 9px; height: 9px; border-radius: 50%;
  background: #34d399; box-shadow: 0 0 8px #34d399; flex-shrink: 0;
  animation: nbmPulse 2s ease-in-out infinite;
}
@keyframes nbmPulse { 50% { opacity:.3; transform: scale(.8); } }

/* nav items */
.nbm-nav { padding: 12px 16px; flex: 1; }
.nbm-sec {
  font-family: 'Share Tech Mono', monospace;
  font-size: .58rem; color: #374151; letter-spacing: .14em;
  padding: 14px 6px 6px; text-transform: uppercase;
}
.nbm-item {
  display: flex; align-items: center; gap: 16px;
  padding: 14px 16px; border-radius: 14px; margin-bottom: 4px;
  text-decoration: none; color: #94a3b8; font-size: 1rem; font-weight: 500;
  transition: background .18s, color .18s, transform .18s;
  position: relative;
}
.nbm-item:active { transform: scale(.97); }
.nbm-item:hover  { background: rgba(168,85,247,.07); color: #e2e8f0; }
.nbm-item.on {
  background: rgba(168,85,247,.11); color: #a855f7;
  border: 1px solid rgba(168,85,247,.14);
}
.nbm-item.on::before {
  content: ''; position: absolute;
  left: 0; top: 22%; bottom: 22%; width: 3px; border-radius: 0 3px 3px 0;
  background: linear-gradient(180deg,#7c3aed,#db2777);
}
.nbm-ic {
  width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: .88rem;
  background: rgba(255,255,255,.05);
  transition: background .18s;
}
.nbm-item.on  .nbm-ic { background: rgba(168,85,247,.18); }
.nbm-item:hover:not(.on) .nbm-ic { background: rgba(168,85,247,.1); }
.nbm-label { flex: 1; }
.nbm-badge {
  font-family: 'Share Tech Mono', monospace; font-size: .58rem;
  padding: 3px 9px; border-radius: 20px;
  background: rgba(217,119,6,.12); color: #fbbf24; border: 1px solid rgba(217,119,6,.2);
}

/* logout row */
.nbm-footer { padding: 8px 16px 32px; flex-shrink: 0; }
.nbm-logout {
  display: flex; align-items: center; gap: 16px;
  padding: 14px 16px; border-radius: 14px;
  text-decoration: none; color: #f87171; font-size: 1rem; font-weight: 600;
  background: rgba(248,113,113,.06); border: 1px solid rgba(248,113,113,.1);
  transition: background .18s, transform .18s;
}
.nbm-logout:active { transform: scale(.97); }
.nbm-logout:hover  { background: rgba(248,113,113,.14); }
.nbm-logout .nbm-ic { background: rgba(248,113,113,.1); color: #f87171; }

/* ─────────────────────────────────────────
   STAGGER
───────────────────────────────────────── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(18px); }
  to   { opacity: 1; transform: translateY(0); }
}
.nbm-user, .nbm-item, .nbm-logout, .nbm-sec { opacity: 0; }
.nb-mobile.open .nbm-user   { animation: fadeUp .38s cubic-bezier(.16,1,.3,1) .12s  both; }
.nb-mobile.open .nbm-sec    { animation: fadeUp .32s cubic-bezier(.16,1,.3,1) var(--nd,.18s) both; }
.nb-mobile.open .nbm-item   { animation: fadeUp .38s cubic-bezier(.16,1,.3,1) var(--nd,.22s) both; }
.nb-mobile.open .nbm-logout { animation: fadeUp .38s cubic-bezier(.16,1,.3,1) .60s  both; }

/* ─────────────────────────────────────────
   RESPONSIVE
───────────────────────────────────────── */
@media (max-width: 900px) {
  .nb-links, .nb-sep, .nb-acts, .nb-chip { display: none !important; }
  .nb-ham { display: flex !important; }
}
@media (max-width: 480px) {
  .nb-row { padding: 0 16px; height: 62px; }
  .nb-logo-name { font-size: .98rem; }
  .nb-logo-img { width: 38px; height: 38px; border-radius: 10px; }
}
</style>

<?php
function nbOn(string $f): string { return basename($_SERVER['PHP_SELF']) === $f ? 'on' : ''; }
$username = htmlspecialchars($_SESSION['username'] ?? '');
$initial  = strtoupper(mb_substr($username, 0, 1));
$isAdmin  = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$loggedIn = isset($_SESSION['user_id']);
$nav = [
  ['Home',     'index.php',    'fa-house',      false],
  ['About',    'about.php',    'fa-user',        true],
  ['Projects', 'projects.php', 'fa-folder-open', true],
  ['Contact',  'contact.php',  'fa-envelope',    true],
];
?>

<!-- ═══ TOPBAR ═══ -->
<div id="navbar">
  <div class="nb-bar" id="nbBar">
    <div class="nb-line"></div>
    <div class="nb-row">

      <a href="index.php" class="nb-logo">
        <div class="nb-logo-img"><img src="assets/Icon portfolio.png" alt="Logo" loading="eager"></div>
        <span class="nb-logo-name">Portfolio</span>
      </a>

      <!-- Desktop links -->
      <ul class="nb-links">
        <?php foreach ($nav as [$lbl,$url,$ic,$need]): ?>
        <?php if ($need && !$loggedIn): ?>
          <li><a href="#" class="nb-a <?=nbOn($url)?>" onclick="showModal();return false;"><?=$lbl?></a></li>
        <?php else: ?>
          <li><a href="<?=$url?>" class="nb-a <?=nbOn($url)?>"><?=$lbl?></a></li>
        <?php endif; endforeach; ?>
      </ul>

      <!-- Desktop right -->
      <?php if ($loggedIn): ?>
        <div class="nb-sep"></div>
        <div class="nb-acts">
          <div class="nb-chip">
            <div class="nb-av"><?=$initial?></div>
            <span class="nb-uname"><?=$username?></span>
          </div>
          <?php if ($isAdmin): ?>
          <a href="dashboard.php" class="nb-btn nb-gold"><i class="fas fa-gauge-high"></i> Dashboard</a>
          <?php endif; ?>
          <a href="setting.php" class="nb-ibtn nb-purple" title="Settings"><i class="fas fa-gear"></i></a>
          <a href="logout.php"  class="nb-ibtn nb-red"    title="Logout"><i class="fas fa-right-from-bracket"></i></a>
        </div>
      <?php else: ?>
        <div class="nb-acts" style="margin-left:auto;">
          <a href="login.php"    class="nb-btn nb-purple"><i class="fas fa-right-to-bracket"></i> เข้าสู่ระบบ</a>
          <a href="register.php" class="nb-btn nb-gold"><i class="fas fa-user-plus"></i> สมัคร</a>
        </div>
      <?php endif; ?>

      <!-- Hamburger -->
      <button class="nb-ham" id="nbHam" type="button" aria-label="เมนู">
        <div class="hb">
          <span></span><span></span><span></span>
        </div>
      </button>

    </div>
  </div>
</div>

<!-- ═══ FULLSCREEN MOBILE MENU ═══ -->
<div class="nb-mobile" id="nbMobile" aria-hidden="true">

  <div class="nbm-top">
    <span style="font-family:'Share Tech Mono',monospace;font-size:.62rem;color:#475569;letter-spacing:.12em;">NAVIGATION</span>
    <button class="nbm-close" id="nbMClose" type="button" aria-label="ปิด">
      <i class="fas fa-xmark"></i>
    </button>
  </div>

  <?php if ($loggedIn): ?>
  <div class="nbm-user">
    <div class="nbm-av"><?=$initial?></div>
    <div>
      <div class="nbm-uname"><?=$username?></div>
      <div class="nbm-role"><?=$isAdmin ? '⚡ ADMINISTRATOR' : '● MEMBER'?></div>
    </div>
    <div class="nbm-dot"></div>
  </div>
  <?php endif; ?>

  <div class="nbm-nav">

    <div class="nbm-sec" style="--nd:.20s">เมนูหลัก</div>

    <?php foreach ($nav as $ni => [$lbl,$url,$ic,$need]):
      $on = nbOn($url);
      $nd = round(0.24 + $ni * 0.055, 3) . 's';
      if ($need && !$loggedIn): ?>
        <a href="#" class="nbm-item" style="--nd:<?=$nd?>" onclick="nbClose();showModal();return false;">
          <span class="nbm-ic"><i class="fas <?=$ic?>"></i></span>
          <span class="nbm-label"><?=$lbl?></span>
          <i class="fas fa-lock" style="font-size:.7rem;color:#374151;"></i>
        </a>
      <?php else: ?>
        <a href="<?=$url?>" class="nbm-item <?=$on?>" style="--nd:<?=$nd?>" onclick="nbClose()">
          <span class="nbm-ic"><i class="fas <?=$ic?>"></i></span>
          <span class="nbm-label"><?=$lbl?></span>
        </a>
      <?php endif;
    endforeach; ?>

    <?php if ($loggedIn): ?>

    <div class="nbm-sec" style="--nd:.46s">บัญชีของฉัน</div>

    <?php if ($isAdmin): ?>
    <a href="dashboard.php" class="nbm-item <?=nbOn('dashboard.php')?>" style="--nd:.51s" onclick="nbClose()">
      <span class="nbm-ic"><i class="fas fa-gauge-high"></i></span>
      <span class="nbm-label">Dashboard</span>
      <span class="nbm-badge">ADMIN</span>
    </a>
    <?php endif; ?>

    <a href="setting.php" class="nbm-item <?=nbOn('setting.php')?>" style="--nd:.56s" onclick="nbClose()">
      <span class="nbm-ic"><i class="fas fa-gear"></i></span>
      <span class="nbm-label">Settings</span>
    </a>

    <?php else: ?>

    <div class="nbm-sec" style="--nd:.46s">เข้าสู่ระบบ</div>
    <a href="login.php"    class="nbm-item" style="--nd:.51s" onclick="nbClose()">
      <span class="nbm-ic"><i class="fas fa-right-to-bracket"></i></span>
      <span class="nbm-label">เข้าสู่ระบบ</span>
    </a>
    <a href="register.php" class="nbm-item" style="--nd:.56s" onclick="nbClose()">
      <span class="nbm-ic"><i class="fas fa-user-plus"></i></span>
      <span class="nbm-label">สมัครสมาชิก</span>
    </a>

    <?php endif; ?>
  </div>

  <?php if ($loggedIn): ?>
  <div class="nbm-footer">
    <a href="logout.php" class="nbm-logout" style="--nd:.60s" onclick="nbClose()">
      <span class="nbm-ic"><i class="fas fa-right-from-bracket"></i></span>
      <span>ออกจากระบบ</span>
    </a>
  </div>
  <?php endif; ?>

</div>

<script>
(function(){
  const navbar = document.getElementById('navbar');
  const bar    = document.getElementById('nbBar');
  const ham    = document.getElementById('nbHam');
  const mobile = document.getElementById('nbMobile');
  const mClose = document.getElementById('nbMClose');

  /* ── scroll effect ── */
  window.addEventListener('scroll', function(){
    bar.classList.toggle('scrolled', window.scrollY > 8);
  }, { passive: true });

  /* ──────────────────────────────────────────
     nbPushDown / nbPopUp
     เรียกจาก projects.php เมื่อเปิด/ปิด modal
     เพื่อให้ navbar ไม่บังเนื้อหา

     การใช้งานใน projects.php:
       เปิด modal  → nbPushDown()
       ปิด modal   → nbPopUp()
  ────────────────────────────────────────── */
  window.nbPushDown = function() {
    navbar.classList.add('nb-behind');
  };
  window.nbPopUp = function() {
    navbar.classList.remove('nb-behind');
  };

  /* ── OPEN mobile menu ── */
  function nbOpen() {
    mobile.querySelectorAll('.nbm-user,.nbm-item,.nbm-logout,.nbm-sec').forEach(function(el){
      el.style.animationName = 'none';
    });
    void mobile.offsetWidth;
    mobile.querySelectorAll('.nbm-user,.nbm-item,.nbm-logout,.nbm-sec').forEach(function(el){
      el.style.animationName = '';
    });
    mobile.classList.add('open');
    ham.classList.add('is-open');
    mobile.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
  }

  /* ── CLOSE mobile menu ── */
  window.nbClose = function() {
    if (!mobile.classList.contains('open')) return;
    mobile.classList.remove('open');
    ham.classList.remove('is-open');
    mobile.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  };

  ham.addEventListener('click', function(e){
    e.stopPropagation();
    if (mobile.classList.contains('open')) { nbClose(); } else { nbOpen(); }
  });

  if (mClose) {
    mClose.addEventListener('click', function(e){
      e.stopPropagation();
      nbClose();
    });
  }

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') nbClose();
  });

  document.querySelectorAll('img').forEach(function(img){
    img.setAttribute('decoding','async');
  });
})();
</script>