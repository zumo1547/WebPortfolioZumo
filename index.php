<?php
/* ╔══════════════════════════════════════════════════════════════╗
   ║  SECURITY LAYER — ต้องอยู่ก่อน output ทุกอย่าง              ║
   ╚══════════════════════════════════════════════════════════════╝ */

/* ── 1. Security Headers ── */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: "
    . "default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.tailwindcss.com; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
    . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; "
    . "img-src 'self' data: blob:; "
    . "media-src 'self'; "
    . "connect-src 'self'; "
    . "frame-ancestors 'self';"
);

/* ── 2. Rate Limiting — ป้องกัน reload spam / DDoS เบื้องต้น ──
   60 requests / 60 วิ / session  →  เกินแล้วแสดง 429 + sleep 2s  */
session_start();

define('RL_LIMIT',  60);
define('RL_WINDOW', 60);

$_now = time();
if (empty($_SESSION['rl_ts']) || $_now - $_SESSION['rl_ts'] > RL_WINDOW) {
    $_SESSION['rl_ts']  = $_now;
    $_SESSION['rl_cnt'] = 0;
}
$_SESSION['rl_cnt']++;

if ($_SESSION['rl_cnt'] > RL_LIMIT) {
    sleep(2);   /* penalise automated tools */
    http_response_code(429);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>429</title>
    <style>body{background:#03030a;color:#eeeef8;font-family:monospace;display:flex;align-items:center;justify-content:center;height:100vh;text-align:center;}
    h1{font-size:3rem;color:#c8ff57;}p{color:rgba(238,238,248,.5);margin-top:.5rem;}</style>
    </head><body><div><h1>429</h1><p>Too Many Requests<br><small>กรุณารอสักครู่แล้วลองใหม่</small></p></div></body></html>';
    exit;
}

/* ── 3. CSRF Token (ใช้ใน form ถ้ามีในหน้าอื่น) ── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ── 4. Config + Auth ── */
include 'config.php';
$logged_in = isset($_SESSION['user_id']);
/* ป้องกัน XSS: escape ทุก output */
$username  = $logged_in ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : 'visitors';
$boot_key  = $logged_in ? 'nexus3_u' . (int)$_SESSION['user_id'] : 'nexus3_v';
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zumo Dev — Portfolio</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" type="image/png" href="assets/Icon portfolio.png">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=JetBrains+Mono:wght@300;400;500;700&family=Kanit:wght@300;400;700;800;900&family=Noto+Sans+Thai:wght@300;400;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
/* ═══ ROOT ═══ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
:root{
  --bg:#03030a; --lime:#c8ff57; --lime2:#a3f020;
  --cyan:#00e5ff; --purple:#a855f7; --pink:#ec4899;
  --white:#eeeef8; --dim:rgba(238,238,248,.42); --dimmer:rgba(238,238,248,.2);
  --border:rgba(255,255,255,.07); --border2:rgba(255,255,255,.13);
  --fd:'Bebas Neue',Impact,sans-serif; --fm:'JetBrains Mono',monospace; --fb:'Kanit','Noto Sans Thai',sans-serif;
}
body{background:var(--bg);color:var(--white);font-family:var(--fb);overflow-x:hidden;cursor:none;}
body.boot-active>*:not(#os){opacity:0!important;pointer-events:none!important;}
body:not(.boot-active)>*:not(#os){transition:opacity .5s ease .15s;}

/* ══ CURSOR — desktop เท่านั้น ══ */
#cur,#cur-r{
  position:fixed;pointer-events:none;z-index:9999;
  transform:translate(-50%,-50%);will-change:transform;
}
#cur{
  width:8px;height:8px;border-radius:50%;
  background:var(--lime);mix-blend-mode:difference;
  transition:width .18s,height .18s;
}
#cur-r{
  width:32px;height:32px;border-radius:50%;
  border:1px solid rgba(200,255,87,.3);z-index:9998;
  transition:width .3s,height .3s,border-color .22s;
}
body:has(a:hover,button:hover) #cur{width:14px;height:14px;}
body:has(a:hover,button:hover) #cur-r{width:48px;height:48px;border-color:rgba(200,255,87,.65);}

/* ── ซ่อน cursor บน touch / mobile ทุกกรณี ── */
@media (hover:none),(pointer:coarse){
  #cur,#cur-r{display:none!important;}
  body{cursor:auto!important;}
}

/* NOISE */
.noise{position:fixed;inset:0;pointer-events:none;z-index:1;opacity:.022;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");}

/* BOOT */
#os{position:fixed;inset:0;z-index:9999;background:var(--bg);display:flex;flex-direction:column;font-family:var(--fm);overflow:hidden;}
#os::after{content:'';position:absolute;inset:0;pointer-events:none;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,.05) 2px,rgba(0,0,0,.05) 4px);z-index:2;}
#os.leaving{animation:osOut .85s cubic-bezier(.77,0,1,1) forwards;}
@keyframes osOut{0%{opacity:1;transform:scaleY(1);filter:brightness(1);}40%{filter:brightness(3);}60%{transform:scaleY(.006);}100%{transform:scaleY(0);opacity:0;}}
#os-bar{height:36px;background:rgba(3,3,10,.98);border-bottom:1px solid rgba(200,255,87,.1);display:flex;align-items:center;padding:0 16px;gap:8px;flex-shrink:0;}
.td{width:10px;height:10px;border-radius:50%;}.td-r{background:#ff5f57;box-shadow:0 0 5px #ff5f57;}.td-y{background:#febc2e;box-shadow:0 0 5px #febc2e;}.td-g{background:#28c840;box-shadow:0 0 5px #28c840;}
#bar-t{font-size:.55rem;color:rgba(255,255,255,.22);letter-spacing:.14em;flex:1;}
#bar-c{font-size:.52rem;color:rgba(200,255,87,.4);letter-spacing:.1em;}
#os-body{flex:1;padding:22px 26px;display:flex;flex-direction:column;overflow:hidden;}
.pl{font-size:clamp(.48rem,1.2vw,.63rem);line-height:2;letter-spacing:.02em;color:rgba(255,255,255,.3);opacity:0;transition:opacity .1s;}
.pl.on{opacity:1;}.pl.hi{color:rgba(200,255,87,.7);}.pl.ok{color:rgba(0,255,136,.55);}.pl.wn{color:rgba(251,191,36,.55);}
.bcu{display:inline-block;width:7px;height:.82em;background:var(--lime);vertical-align:middle;margin-left:2px;animation:blk .7s step-end infinite;}
@keyframes blk{0%,100%{opacity:1}50%{opacity:0}}
#os-foot{margin-top:auto;padding-top:16px;}
#os-pb-lbl{font-size:.48rem;letter-spacing:.2em;color:rgba(200,255,87,.4);margin-bottom:6px;}
#os-pb{height:2px;background:rgba(255,255,255,.05);border-radius:2px;overflow:hidden;margin-bottom:4px;}
#os-pb-f{height:100%;width:0;background:linear-gradient(90deg,var(--lime2),var(--cyan));border-radius:2px;transition:width .4s cubic-bezier(.4,0,.2,1);}
#os-pb-p{font-size:.44rem;color:rgba(200,255,87,.3);letter-spacing:.1em;}

/* HERO */
#hero{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;z-index:2;}
#hero-canvas{position:absolute;inset:0;width:100%;height:100%;z-index:0;}
#hero-vig{position:absolute;inset:0;z-index:1;background:radial-gradient(ellipse 65% 65% at 50% 50%,transparent 25%,rgba(3,3,10,.7) 100%);}
.hero-inner{position:relative;z-index:3;text-align:center;padding:0 1.5rem;max-width:1100px;width:100%;}
.h-badge{display:inline-flex;align-items:center;gap:8px;font-family:var(--fm);font-size:.56rem;letter-spacing:.22em;color:rgba(200,255,87,.65);background:rgba(200,255,87,.06);border:1px solid rgba(200,255,87,.18);padding:5px 14px;border-radius:2px;margin-bottom:2rem;opacity:0;transform:translateY(10px);animation:fup .6s ease .2s forwards;}
.h-badge::before{content:'';width:5px;height:5px;border-radius:50%;background:var(--lime);box-shadow:0 0 8px var(--lime);animation:blk .9s step-end infinite;}
@keyframes fup{to{opacity:1;transform:translateY(0);}}
.ktitle{font-family:var(--fd);font-size:clamp(4.5rem,13vw,11rem);line-height:.88;letter-spacing:-.01em;color:var(--white);margin-bottom:.4rem;}
.ktitle .ch{display:inline-block;opacity:0;transform:translateY(110%);animation:chUp .7s cubic-bezier(.22,1,.36,1) forwards;}
.ktitle .sp{display:inline-block;width:.22em;}
.ktitle .ac{color:transparent;-webkit-text-stroke:1.5px var(--lime);}
@keyframes chUp{to{opacity:1;transform:translateY(0);}}
.h-sub{font-family:var(--fm);font-size:clamp(.65rem,1.4vw,.85rem);letter-spacing:.16em;color:var(--dim);margin-bottom:2.2rem;opacity:0;animation:fup .6s ease .9s forwards;}
.h-sub em{color:var(--lime);font-style:normal;}
.chips{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-bottom:2.5rem;opacity:0;animation:fup .6s ease 1.05s forwards;}
.chip{font-family:var(--fm);font-size:.58rem;letter-spacing:.12em;padding:5px 12px;border-radius:2px;border:1px solid;transition:all .22s ease;cursor:default;}
.chip:hover{transform:translateY(-2px);filter:brightness(1.3);}
.cg{color:var(--lime);border-color:rgba(200,255,87,.25);background:rgba(200,255,87,.05);}
.cc{color:var(--cyan);border-color:rgba(0,229,255,.25);background:rgba(0,229,255,.05);}
.cp{color:var(--pink);border-color:rgba(236,72,153,.25);background:rgba(236,72,153,.05);}
.ca{color:var(--purple);border-color:rgba(168,85,247,.25);background:rgba(168,85,247,.05);}
.ctas{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;opacity:0;animation:fup .6s ease 1.2s forwards;}
.btn-p{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-radius:3px;background:var(--lime);color:#050508;font-family:var(--fm);font-size:.7rem;letter-spacing:.14em;font-weight:700;text-decoration:none;border:none;cursor:pointer;transition:all .25s cubic-bezier(.34,1.4,.64,1);position:relative;overflow:hidden;}
.btn-p::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent);transform:translateX(-100%);transition:transform .4s ease;}
.btn-p:hover{transform:translateY(-2px) scale(1.02);box-shadow:0 8px 32px rgba(200,255,87,.28);}
.btn-p:hover::after{transform:translateX(100%);}
.btn-g{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border-radius:3px;background:transparent;color:var(--white);font-family:var(--fm);font-size:.7rem;letter-spacing:.14em;border:1px solid rgba(255,255,255,.18);text-decoration:none;cursor:pointer;transition:all .22s ease;}
.btn-g:hover{border-color:rgba(255,255,255,.42);background:rgba(255,255,255,.05);transform:translateY(-2px);}
.scroller{position:absolute;bottom:2.2rem;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:6px;font-family:var(--fm);font-size:.4rem;letter-spacing:.22em;color:var(--dimmer);opacity:0;animation:fup .5s ease 1.8s forwards;}
.scr-line{width:1px;height:48px;background:linear-gradient(to bottom,rgba(200,255,87,.5),transparent);animation:scrl 1.8s ease-in-out infinite;}
@keyframes scrl{0%{transform:scaleY(0);transform-origin:top;}50%{transform:scaleY(1);transform-origin:top;}51%{transform:scaleY(1);transform-origin:bottom;}100%{transform:scaleY(0);transform-origin:bottom;}}

/* MARQUEE */
.mq-wrap{position:relative;z-index:2;overflow:hidden;padding:13px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);background:rgba(200,255,87,.02);}
.mq-track{display:flex;animation:mqS 24s linear infinite;white-space:nowrap;}
.mq-track:hover{animation-play-state:paused;}
@keyframes mqS{from{transform:translateX(0);}to{transform:translateX(-50%);}}
.mq-item{display:inline-flex;align-items:center;gap:10px;padding:0 22px;font-family:var(--fm);font-size:.58rem;letter-spacing:.18em;color:rgba(200,255,87,.32);}
.mq-dot{width:3px;height:3px;border-radius:50%;background:rgba(200,255,87,.3);}

/* SHARED LAYOUT */
.wrap{max-width:1120px;margin:0 auto;padding:0 1.5rem;}
.sec-lbl{font-family:var(--fm);font-size:.56rem;letter-spacing:.24em;color:rgba(200,255,87,.5);margin-bottom:.6rem;display:flex;align-items:center;gap:8px;}
.sec-lbl::before{content:'//';color:rgba(200,255,87,.28);}
.sec-h{font-family:var(--fd);font-size:clamp(2.8rem,7vw,5.5rem);line-height:.9;letter-spacing:.01em;margin-bottom:3rem;color:var(--white);}
.sec-h .hl{color:var(--lime);}

/* SCROLL REVEAL */
.sr{opacity:0;transform:translateY(24px);transition:opacity .9s cubic-bezier(.16,1,.3,1),transform .9s cubic-bezier(.16,1,.3,1);will-change:transform,opacity;}
.sr.vis{opacity:1;transform:none;}
.sr.d1{transition-delay:.08s;}.sr.d2{transition-delay:.16s;}.sr.d3{transition-delay:.24s;}.sr.d4{transition-delay:.32s;}.sr.d5{transition-delay:.40s;}

/* TECH STACK */
.stack-sec{padding:6rem 0;}
.tech-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:10px;}
@media(max-width:900px){.tech-grid{grid-template-columns:repeat(4,1fr);}}
@media(max-width:500px){.tech-grid{grid-template-columns:repeat(4,1fr);gap:7px;}}

/* 3D Tilt Card */
.tilt-card{background:rgba(255,255,255,.028);border:1px solid var(--border);border-radius:16px;padding:clamp(14px,3vw,22px) 12px clamp(12px,2.5vw,18px);display:flex;flex-direction:column;align-items:center;gap:9px;position:relative;overflow:hidden;cursor:default;transform-style:preserve-3d;transition:border-color .3s ease,box-shadow .3s ease,background .3s ease,transform .08s linear;will-change:transform;}
.tilt-card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);opacity:0;transition:opacity .3s;}
.tilt-card:hover::before{opacity:1;}
.tilt-card::after{content:'';position:absolute;inset:0;border-radius:16px;background:radial-gradient(circle at var(--mx,50%) var(--my,50%),rgba(255,255,255,.07) 0%,transparent 60%);opacity:0;transition:opacity .3s;pointer-events:none;z-index:0;}
.tilt-card:hover::after{opacity:1;}
.tilt-card.glow{border-color:var(--tc-c,rgba(200,255,87,.3));box-shadow:0 24px 60px rgba(0,0,0,.45),0 0 40px var(--tc-g,rgba(200,255,87,.1));}
.tc-lua{--tc-c:rgba(100,120,255,.4);--tc-g:rgba(80,110,220,.1);}
.tc-cs{--tc-c:rgba(180,100,255,.4);--tc-g:rgba(160,80,240,.1);}
.tc-esp{--tc-c:rgba(210,60,60,.4);--tc-g:rgba(200,50,50,.1);}
.tc-blynk{--tc-c:rgba(0,200,240,.4);--tc-g:rgba(0,190,230,.1);}
.tc-py{--tc-c:rgba(255,210,50,.35);--tc-g:rgba(250,200,40,.1);}
.tc-react{--tc-c:rgba(97,218,251,.4);--tc-g:rgba(90,210,245,.1);}
.tc-go{--tc-c:rgba(0,180,215,.4);--tc-g:rgba(0,170,205,.1);}
.tc-php{--tc-c:rgba(140,143,220,.4);--tc-g:rgba(130,135,210,.1);}
.tc-ico{width:clamp(44px,7.5vw,58px);height:clamp(44px,7.5vw,58px);border-radius:13px;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.06);position:relative;z-index:1;flex-shrink:0;transition:transform .3s cubic-bezier(.34,1.4,.64,1);}
.tilt-card:hover .tc-ico{transform:scale(1.1) translateZ(8px) translateY(-2px);}
.tc-ico svg{width:clamp(24px,4.2vw,32px);height:clamp(24px,4.2vw,32px);display:block;}
.tc-nm{font-family:var(--fm);font-size:clamp(.5rem,1.2vw,.64rem);font-weight:600;letter-spacing:.05em;color:rgba(255,255,255,.75);position:relative;z-index:1;white-space:nowrap;}
.tc-sb{font-family:var(--fm);font-size:clamp(.35rem,.8vw,.44rem);letter-spacing:.12em;color:rgba(255,255,255,.2);text-transform:uppercase;margin-top:-4px;white-space:nowrap;position:relative;z-index:1;}
.tilt-card:hover .tc-nm{color:var(--white);}
.tilt-card:hover .tc-sb{color:rgba(255,255,255,.38);}
@media(max-width:500px){.tilt-card{padding:12px 8px 10px;border-radius:12px;gap:7px;}.tc-ico{border-radius:10px;}}

/* BENTO */
.bento-sec{padding:0 0 6rem;}
.b-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px;}
.bc{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:20px;overflow:hidden;position:relative;transition:transform .5s cubic-bezier(.16,1,.3,1),box-shadow .4s ease,border-color .3s ease;will-change:transform;}
.bc:hover{transform:translateY(-4px);border-color:var(--border2);box-shadow:0 20px 50px rgba(0,0,0,.3);}
.bc::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;opacity:0;transition:opacity .3s;}
.bc:hover::before{opacity:1;}
.bc-p{padding:1.6rem 1.8rem;}
.b7{grid-column:span 7;}.b5{grid-column:span 5;}.b8{grid-column:span 8;}.b4{grid-column:span 4;}.b6{grid-column:span 6;}.b12{grid-column:span 12;}
@media(max-width:900px){.b7,.b5,.b8,.b4{grid-column:span 6;}.b6{grid-column:span 12;}}
@media(max-width:600px){.b-grid{gap:8px;}.b7,.b5,.b8,.b4,.b6{grid-column:span 12;}}
.bl::before{background:linear-gradient(90deg,var(--lime2),var(--lime));}
.bct::before{background:linear-gradient(90deg,#0099cc,var(--cyan));}
.bpu::before{background:linear-gradient(90deg,#7c3aed,var(--purple),var(--pink));}
.bpk::before{background:linear-gradient(90deg,var(--pink),#f43f5e);}
.b-num{font-family:var(--fd);font-size:clamp(3rem,8vw,5.5rem);line-height:1;letter-spacing:.02em;}
.b-lbl{font-family:var(--fm);font-size:.58rem;letter-spacing:.18em;color:var(--dim);margin-top:.4rem;}
.b-sub{font-size:.52rem;letter-spacing:.12em;color:var(--dimmer);margin-top:.12rem;}
.b-tag{font-family:var(--fm);font-size:.5rem;letter-spacing:.2em;padding:3px 9px;border-radius:2px;display:inline-block;margin-bottom:.9rem;}
.sk-row{display:flex;justify-content:space-between;font-family:var(--fm);font-size:.58rem;letter-spacing:.07em;color:var(--dim);margin-bottom:.38rem;}
.sk-row span:last-child{color:var(--lime);}
.sk-t{height:3px;background:rgba(255,255,255,.05);border-radius:2px;overflow:hidden;margin-bottom:.85rem;}
.sk-f{height:100%;border-radius:2px;width:0;transition:width 1.4s cubic-bezier(.16,1,.3,1);}
.sk-l{background:linear-gradient(90deg,var(--lime2),var(--lime));}
.sk-c{background:linear-gradient(90deg,#0099cc,var(--cyan));}
.sk-o{background:linear-gradient(90deg,#e8630a,#f97316);}
.sk-p{background:linear-gradient(90deg,#7c3aed,var(--purple));}
.b-quote{font-family:'Kanit',sans-serif;font-size:clamp(.95rem,2.3vw,1.4rem);font-weight:700;line-height:1.45;color:var(--white);}
.b-quote em{color:var(--lime);font-style:normal;}
.live-b{display:inline-flex;align-items:center;gap:5px;font-family:var(--fm);font-size:.45rem;letter-spacing:.15em;color:rgba(0,255,136,.6);background:rgba(0,255,136,.06);border:1px solid rgba(0,255,136,.18);padding:3px 9px;border-radius:2px;}
.ld{width:5px;height:5px;border-radius:50%;background:#00ff88;box-shadow:0 0 5px #00ff88;animation:blk .9s step-end infinite;}
.b-neural{grid-column:span 5;min-height:220px;padding:0!important;background:rgba(3,3,10,.85)!important;border-color:rgba(168,85,247,.15)!important;}
@media(max-width:900px){.b-neural{grid-column:span 12;min-height:200px;}}
#neural-canvas{width:100%;height:100%;display:block;}
.ag{display:grid;grid-template-columns:repeat(26,1fr);gap:3px;margin-top:.8rem;}
.ag-c{aspect-ratio:1;border-radius:2px;background:var(--cc,rgba(200,255,87,.05));transition:background .18s,transform .12s;}
.ag-c:hover{transform:scale(1.4);}

/* WAVE */
#wave-section{position:relative;z-index:2;height:200px;overflow:hidden;}
#wave-canvas{width:100%;height:100%;display:block;}
.wave-txt{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--fd);font-size:clamp(1.2rem,4vw,2.8rem);letter-spacing:.25em;color:rgba(200,255,87,.15);pointer-events:none;}

/* SCROLLYTELLING */
.story-sec{position:relative;z-index:2;padding:6rem 0;}
.story-step{display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;padding:4.5rem 0;}
.story-step>div:not(.sv),.story-step>.sv{opacity:0;transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1);}
.story-step>div:not(.sv){transform:translateX(-32px);}
.story-step>.sv{transform:translateX(32px);transition-delay:.08s;}
.story-step:nth-child(even)>div:not(.sv){transform:translateX(32px);}
.story-step:nth-child(even)>.sv{transform:translateX(-32px);}
#st2>.sv{transform:none!important;transition:opacity .85s cubic-bezier(.16,1,.3,1) .15s!important;}
#st2>div:not(.sv){transition-delay:.05s;}
.story-step.s-in>div:not(.sv),.story-step.s-in>.sv{opacity:1;transform:translateX(0)!important;}
.story-step.s-out>div:not(.sv),.story-step.s-out>.sv{opacity:0!important;transform:translateY(12px)!important;transition:opacity .35s ease,transform .35s ease!important;}
#st2.s-out>.sv{transform:none!important;}
#robot-canvas{position:absolute;inset:0;width:100%;height:100%;border-radius:20px;will-change:contents;transform:translateZ(0);backface-visibility:hidden;}
@media(max-width:768px){.story-step{grid-template-columns:1fr;gap:2rem;}}
.story-step:nth-child(even) .sv{order:-1;}
@media(max-width:768px){.story-step:nth-child(even) .sv{order:0;}}
.si{font-family:var(--fm);font-size:.48rem;letter-spacing:.24em;color:var(--dimmer);margin-bottom:.7rem;display:flex;align-items:center;gap:8px;}
.si::before{content:'';flex:0 0 22px;height:1px;background:var(--dimmer);}
.slbl{font-family:var(--fm);font-size:.56rem;letter-spacing:.2em;padding:4px 10px;border-radius:2px;margin-bottom:.9rem;display:inline-block;}
.stitle{font-family:var(--fd);font-size:clamp(2rem,5vw,3.8rem);line-height:.95;letter-spacing:.01em;color:var(--white);margin-bottom:1.1rem;}
.sbody{font-family:'Noto Sans Thai',sans-serif;font-size:clamp(.85rem,1.7vw,.98rem);color:var(--dim);line-height:1.85;}
.sbody strong{color:var(--white);}
.shi{font-family:var(--fm);font-size:.58rem;color:var(--lime);letter-spacing:.08em;margin-top:.9rem;padding-top:.9rem;border-top:1px solid rgba(200,255,87,.15);}
.sv{position:relative;aspect-ratio:1/.85;border-radius:20px;overflow:hidden;background:rgba(255,255,255,.018);border:1px solid var(--border);}
.sv-in{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1.4rem;}
.code-blk{background:rgba(0,0,0,.65);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:.9rem 1.1rem;font-family:var(--fm);font-size:clamp(.5rem,1.1vw,.62rem);line-height:1.95;width:100%;text-align:left;}
.kw{color:#c792ea;}.fn{color:var(--lime);}.str{color:#f78c6c;}.cm{color:#546e7a;}.vr{color:var(--cyan);}

/* CTA */
.cta-sec{position:relative;z-index:2;padding:4rem 0 7rem;text-align:center;}
.cta-h{font-family:var(--fd);font-size:clamp(3.5rem,11vw,8rem);line-height:.88;letter-spacing:.01em;margin-bottom:1.4rem;}
.cta-ol{color:transparent;-webkit-text-stroke:1.5px var(--lime);}
.cta-sub{font-size:clamp(.9rem,1.9vw,1rem);color:var(--dim);margin-bottom:2.2rem;line-height:1.75;}
.cta-div{width:50px;height:1px;background:linear-gradient(90deg,transparent,var(--lime),transparent);margin:1.8rem auto;}

footer{position:relative;z-index:2;border-top:1px solid var(--border);padding:2rem 1.5rem;text-align:center;}
.ft{font-family:var(--fm);font-size:.5rem;color:var(--dimmer);letter-spacing:.12em;}

/* SCROLL TOP */
#stb{position:fixed;bottom:-80px;right:1.5rem;z-index:50;width:42px;height:42px;border-radius:50%;border:1px solid rgba(200,255,87,.22);background:rgba(200,255,87,.05);color:var(--lime);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:bottom .4s ease,background .2s;font-size:.78rem;}
#stb.vis{bottom:1.5rem;}#stb:hover{background:rgba(200,255,87,.14);}
.modal-bg{background:rgba(0,0,0,.75);backdrop-filter:blur(6px);}
.cookie-banner{background:rgba(3,3,10,.97);border-top:1px solid rgba(200,255,87,.1);z-index:300!important;}

/* MUSIC FAB */
#mp-fab{position:fixed;bottom:1.4rem;left:1.4rem;z-index:200;width:42px;height:42px;border-radius:50%;border:1px solid rgba(200,255,87,.22);background:rgba(200,255,87,.06);color:var(--lime);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.82rem;transition:background .2s,transform .2s;}
#mp-fab:hover{background:rgba(200,255,87,.14);transform:scale(1.08);}
#mp-fab.playing{border-color:rgba(200,255,87,.5);}
#mp-fab.playing::before{content:'';position:absolute;inset:-6px;border-radius:50%;border:1px solid rgba(200,255,87,.28);animation:fp 2s ease-in-out infinite;}
@keyframes fp{0%{transform:scale(1);opacity:.7}100%{transform:scale(1.45);opacity:0}}
#mp-panel{position:fixed;bottom:calc(1.4rem + 42px + 10px);left:1.4rem;z-index:199;width:min(300px,calc(100vw - 36px));background:rgba(3,3,14,.97);border:1px solid rgba(200,255,87,.14);border-radius:16px;backdrop-filter:blur(24px);box-shadow:0 20px 60px rgba(0,0,0,.7);transform:translateY(8px) scale(.97);opacity:0;pointer-events:none;transition:transform .28s cubic-bezier(.34,1.3,.64,1),opacity .22s ease;overflow:hidden;}
#mp-panel.open{transform:none;opacity:1;pointer-events:auto;}
#mp-tb{height:2px;background:linear-gradient(90deg,var(--lime2),var(--cyan),var(--purple),var(--lime2));background-size:300%;animation:mpb 4s linear infinite;}
@keyframes mpb{from{background-position:0%}to{background-position:300%}}
#mp-in{padding:13px 15px 15px;}
#mp-ti{font-family:var(--fd);font-size:.95rem;color:var(--white);letter-spacing:.05em;}
#mp-ar{font-family:var(--fm);font-size:.43rem;color:rgba(200,255,87,.48);letter-spacing:.1em;margin:2px 0 11px;}
#mp-trk{height:2px;background:rgba(255,255,255,.06);border-radius:2px;cursor:pointer;margin-bottom:4px;overflow:hidden;}
#mp-fi{height:100%;width:0;background:linear-gradient(90deg,var(--lime2),var(--lime));border-radius:2px;transition:width .5s linear;}
#mp-ts{display:flex;justify-content:space-between;font-family:var(--fm);font-size:.38rem;color:var(--dimmer);margin-bottom:11px;}
#mp-ctrl{display:flex;align-items:center;gap:8px;}
#mp-vr{display:flex;align-items:center;gap:6px;flex:1;}
#mp-vi{color:var(--dimmer);font-size:.58rem;}
#mp-vol{flex:1;-webkit-appearance:none;height:2px;border-radius:2px;cursor:pointer;outline:none;border:none;background:linear-gradient(90deg,rgba(200,255,87,.5) var(--vol,50%),rgba(255,255,255,.06) var(--vol,50%));}
#mp-vol::-webkit-slider-thumb{-webkit-appearance:none;width:10px;height:10px;border-radius:50%;background:var(--lime);cursor:pointer;}
#mp-vp{font-family:var(--fm);font-size:.38rem;color:var(--dimmer);width:22px;text-align:right;}
#mp-btn{width:34px;height:34px;border-radius:50%;border:1px solid rgba(200,255,87,.3);background:rgba(200,255,87,.08);color:var(--lime);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.73rem;transition:all .18s ease;}
#mp-btn:hover{background:rgba(200,255,87,.18);}
@media(max-width:480px){#mp-fab{bottom:1rem;left:1rem;}#mp-panel{bottom:calc(1rem + 42px + 8px);left:1rem;}#stb.vis{bottom:1rem;}#stb{right:1rem;}}
</style>
</head>
<body class="boot-active">

<?php /* cursor ซ่อนบน touch ด้วย CSS แล้ว แต่ div ยังอยู่เผื่อ JS ใช้ */ ?>
<div id="cur"></div>
<div id="cur-r"></div>
<div class="noise"></div>

<!-- ════ BOOT SCREEN ════ -->
<div id="os">
  <div id="os-bar">
    <div class="td td-r"></div><div class="td td-y"></div><div class="td td-g"></div>
    <div id="bar-t">NEXUS OS 3.0 — zumo-dev.portfolio — webgl boot sequence</div>
    <div id="bar-c">00:00:00</div>
  </div>
  <div id="os-body">
    <div class="pl hi" id="l0">NEXUS OS  kernel 6.1.0  [ZUMO-DEV-WEBGL]</div>
    <div class="pl"    id="l1">Copyright (c) 2025 ZUMO DEV · All rights reserved.</div>
    <div class="pl"    id="l2">&nbsp;</div>
    <div class="pl ok" id="l3">[  OK  ]  Starting system initialization...</div>
    <div class="pl ok" id="l4">[  OK  ]  Creative-i9  MEM: 16384MB</div>
    <div class="pl ok" id="l5">[  OK  ]  Mounted  /dev/portfolio  [rw]</div>
    <div class="pl wn" id="l6">[ WAIT ]  Initializing WebGL 2.0 renderer...</div>
    <div class="pl ok" id="l7">[  OK  ]  GPU: online  GLSL: 3.30  Shaders: ready</div>
    <div class="pl ok" id="l8">[  OK  ]  Particle system: 8192 pts</div>
    <div class="pl ok" id="l9">[  OK  ]  Game + IoT + Robotics + AI: online</div>
    <div class="pl"    id="la">&nbsp;</div>
    <div class="pl hi" id="lb">Launching graphical interface<span class="bcu"></span></div>
    <div id="os-foot">
      <div id="os-pb-lbl">LOADING WEBGL EXPERIENCE</div>
      <div id="os-pb"><div id="os-pb-f"></div></div>
      <div id="os-pb-p">0%</div>
    </div>
  </div>
</div>

<?php include 'navbar.php'; ?>

<!-- ════ HERO ════ -->
<section id="hero">
  <canvas id="hero-canvas"></canvas>
  <div id="hero-vig"></div>
  <div class="hero-inner">
    <div class="h-badge">PORTFOLIO · ZUMO DEV · <?= date('Y') ?> · WEBGL ENABLED</div>
    <h1 class="ktitle" id="ktitle"><span id="kn">ZUMO<span class="sp"></span><span class="ac">DEV</span></span></h1>
    <div class="h-sub">GAME &nbsp;·&nbsp; <em>IoT</em> &nbsp;·&nbsp; ROBOTICS &nbsp;·&nbsp; AI</div>
    <div class="chips">
      <span class="chip cg">🎮 Game Dev</span>
      <span class="chip cc">⚡ IoT Engineer</span>
      <span class="chip cp">🤖 Robotics</span>
      <span class="chip ca">🧠 AI Enthusiast</span>
    </div>
    <div class="ctas">
      <a href="<?= $logged_in ? 'projects.php' : '#' ?>" class="btn-p"
         onclick="<?= !$logged_in ? 'showModal();return false;' : '' ?>">
        <i class="fas fa-rocket"></i> SEE PROJECTS
      </a>
      <a href="<?= $logged_in ? 'about.php' : '#' ?>" class="btn-g"
         onclick="<?= !$logged_in ? 'showModal();return false;' : '' ?>">
        ABOUT ME →
      </a>
    </div>
    <p style="font-family:var(--fm);font-size:.6rem;color:var(--dim);margin-top:1.4rem;opacity:0;animation:fup .5s ease 1.55s forwards;">
      Welcome, <span style="color:var(--lime);"><?= $username ?></span>
    </p>
  </div>
  <div class="scroller"><div class="scr-line"></div><span>SCROLL</span></div>
</section>

<!-- MARQUEE -->
<div class="mq-wrap"><div class="mq-track" id="mq">
<?php
/* ป้องกัน XSS: escape ทุก item */
$items = ['GAME DEV','IoT SYSTEMS','WEBGL 3D','ROBLOX STUDIO','UNITY ENGINE','ESP32',
          'RASPBERRY PI','PYTHON','LUA','C#','ROBOTICS','BLYNK','AI DETECTION',
          'PHP','REACT','THREE.JS','SHADER ART'];
$d = array_merge($items, $items, $items, $items);
foreach ($d as $it) {
    echo "<span class='mq-item'><span class='mq-dot'></span>"
       . htmlspecialchars($it, ENT_QUOTES, 'UTF-8') . "</span>";
}
?>
</div></div>

<!-- TECH STACK -->
<div class="stack-sec"><div class="wrap">
  <div class="sec-lbl sr">TOOLS &amp; TECH</div>
  <h2 class="sec-h sr d1">TECH<br><span class="hl">STACK.</span></h2>
  <div class="tech-grid" id="tech-grid">
    <div class="tilt-card tc-lua sr d1">
      <div class="tc-ico" style="background:radial-gradient(135deg at 30% 30%,#1a3abf,#000060);">
        <svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="14" fill="#1a3abf"/><circle cx="22" cy="10" r="5.5" fill="rgba(255,255,255,.15)"/><circle cx="22" cy="10" r="4.8" fill="white"/><circle cx="11.5" cy="17" r="4.5" fill="rgba(255,255,255,.12)"/><circle cx="11.5" cy="17" r="3.8" fill="white" opacity=".92"/><circle cx="22" cy="23" r="4.2" fill="rgba(255,255,255,.12)"/><circle cx="22" cy="23" r="3.6" fill="white" opacity=".92"/></svg>
      </div><span class="tc-nm">Lua</span><span class="tc-sb">Roblox</span>
    </div>
    <div class="tilt-card tc-cs sr d2">
      <div class="tc-ico" style="background:linear-gradient(145deg,#1a0840,#0c0320);">
        <svg viewBox="0 0 32 32" fill="none"><defs><linearGradient id="csg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#d580ff"/><stop offset="100%" stop-color="#9333ea"/></linearGradient></defs><text x="2" y="23" font-size="17" fill="rgba(100,40,160,.5)" font-family="monospace" font-weight="900" dx=".8" dy=".8">C#</text><text x="2" y="23" font-size="17" fill="url(#csg)" font-family="monospace" font-weight="900">C#</text></svg>
      </div><span class="tc-nm">C#</span><span class="tc-sb">Unity</span>
    </div>
    <div class="tilt-card tc-esp sr d3">
      <div class="tc-ico" style="background:linear-gradient(145deg,#1e0806,#100402);">
        <svg viewBox="0 0 32 32" fill="none"><rect x="1" y="7" width="30" height="18" rx="2.5" fill="#120404" stroke="#c0392b" stroke-width=".9" stroke-opacity=".6"/><rect x="9" y="11" width="14" height="10" rx="2" fill="#c0392b"/><rect x="9" y="11" width="14" height="3" rx="2 2 0 0" fill="rgba(255,120,100,.22)"/><rect x="2" y="12.5" width="5.5" height="2" rx="1" fill="#8b1c10"/><rect x="2" y="16.5" width="5.5" height="2" rx="1" fill="#8b1c10"/><rect x="24.5" y="12.5" width="5.5" height="2" rx="1" fill="#8b1c10"/><rect x="24.5" y="16.5" width="5.5" height="2" rx="1" fill="#8b1c10"/><text x="11" y="18.5" font-size="4.5" fill="rgba(255,255,255,.85)" font-family="monospace" font-weight="bold">ESP</text></svg>
      </div><span class="tc-nm">ESP32</span><span class="tc-sb">Arduino</span>
    </div>
    <div class="tilt-card tc-blynk sr d4">
      <div class="tc-ico" style="background:linear-gradient(145deg,#002030,#001018);">
        <svg viewBox="0 0 32 32" fill="none"><defs><linearGradient id="blg" x1="0" y1="0" x2=".5" y2="1"><stop offset="0%" stop-color="#40f0ff"/><stop offset="100%" stop-color="#00aabf"/></linearGradient></defs><polygon points="19,2 11,16 16,16 13.5,30 21,14 16,14" fill="rgba(0,200,230,.2)" transform="translate(1,1)"/><polygon points="19,2 11,16 16,16 13.5,30 21,14 16,14" fill="url(#blg)"/><polygon points="19,2 15,10 16.5,10 16,16 17,14" fill="rgba(255,255,255,.28)"/></svg>
      </div><span class="tc-nm">Blynk</span><span class="tc-sb">IoT Cloud</span>
    </div>
    <div class="tilt-card tc-py sr d1">
      <div class="tc-ico" style="background:linear-gradient(145deg,#0c1824,#060e14);">
        <svg viewBox="0 0 32 32" fill="none"><defs><linearGradient id="pyb" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#5ba8d5"/><stop offset="100%" stop-color="#2d6e9e"/></linearGradient><linearGradient id="pyy" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#ffe566"/><stop offset="100%" stop-color="#ffd43b"/></linearGradient></defs><path d="M16 2C10.5 2 11 5.1 11 5.1v3.2h5.2v1.1H7.9S4.3 9 4.3 15.2 7.8 21 7.8 21h2.4v-3.3s-.1-3.9 3.9-3.9h6.4s3.6.12 3.6-3.4V6.6S23.5 2 16 2zm-1.7 2.4c.8 0 1.35.6 1.35 1.3S15.1 7 14.3 7s-1.35-.58-1.35-1.3.55-1.3 1.35-1.3z" fill="url(#pyb)"/><path d="M16 30c5.5 0 5-3.1 5-3.1v-3.2h-5.2v-1.1H24.1s3.6-.3 3.6-6.4-3.5-5.7-3.5-5.7h-2.4v3.3s.1 3.9-3.9 3.9H11.5s-3.6-.12-3.6 3.4v5.2S7.5 30 16 30zm1.7-2.4c-.8 0-1.35-.6-1.35-1.3s.55-1.3 1.35-1.3 1.35.58 1.35 1.3-.55 1.3-1.35 1.3z" fill="url(#pyy)"/></svg>
      </div><span class="tc-nm">Python</span><span class="tc-sb">Anaconda</span>
    </div>
    <div class="tilt-card tc-react sr d2">
      <div class="tc-ico" style="background:linear-gradient(145deg,#040d18,#020810);">
        <svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="3" fill="#61DAFB"/><ellipse cx="16" cy="16" rx="14" ry="5.5" stroke="#61DAFB" stroke-width="1.3" fill="none" opacity=".9"/><ellipse cx="16" cy="16" rx="14" ry="5.5" stroke="#61DAFB" stroke-width="1.3" fill="none" opacity=".9" transform="rotate(60 16 16)"/><ellipse cx="16" cy="16" rx="14" ry="5.5" stroke="#61DAFB" stroke-width="1.3" fill="none" opacity=".9" transform="rotate(120 16 16)"/></svg>
      </div><span class="tc-nm">React</span><span class="tc-sb">Frontend</span>
    </div>
    <div class="tilt-card tc-go sr d3">
      <div class="tc-ico" style="background:linear-gradient(145deg,#002535,#001420);">
        <svg viewBox="0 0 32 32" fill="none"><circle cx="11.5" cy="16" r="3.5" fill="white"/><circle cx="20.5" cy="16" r="3.5" fill="white"/><circle cx="12" cy="16" r="1.5" fill="#00ADD8"/><circle cx="21" cy="16" r="1.5" fill="#00ADD8"/><circle cx="12.6" cy="15.2" r=".5" fill="white"/><circle cx="21.6" cy="15.2" r=".5" fill="white"/><ellipse cx="16" cy="20" rx="2.5" ry="1.3" fill="rgba(0,100,130,.45)"/><line x1="5" y1="13" x2="9.5" y2="14.8" stroke="#00ADD8" stroke-width="1.5" stroke-linecap="round"/><line x1="27" y1="13" x2="22.5" y2="14.8" stroke="#00ADD8" stroke-width="1.5" stroke-linecap="round"/></svg>
      </div><span class="tc-nm">Go</span><span class="tc-sb">Backend</span>
    </div>
    <div class="tilt-card tc-php sr d4">
      <div class="tc-ico" style="background:linear-gradient(145deg,#121438,#0a0c26);">
        <svg viewBox="0 0 32 32" fill="none"><defs><linearGradient id="phpg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#c0c3f0"/><stop offset="100%" stop-color="#7b7fb5"/></linearGradient></defs><ellipse cx="16" cy="16" rx="14" ry="8" fill="rgba(123,127,181,.06)" stroke="rgba(140,143,220,.22)" stroke-width="1.2"/><text x="4.5" y="20.5" font-size="11.5" fill="rgba(70,73,140,.55)" font-family="monospace" font-weight="bold" dx=".6" dy=".6">PHP</text><text x="4.5" y="20.5" font-size="11.5" fill="url(#phpg)" font-family="monospace" font-weight="bold">PHP</text></svg>
      </div><span class="tc-nm">PHP</span><span class="tc-sb">Web / API</span>
    </div>
  </div>
</div></div>

<!-- WAVE DIVIDER -->
<div id="wave-section">
  <canvas id="wave-canvas"></canvas>
  <div class="wave-txt">GAME · IoT · ROBOTICS · AI · WEBGL</div>
</div>

<!-- BENTO -->
<div class="bento-sec"><div class="wrap">
  <div class="sec-lbl sr">NUMBERS &amp; SKILLS</div>
  <h2 class="sec-h sr d1">BY THE<br><span class="hl">NUMBERS.</span></h2>
  <div class="b-grid">
    <div class="bc b7 bl sr" style="min-height:260px;">
      <div class="bc-p" style="height:100%;display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <span class="b-tag" style="color:var(--lime);background:rgba(200,255,87,.07);border:1px solid rgba(200,255,87,.15);">PROJECTS</span>
          <div class="b-num" style="color:var(--lime);" data-target="5" data-suffix="+">0</div>
          <div class="b-lbl">โปรเจกต์ทั้งหมด</div><div class="b-sub">ALL PROJECTS</div>
        </div>
        <div>
          <div class="ag" id="ag"></div>
          <div style="font-family:var(--fm);font-size:.42rem;color:var(--dimmer);letter-spacing:.12em;margin-top:.45rem;">ACTIVITY · 2024–<?= date('Y') ?></div>
        </div>
      </div>
    </div>
    <div class="bc b5 bct sr d1">
      <div class="bc-p">
        <span class="b-tag" style="color:var(--cyan);background:rgba(0,229,255,.06);border:1px solid rgba(0,229,255,.14);">AWARDS</span>
        <div class="b-num" style="color:var(--cyan);" data-target="3" data-suffix="">0</div>
        <div class="b-lbl">รางวัลระดับจังหวัด</div><div class="b-sub">PROVINCIAL AWARDS</div>
      </div>
    </div>
    <div class="bc b-neural bpu sr d2"><canvas id="neural-canvas"></canvas></div>
    <div class="bc b7 bl sr d1">
      <div class="bc-p">
        <span class="b-tag" style="color:var(--lime);background:rgba(200,255,87,.07);border:1px solid rgba(200,255,87,.15);">PROGRAMMING SKILLS</span>
        <div style="margin-top:.7rem;">
          <div class="sk-row"><span>Lua (Roblox)</span><span>85%</span></div><div class="sk-t"><div class="sk-f sk-l" data-w="85"></div></div>
          <div class="sk-row"><span>PHP / Web</span><span>75%</span></div><div class="sk-t"><div class="sk-f sk-l" data-w="75"></div></div>
          <div class="sk-row"><span>C# (Unity)</span><span style="color:var(--purple);">70%</span></div><div class="sk-t"><div class="sk-f sk-p" data-w="70"></div></div>
          <div class="sk-row"><span>Python</span><span style="color:#f97316;">65%</span></div><div class="sk-t"><div class="sk-f sk-o" data-w="65"></div></div>
        </div>
      </div>
    </div>
    <div class="bc b5 bpu sr d2">
      <div class="bc-p">
        <span class="b-tag" style="color:var(--purple);background:rgba(168,85,247,.06);border:1px solid rgba(168,85,247,.14);">EXP</span>
        <div class="b-num" style="color:var(--purple);" data-target="2" data-suffix=" ปี">0</div>
        <div class="b-lbl">ประสบการณ์</div><div class="b-sub">EXPERIENCE</div>
      </div>
    </div>
    <div class="bc b6 bl sr">
      <div class="bc-p">
        <div class="live-b" style="margin-bottom:1rem;"><div class="ld"></div>AVAILABLE</div>
        <div class="b-quote">"I build worlds,<br>write code, and<br>bring <em>ideas to life.</em>"</div>
      </div>
    </div>
    <div class="bc b6 bct sr d1">
      <div class="bc-p">
        <span class="b-tag" style="color:var(--cyan);background:rgba(0,229,255,.06);border:1px solid rgba(0,229,255,.14);">HARDWARE &amp; IoT</span>
        <div style="margin-top:.7rem;">
          <div class="sk-row"><span>ESP32 / Arduino</span><span style="color:var(--cyan);">80%</span></div><div class="sk-t"><div class="sk-f sk-c" data-w="80"></div></div>
          <div class="sk-row"><span>Blynk / IoT</span><span style="color:var(--cyan);">78%</span></div><div class="sk-t"><div class="sk-f sk-c" data-w="78"></div></div>
          <div class="sk-row"><span>Raspberry Pi</span><span style="color:var(--cyan);">60%</span></div><div class="sk-t"><div class="sk-f sk-c" data-w="60"></div></div>
          <div class="sk-row"><span>AI / ML Detection</span><span style="color:#f97316;">55%</span></div><div class="sk-t"><div class="sk-f sk-o" data-w="55"></div></div>
        </div>
      </div>
    </div>
    <div class="bc b12 bpk sr d2">
      <div style="padding:1.6rem 1.8rem;display:flex;flex-wrap:wrap;gap:1.5rem;align-items:center;">
        <div>
          <span class="b-tag" style="color:var(--pink);background:rgba(236,72,153,.06);border:1px solid rgba(236,72,153,.14);">LANGUAGES</span>
          <div class="b-num" style="color:var(--pink);" data-target="4" data-suffix="+">0</div>
          <div class="b-lbl">ภาษาโปรแกรม</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php
          /* ป้องกัน XSS: escape ทุก lang name */
          $langs = [
              ['Lua',        'rgba(100,120,255,'],
              ['C#',         'rgba(168,85,247,'],
              ['Python',     'rgba(255,210,60,'],
              ['PHP',        'rgba(150,153,230,'],
              ['JavaScript', 'rgba(255,200,40,'],
              ['Go',         'rgba(0,200,230,'],
          ];
          foreach ($langs as [$n, $c]) {
              $safe = htmlspecialchars($n, ENT_QUOTES, 'UTF-8');
              echo "<span style=\"font-family:var(--fm);font-size:.54rem;letter-spacing:.1em;"
                 . "padding:5px 12px;border-radius:2px;"
                 . "color:{$c}.7);background:{$c}.07);border:1px solid {$c}.2);\">"
                 . "$safe</span>";
          }
          ?>
        </div>
      </div>
    </div>
  </div>
</div></div>

<!-- SCROLLYTELLING -->
<section class="story-sec"><div class="wrap">
  <div class="sec-lbl sr">DEVELOPER JOURNEY</div>
  <h2 class="sec-h sr d1" style="margin-bottom:4rem;">HOW I<br><span class="hl">GOT HERE.</span></h2>

  <div class="story-step" id="st0">
    <div>
      <div class="si">01 OF 04</div>
      <span class="slbl" style="color:var(--lime);background:rgba(200,255,87,.07);border:1px solid rgba(200,255,87,.2);">GAME DEV</span>
      <h3 class="stitle">GAME<br>DEVELOPMENT</h3>
      <p class="sbody">เชี่ยวชาญการเขียนสคริปต์ด้วย <strong>Lua</strong>, ออกแบบ UI, และสร้างระบบเกมที่ซับซ้อนใน <strong>Roblox Studio</strong>.<br><br>พัฒนาเกม 2D/3D ด้วย <strong>C#</strong>, สร้าง Game Mechanics และ AI ใน <strong>Unity</strong>.</p>
      <div class="shi">⚡ AI ChatGPT API — ไล่ล่า Humanoid อัตโนมัติ</div>
    </div>
    <div class="sv"><div class="sv-in" style="background:radial-gradient(ellipse at center,rgba(200,255,87,.04),transparent);">
      <div class="code-blk"><span class="cm">-- Roblox AI hunter</span>
<span class="kw">local</span> <span class="vr">NPC</span> = script.Parent
<span class="kw">local</span> <span class="vr">Path</span> = PathfindingService
  :<span class="fn">CreatePath</span>({ AgentRadius = 2 })

<span class="kw">function</span> <span class="fn">hunt</span>(target)
  Path:<span class="fn">ComputeAsync</span>(
    NPC.Position,
    target.Character.HumanoidRootPart.Position
  )
  <span class="kw">for</span> _, wp <span class="kw">in</span> Path:<span class="fn">GetWaypoints</span>() <span class="kw">do</span>
    NPC.Humanoid:<span class="fn">MoveTo</span>(wp.Position)
  <span class="kw">end</span>
<span class="kw">end</span></div>
    </div></div>
  </div>

  <div class="story-step" id="st1">
    <div>
      <div class="si">02 OF 04</div>
      <span class="slbl" style="color:var(--cyan);background:rgba(0,229,255,.07);border:1px solid rgba(0,229,255,.2);">IoT</span>
      <h3 class="stitle">SMART<br>SYSTEMS</h3>
      <p class="sbody"><strong>Smart Farm:</strong> ระบบรดน้ำอัตโนมัติ Real-time บน <strong>Blynk</strong><br><br><strong>Smart Home:</strong> ถังขยะอัตโนมัติด้วย <strong>Raspberry Pi 4</strong><br><br><strong>Safety System:</strong> ระบบดับเพลิงจำลองด้วย <strong>ESP32</strong></p>
      <div class="shi" style="border-color:rgba(0,229,255,.18);color:var(--cyan);">⚡ Sensors · WiFi · Real-time dashboard</div>
    </div>
    <div class="sv"><div class="sv-in" style="background:radial-gradient(ellipse at center,rgba(0,229,255,.04),transparent);">
      <div class="code-blk"><span class="cm">// ESP32 Smart Farm</span>
<span class="kw">#include</span> <span class="str">&lt;BlynkSimpleEsp32.h&gt;</span>

<span class="kw">const int</span> <span class="vr">PUMP</span> = 26;
<span class="kw">const int</span> <span class="vr">SOIL</span> = 34;

<span class="fn">BLYNK_WRITE</span>(V1) {
  <span class="kw">int</span> <span class="vr">moist</span> = <span class="fn">analogRead</span>(SOIL);
  <span class="kw">if</span> (moist &lt; 2000) {
    <span class="fn">digitalWrite</span>(PUMP, HIGH);
    Blynk.<span class="fn">logEvent</span>(<span class="str">"watering"</span>);
  }
}</div>
    </div></div>
  </div>

  <div class="story-step" id="st2">
    <div>
      <div class="si">03 OF 04</div>
      <span class="slbl" style="color:var(--pink);background:rgba(236,72,153,.07);border:1px solid rgba(236,72,153,.2);">ROBOTICS</span>
      <h3 class="stitle">HARDWARE<br>BUILDS</h3>
      <p class="sbody"><strong>Robotic Arm:</strong> ควบคุมแขนกล 6 แกนด้วย <strong>EspCam</strong><br><br><strong>Rescue Robot:</strong> หุ่นยนต์กู้ภัยไร้สาย<br><br><strong>Line Follower:</strong> เดินตามเส้นอัตโนมัติด้วย <strong>POP32i</strong></p>
      <div class="shi" style="border-color:rgba(236,72,153,.2);color:var(--pink);">⚡ Servo · Vision · Autonomous navigation</div>
    </div>
    <div class="sv" style="background:rgba(3,3,10,.95);">
      <canvas id="robot-canvas"></canvas>
      <div id="robot-hud" style="position:absolute;inset:0;pointer-events:none;padding:1rem;display:flex;flex-direction:column;justify-content:space-between;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <div style="font-family:var(--fm);font-size:.38rem;letter-spacing:.2em;color:rgba(236,72,153,.5);margin-bottom:3px;">POP32i · LINE FOLLOWER</div>
            <div id="hud-status" style="font-family:var(--fm);font-size:.46rem;letter-spacing:.15em;color:rgba(236,72,153,.85);display:flex;align-items:center;gap:5px;">
              <span style="width:5px;height:5px;border-radius:50%;background:#ec4899;box-shadow:0 0 6px #ec4899;display:inline-block;animation:blk .8s step-end infinite;"></span>
              ON TRACK
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-family:var(--fm);font-size:.38rem;letter-spacing:.15em;color:rgba(255,255,255,.2);margin-bottom:3px;">SPEED</div>
            <div id="hud-speed" style="font-family:var(--fd);font-size:1.1rem;color:rgba(236,72,153,.8);line-height:1;">00</div>
            <div style="font-family:var(--fm);font-size:.34rem;letter-spacing:.12em;color:rgba(255,255,255,.18);">cm/s</div>
          </div>
        </div>
        <div>
          <div style="font-family:var(--fm);font-size:.36rem;letter-spacing:.2em;color:rgba(255,255,255,.22);margin-bottom:5px;">IR SENSORS</div>
          <div style="display:flex;gap:4px;" id="hud-sensors">
            <?php for ($s = 0; $s < 5; $s++): ?>
            <div class="hud-s" style="flex:1;height:6px;border-radius:2px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);overflow:hidden;"><div class="hud-sf" style="height:100%;width:0%;background:var(--pink);border-radius:2px;transition:width .15s ease;"></div></div>
            <?php endfor; ?>
          </div>
          <div style="display:flex;gap:4px;margin-top:3px;" id="hud-sensor-vals">
            <?php for ($s = 1; $s <= 5; $s++): ?>
            <div style="flex:1;font-family:var(--fm);font-size:.3rem;text-align:center;color:rgba(255,255,255,.2);">S<?= $s ?></div>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="story-step" id="st3">
    <div>
      <div class="si">04 OF 04</div>
      <span class="slbl" style="color:var(--purple);background:rgba(168,85,247,.07);border:1px solid rgba(168,85,247,.2);">AI</span>
      <h3 class="stitle">AI &amp;<br>INNOVATION</h3>
      <p class="sbody"><strong>AI Detection:</strong> Train Model 100+ รูป รัน Model บน <strong>Anaconda</strong><br><br><strong>Circuits:</strong> วงจรไฟฟ้าอย่างง่ายและอ่านค่า Resistor</p>
      <div class="shi" style="border-color:rgba(168,85,247,.2);color:var(--purple);">⚡ Python · OpenCV · Custom dataset · YOLO</div>
    </div>
    <div class="sv"><div class="sv-in" style="background:radial-gradient(ellipse at center,rgba(168,85,247,.04),transparent);">
      <div class="code-blk"><span class="cm"># AI Object Detection</span>
<span class="kw">import</span> <span class="vr">cv2</span>
<span class="kw">from</span> <span class="vr">ultralytics</span> <span class="kw">import</span> YOLO

<span class="vr">model</span> = <span class="fn">YOLO</span>(<span class="str">'custom_model.pt'</span>)

<span class="kw">for</span> result <span class="kw">in</span> model.<span class="fn">stream</span>(source=<span class="str">0</span>):
  boxes = result.boxes
  <span class="vr">frame</span> = result.<span class="fn">plot</span>()
  cv2.<span class="fn">imshow</span>(<span class="str">"ZumoDet"</span>, frame)</div>
    </div></div>
  </div>
</div></section>

<!-- CTA -->
<section class="cta-sec"><div class="wrap">
  <div class="sr" style="font-family:var(--fm);font-size:.56rem;letter-spacing:.22em;color:rgba(200,255,87,.5);margin-bottom:1rem;">READY TO EXPLORE?</div>
  <h2 class="cta-h sr d1">SEE<br>ALL<br><span class="cta-ol">WORK.</span></h2>
  <div class="cta-div sr d2"></div>
  <p class="cta-sub sr d3">ผลงานด้าน Game, IoT, Robotics และ AI ทั้งหมดในหน้า Projects</p>
  <div class="sr d4">
    <a href="<?= $logged_in ? 'projects.php' : '#' ?>" class="btn-p"
       onclick="<?= !$logged_in ? 'showModal();return false;' : '' ?>"
       style="font-size:.74rem;padding:14px 36px;">
      <i class="fas fa-rocket"></i> SEE MY PROJECTS
    </a>
  </div>
</div></section>

<footer><div class="ft">&copy; <?= date('Y') ?> · ZUMO DEV · PHP · TAILWIND · THREE.JS · ❤️</div></footer>

<!-- MUSIC FAB -->
<button id="mp-fab"><i class="fas fa-music" id="mp-fab-i"></i></button>
<div id="mp-panel"><div id="mp-tb"></div><div id="mp-in">
  <div id="mp-ti">Lo-fi Beats</div><div id="mp-ar">CODING SESSION · LOOPED</div>
  <div id="mp-trk"><div id="mp-fi"></div></div>
  <div id="mp-ts"><span id="mp-cur">0:00</span><span id="mp-dur">--:--</span></div>
  <div id="mp-ctrl">
    <div id="mp-vr"><i class="fas fa-volume-down" id="mp-vi"></i><input type="range" id="mp-vol" min="0" max="100" value="50"><span id="mp-vp">50%</span></div>
    <button id="mp-btn"><i class="fas fa-pause" id="mp-icon"></i></button>
  </div>
</div></div>

<button id="stb"><i class="fas fa-arrow-up"></i></button>

<!-- LOGIN MODAL -->
<div id="loginModal" class="fixed inset-0 flex items-center justify-center hidden modal-bg z-50 p-4">
  <div style="background:#06060f;border:1px solid rgba(200,255,87,.15);border-radius:16px;padding:2.2rem;text-align:center;width:90%;max-width:300px;">
    <div style="font-family:var(--fd);font-size:2rem;margin-bottom:.4rem;">ACCESS DENIED</div>
    <p style="font-family:var(--fm);font-size:.58rem;letter-spacing:.12em;color:var(--dim);margin-bottom:1.4rem;">กรุณาเข้าสู่ระบบ</p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <a href="login.php" class="btn-p" style="font-size:.62rem;padding:9px 18px;">LOGIN</a>
      <button onclick="hideModal()" class="btn-g" style="font-size:.62rem;padding:8px 16px;">BACK</button>
    </div>
  </div>
</div>

<!-- COOKIE BANNER -->
<div id="cookieBanner" class="cookie-banner fixed bottom-0 left-0 w-full p-4 flex flex-col md:flex-row justify-between items-center text-white text-sm z-50 hidden">
  <p style="font-family:var(--fm);font-size:.58rem;letter-spacing:.08em;">
    เว็บไซต์นี้ใช้คุกกี้
    <a href="privacy.php" style="color:var(--lime);text-decoration:none;">เรียนรู้เพิ่มเติม →</a>
  </p>
  <button onclick="acceptCookies()" class="btn-p mt-2 md:mt-0" style="font-size:.56rem;padding:7px 16px;">ยอมรับ</button>
</div>

<!-- ════ BOOT SCRIPT ════ -->
<script>
(function(){
  var KEY = '<?= addslashes($boot_key) ?>';
  var os = document.getElementById('os');
  if (sessionStorage.getItem(KEY)) {
    os.style.display = 'none';
    document.body.classList.remove('boot-active');
    return;
  }
  document.body.style.overflow = 'hidden';
  var cl = document.getElementById('bar-c'), t0 = Date.now();
  var ci = setInterval(function(){
    var d = new Date(Date.now()-t0);
    cl.textContent = String(d.getUTCHours()).padStart(2,'0') + ':' +
                     String(d.getUTCMinutes()).padStart(2,'0') + ':' +
                     String(d.getUTCSeconds()).padStart(2,'0');
  }, 1000);
  var pbf = document.getElementById('os-pb-f'),
      pbp = document.getElementById('os-pb-p'),
      pbl = document.getElementById('os-pb-lbl');
  ['l0','l1','l2','l3','l4','l5','l6','l7','l8','l9','la','lb'].forEach(function(id,i){
    setTimeout(function(){ var e=document.getElementById(id); if(e) e.classList.add('on'); }, 70+i*85);
  });
  var pe = 70 + 12*85 + 200;
  [{t:0,w:12,s:'INIT WebGL'},{t:300,w:32,s:'COMPILING SHADERS'},{t:650,w:54,s:'LOADING PARTICLES'},
   {t:950,w:72,s:'BUILDING 3D SCENE'},{t:1250,w:90,s:'ALMOST READY...'},{t:1500,w:100,s:'LAUNCHING'}
  ].forEach(function(o){
    setTimeout(function(){
      pbf.style.width = o.w+'%'; pbp.textContent = o.w+'%'; pbl.textContent = o.s;
      if (o.w === 100) { pbf.style.background='linear-gradient(90deg,#00ff88,#00e5ff)'; setTimeout(doExit,450); }
    }, pe+o.t);
  });
  var ex = false;
  function doExit(){
    if (ex) return; ex = true;
    clearInterval(ci);
    os.classList.add('leaving');
    setTimeout(function(){
      os.style.display = 'none';
      document.body.style.overflow = '';
      document.documentElement.style.overflow = '';
      document.body.classList.remove('boot-active');
      if (window._mpAP) window._mpAP();
    }, 880);
    sessionStorage.setItem(KEY, '1');
  }
  var cs = false;
  setTimeout(function(){ cs = true; }, pe);
  os.addEventListener('click', function(){ if(cs) doExit(); });
  document.addEventListener('keydown', function(e){
    if (cs && (e.key==='Escape'||e.key===' '||e.key==='Enter')) doExit();
  });
})();
</script>

<!-- ════ CURSOR — desktop only (ข้าม touch device) ════ -->
<script>
(function(){
  /* ตรวจ touch device ด้วย JS ด้วย (เสริมจาก CSS media query) */
  var isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
  if (isTouch) return;

  var C = document.getElementById('cur'), CR = document.getElementById('cur-r');
  var mx = 0, my = 0, rx = 0, ry = 0;
  document.addEventListener('mousemove', function(e){
    mx = e.clientX; my = e.clientY;
    C.style.transform = 'translate('+mx+'px,'+my+'px) translate(-50%,-50%)';
  });
  (function rc(){
    rx += (mx-rx)*.1; ry += (my-ry)*.1;
    CR.style.transform = 'translate('+rx+'px,'+ry+'px) translate(-50%,-50%)';
    requestAnimationFrame(rc);
  })();
})();
</script>

<!-- ════ MAIN WebGL ════ -->
<script>
/* KINETIC TITLE */
(function(){
  var el = document.getElementById('kn'); if(!el) return;
  var raw = el.innerHTML, out = '', d = 0.3;
  for (var i = 0; i < raw.length;) {
    if (raw[i] === '<') {
      var end = raw.indexOf('>', i), tag = raw.slice(i, end+1);
      if (tag.includes('ac')) {
        var cl = raw.indexOf('</span>', end+1), inn = raw.slice(end+1, cl);
        var a = '<span class="ac">';
        for (var ci2 = 0; ci2 < inn.length; ci2++) {
          if (inn[ci2]===' ') a+=' ';
          else { a+='<span class="ch" style="animation-delay:'+d.toFixed(2)+'s">'+inn[ci2]+'</span>'; d+=.065; }
        }
        a += '</span>'; out += a; i = cl+7;
      } else if (tag.includes('sp')) { out += '<span class="sp"> </span>'; i = end+1; }
      else i = end+1;
    } else {
      if (raw[i]===' ') out+=' ';
      else { out+='<span class="ch" style="animation-delay:'+d.toFixed(2)+'s">'+raw[i]+'</span>'; d+=.065; }
      i++;
    }
  }
  el.innerHTML = out;
})();

/* HERO WebGL */
(function(){
  var canvas = document.getElementById('hero-canvas');
  if (!canvas || !window.THREE) return;
  var W = function(){ return window.innerWidth; }, H = function(){ return window.innerHeight; };
  var scene = new THREE.Scene();
  var camera = new THREE.PerspectiveCamera(60, W()/H(), .1, 200);
  camera.position.set(0, 0, 14);
  var renderer = new THREE.WebGLRenderer({canvas:canvas, alpha:true, antialias:true});
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  renderer.setSize(W(), H()); renderer.setClearColor(0x000000, 0);
  var vsh = 'uniform float uT;uniform vec2 uMouse;attribute float aSize;attribute vec3 aColor;varying vec3 vCol;varying float vA;void main(){vCol=aColor;vec3 p=position;p.y+=sin(uT*.4+p.x*.5)*1.1+cos(uT*.3+p.z*.4)*.7;float dist=length(p.xy-uMouse*10.0);p.xy+=(p.xy-uMouse*10.0)*smoothstep(3.0,0.0,dist)*.12;vec4 mv=modelViewMatrix*vec4(p,1.0);gl_PointSize=aSize*(280.0/-mv.z);gl_Position=projectionMatrix*mv;vA=.5+sin(uT*.6+p.x*2.0+p.z)*.22;}';
  var fsh = 'varying vec3 vCol;varying float vA;void main(){vec2 uv=gl_PointCoord-vec2(.5);float d=length(uv);if(d>.5)discard;float a=1.0-smoothstep(.25,.5,d);gl_FragColor=vec4(vCol,a*vA);}';
  var N=5000, pos=new Float32Array(N*3), sz=new Float32Array(N), col=new Float32Array(N*3);
  var pal=[[.78,1,.34],[0,.9,1],[.66,.33,.97],[.93,.28,.6]];
  for (var i=0; i<N; i++){
    var r=8+Math.random()*20, th=Math.random()*Math.PI*2, ph=Math.acos(2*Math.random()-1);
    pos[i*3]=r*Math.sin(ph)*Math.cos(th); pos[i*3+1]=r*Math.sin(ph)*Math.sin(th)*.55; pos[i*3+2]=r*Math.cos(ph)-5;
    sz[i]=.4+Math.random()*1.5;
    var c=pal[i%pal.length], f=.4+Math.random()*.6;
    col[i*3]=c[0]*f; col[i*3+1]=c[1]*f; col[i*3+2]=c[2]*f;
  }
  var geo = new THREE.BufferGeometry();
  geo.setAttribute('position', new THREE.BufferAttribute(pos,3));
  geo.setAttribute('aSize',    new THREE.BufferAttribute(sz,1));
  geo.setAttribute('aColor',   new THREE.BufferAttribute(col,3));
  var mat = new THREE.ShaderMaterial({vertexShader:vsh,fragmentShader:fsh,uniforms:{uT:{value:0},uMouse:{value:new THREE.Vector2(0,0)}},transparent:true,depthWrite:false,blending:THREE.AdditiveBlending});
  scene.add(new THREE.Points(geo, mat));
  var icoS=new THREE.Mesh(new THREE.IcosahedronGeometry(2.2,1),new THREE.MeshBasicMaterial({color:0x03030a,transparent:true,opacity:.9})); scene.add(icoS);
  var icoW=new THREE.Mesh(new THREE.IcosahedronGeometry(2.22,1),new THREE.MeshBasicMaterial({color:0xc8ff57,wireframe:true,transparent:true,opacity:.18})); scene.add(icoW);
  var icoO=new THREE.Mesh(new THREE.IcosahedronGeometry(2.65,1),new THREE.MeshBasicMaterial({color:0x00e5ff,wireframe:true,transparent:true,opacity:.055})); scene.add(icoO);
  var mk=function(r,c,op,rx2,rz2){var m=new THREE.Mesh(new THREE.TorusGeometry(r,.012,8,90),new THREE.MeshBasicMaterial({color:c,transparent:true,opacity:op}));m.rotation.x=rx2;m.rotation.z=rz2||0;scene.add(m);return m;};
  var ring1=mk(3.8,0xc8ff57,.22,.38,0), ring2=mk(3.2,0x00e5ff,.12,.7,.25);
  var dg=new THREE.SphereGeometry(.07,8,8);
  function mkDot(color,phase){var m=new THREE.Mesh(dg,new THREE.MeshBasicMaterial({color:color}));m.userData={phase:phase,r:3.8};scene.add(m);return m;}
  var dots=[mkDot(0xc8ff57,0),mkDot(0x00e5ff,Math.PI*.6),mkDot(0xa855f7,Math.PI*1.3)];
  var mouse=new THREE.Vector2(), trx=0, try2=0, crx=0, cry=0;
  document.addEventListener('mousemove',function(e){mouse.x=(e.clientX/W())*2-1;mouse.y=-(e.clientY/H())*2+1;trx=mouse.y*.22;try2=mouse.x*.32;mat.uniforms.uMouse.value.copy(mouse);});
  window.addEventListener('resize',function(){camera.aspect=W()/H();camera.updateProjectionMatrix();renderer.setSize(W(),H());});
  var t=0, heroVis=true;
  new IntersectionObserver(function(e){heroVis=e[0].isIntersecting;},{threshold:0,rootMargin:'100px'}).observe(document.getElementById('hero'));
  (function loop(){
    requestAnimationFrame(loop);
    if (!heroVis) return;
    t+=.012; mat.uniforms.uT.value=t;
    crx+=(trx-crx)*.04; cry+=(try2-cry)*.04;
    scene.rotation.y=t*.055+cry; scene.rotation.x=Math.sin(t*.1)*.07+crx;
    icoW.rotation.y=t*.28; icoW.rotation.x=t*.17;
    icoO.rotation.y=-t*.14; icoO.rotation.z=t*.09;
    ring1.rotation.z=t*.07; ring2.rotation.z=-t*.11;
    dots.forEach(function(d){var a=t*.4+d.userData.phase,r=d.userData.r;d.position.set(Math.cos(a)*r,Math.sin(a*1.3)*.8,Math.sin(a)*r*.6);});
    renderer.render(scene,camera);
  })();
})();

/* SHADER WAVE */
(function(){
  var canvas=document.getElementById('wave-canvas');
  if(!canvas||!window.THREE) return;
  var sec=document.getElementById('wave-section');
  var W=function(){return sec.offsetWidth;}, H=function(){return sec.offsetHeight;};
  var scene=new THREE.Scene(), camera=new THREE.OrthographicCamera(-1,1,-1,1,.1,10); camera.position.z=1;
  var renderer=new THREE.WebGLRenderer({canvas:canvas,alpha:true,antialias:false});
  renderer.setSize(W(),H()); renderer.setClearColor(0,0);
  var mat=new THREE.ShaderMaterial({
    vertexShader:'varying vec2 vUv;void main(){vUv=uv;gl_Position=vec4(position,1.0);}',
    fragmentShader:'uniform float uT;varying vec2 vUv;float h21(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5);}float n(vec2 p){vec2 i=floor(p);vec2 f=fract(p);f=f*f*(3.0-2.0*f);return mix(mix(h21(i),h21(i+vec2(1,0)),f.x),mix(h21(i+vec2(0,1)),h21(i+vec2(1,1)),f.x),f.y);}void main(){float t=uT*.35;vec2 uv=vUv;float waves=0.0;for(int i=1;i<=5;i++){float fi=float(i);waves+=sin(uv.x*6.28*fi*.6+t*fi*.7+n(vec2(uv.x*2.0,t*.5))*2.0)*(.08/fi);}float cx=uv.y-.5+waves;float lime=smoothstep(.06,.0,abs(cx))*.55;float cyan=smoothstep(.08,.0,abs(cx-.03))*.3;vec3 col=vec3(lime*.78,lime,lime*.34)+vec3(0.,cyan*.9,cyan);float fade=smoothstep(0.,.12,uv.x)*smoothstep(1.,.88,uv.x);gl_FragColor=vec4(col,(lime+cyan)*fade*.7);}',
    uniforms:{uT:{value:0}},transparent:true,depthWrite:false,blending:THREE.AdditiveBlending
  });
  scene.add(new THREE.Mesh(new THREE.PlaneGeometry(2,2), mat));
  window.addEventListener('resize',function(){renderer.setSize(W(),H());});
  var t=0, waveVis=false;
  new IntersectionObserver(function(e){waveVis=e[0].isIntersecting;},{threshold:0,rootMargin:'100px'}).observe(document.getElementById('wave-section'));
  (function loop(){requestAnimationFrame(loop);if(!waveVis)return;mat.uniforms.uT.value=(t+=1)*.016;renderer.render(scene,camera);})();
})();

/* NEURAL */
(function(){
  var canvas=document.getElementById('neural-canvas'); if(!canvas) return;
  function rsz(){canvas.width=canvas.offsetWidth;canvas.height=canvas.offsetHeight;} rsz();
  var ctx=canvas.getContext('2d'), N=28, nodes=[];
  for(var i=0;i<N;i++) nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-.5)*.32,vy:(Math.random()-.5)*.32,r:1.5+Math.random()*2.5,ph:Math.random()*Math.PI*2});
  var t=0, neuralVis=false;
  new IntersectionObserver(function(e){neuralVis=e[0].isIntersecting;},{threshold:0,rootMargin:'100px'}).observe(canvas);
  (function loop(){
    requestAnimationFrame(loop); if(!neuralVis) return;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    nodes.forEach(function(n){n.x+=n.vx;n.y+=n.vy;if(n.x<0||n.x>canvas.width)n.vx*=-1;if(n.y<0||n.y>canvas.height)n.vy*=-1;});
    for(var i=0;i<N;i++) for(var j=i+1;j<N;j++){
      var dx=nodes[i].x-nodes[j].x,dy=nodes[i].y-nodes[j].y,d=Math.sqrt(dx*dx+dy*dy);
      if(d<95){var p=Math.sin(t*.04+i*.3+j*.2)*.5+.5,a=(1-d/95)*.35*p;
        var g=ctx.createLinearGradient(nodes[i].x,nodes[i].y,nodes[j].x,nodes[j].y);
        g.addColorStop(0,'rgba(168,85,247,'+a+')');g.addColorStop(.5,'rgba(0,229,255,'+(a*1.3)+')');g.addColorStop(1,'rgba(200,255,87,'+a+')');
        ctx.beginPath();ctx.moveTo(nodes[i].x,nodes[i].y);ctx.lineTo(nodes[j].x,nodes[j].y);ctx.strokeStyle=g;ctx.lineWidth=.8;ctx.stroke();}
    }
    nodes.forEach(function(n,i){
      var p=Math.sin(t*.05+n.ph)*.5+.5;
      var g=ctx.createRadialGradient(n.x,n.y,0,n.x,n.y,n.r*4);
      g.addColorStop(0,'rgba(168,85,247,'+(0.7*p)+')');g.addColorStop(.5,'rgba(0,229,255,'+(0.3*p)+')');g.addColorStop(1,'rgba(0,0,0,0)');
      ctx.beginPath();ctx.arc(n.x,n.y,n.r*3.5,0,Math.PI*2);ctx.fillStyle=g;ctx.fill();
      ctx.beginPath();ctx.arc(n.x,n.y,n.r,0,Math.PI*2);
      ctx.fillStyle='rgba('+(i%3===0?'200,255,87':i%3===1?'0,229,255':'168,85,247')+','+(0.7+p*.3)+')';ctx.fill();
    });
    t++;
  })();
  window.addEventListener('resize',function(){rsz();nodes.forEach(function(n){n.x=Math.min(n.x,canvas.width);n.y=Math.min(n.y,canvas.height);});});
})();

/* LINE FOLLOWER ROBOT */
(function(){
  var canvas=document.getElementById('robot-canvas');
  if(!canvas||!window.THREE) return;
  var par=canvas.parentElement;
  var W=function(){return par.offsetWidth||400;}, H=function(){return par.offsetHeight||320;};
  var scene=new THREE.Scene(), camera=new THREE.PerspectiveCamera(48,W()/H(),.1,100);
  camera.position.set(0,9,5); camera.lookAt(0,0,0);
  var renderer=new THREE.WebGLRenderer({canvas:canvas,alpha:true,antialias:false});
  renderer.setPixelRatio(1); renderer.setSize(W(),H()); renderer.setClearColor(0,0);
  function trackPoint(u){var t2=u*Math.PI*2;return new THREE.Vector3(Math.sin(t2)*3.2,0,Math.sin(t2*2)*1.6);}
  var trackPts=[]; for(var i=0;i<=200;i++) trackPts.push(trackPoint(i/200));
  var trackCurve=new THREE.CatmullRomCurve3(trackPts,true);
  scene.add(new THREE.Mesh(new THREE.TubeGeometry(trackCurve,200,.045,8,true),new THREE.MeshBasicMaterial({color:0xec4899,transparent:true,opacity:.7})));
  scene.add(new THREE.Mesh(new THREE.TubeGeometry(trackCurve,200,.12,8,true),new THREE.MeshBasicMaterial({color:0xec4899,transparent:true,opacity:.08,side:THREE.DoubleSide})));
  var grid=new THREE.Mesh(new THREE.PlaneGeometry(14,10,14,10),new THREE.MeshBasicMaterial({color:0xec4899,wireframe:true,transparent:true,opacity:.04}));
  grid.rotation.x=-Math.PI*.5; scene.add(grid);
  var carGroup=new THREE.Group();
  var chassisGeo=new THREE.BoxGeometry(1,.22,1.4);
  carGroup.add(new THREE.Mesh(chassisGeo,new THREE.MeshBasicMaterial({color:0xec4899,transparent:true,opacity:.12})));
  carGroup.add(new THREE.Mesh(chassisGeo,new THREE.MeshBasicMaterial({color:0xec4899,wireframe:true,transparent:true,opacity:.7})));
  var topGeo=new THREE.BoxGeometry(.85,.08,1.0);
  var tp=new THREE.Mesh(topGeo,new THREE.MeshBasicMaterial({color:0x00e5ff,transparent:true,opacity:.18})); tp.position.y=.18; carGroup.add(tp);
  var tw=new THREE.Mesh(topGeo,new THREE.MeshBasicMaterial({color:0x00e5ff,wireframe:true,transparent:true,opacity:.45})); tw.position.y=.18; carGroup.add(tw);
  var wheelGeo=new THREE.CylinderGeometry(.22,.22,.14,12);
  var wheelPos=[[-0.58,.0,.45],[0.58,.0,.45],[-0.58,.0,-.45],[0.58,.0,-.45]];
  var wheels=wheelPos.map(function(wp){
    var wg=new THREE.Group();
    wg.add(new THREE.Mesh(wheelGeo,new THREE.MeshBasicMaterial({color:0xec4899,transparent:true,opacity:.08})));
    var ww=new THREE.Mesh(wheelGeo,new THREE.MeshBasicMaterial({color:0xec4899,wireframe:true,transparent:true,opacity:.55}));
    wg.add(ww); ww.rotation.z=Math.PI*.5;
    wg.add(new THREE.Mesh(new THREE.SphereGeometry(.06,6,6),new THREE.MeshBasicMaterial({color:0xc8ff57,transparent:true,opacity:.9})));
    wg.position.set(wp[0],wp[1],wp[2]); wg.children[0].rotation.z=Math.PI*.5;
    carGroup.add(wg); return wg;
  });
  var sensorDots=[];
  for(var si=0;si<5;si++){var sd=new THREE.Mesh(new THREE.SphereGeometry(.04,6,6),new THREE.MeshBasicMaterial({color:0xc8ff57,transparent:true,opacity:.8}));sd.position.set(-0.4+si*.2,-.08,.8);carGroup.add(sd);sensorDots.push(sd);}
  var sBarGeo=new THREE.BoxGeometry(1,.04,.06);
  var sbg=new THREE.Group(); sbg.add(new THREE.Mesh(sBarGeo,new THREE.MeshBasicMaterial({color:0xc8ff57,wireframe:true,transparent:true,opacity:.6}))); sbg.position.set(0,-.08,.78); carGroup.add(sbg);
  var lasers=sensorDots.map(function(sd){var m=new THREE.Mesh(new THREE.CylinderGeometry(.01,.005,.3,4),new THREE.MeshBasicMaterial({color:0xc8ff57,transparent:true,opacity:.0,blending:THREE.AdditiveBlending}));m.position.copy(sd.position);m.position.y-=.18;carGroup.add(m);return m;});
  var ant=new THREE.Mesh(new THREE.CylinderGeometry(.015,.015,.4,4),new THREE.MeshBasicMaterial({color:0x00e5ff,transparent:true,opacity:.8})); ant.position.set(-.2,.35,-.3); carGroup.add(ant);
  var antTip=new THREE.Mesh(new THREE.SphereGeometry(.04,6,6),new THREE.MeshBasicMaterial({color:0x00e5ff,transparent:true,opacity:.9})); antTip.position.set(-.2,.57,-.3); carGroup.add(antTip);
  scene.add(carGroup);
  var TRAIL=80, trailPos=new Float32Array(TRAIL*3), trailGeo=new THREE.BufferGeometry();
  trailGeo.setAttribute('position',new THREE.BufferAttribute(trailPos,3));
  scene.add(new THREE.Points(trailGeo,new THREE.PointsMaterial({color:0xec4899,size:.06,transparent:true,opacity:.5,blending:THREE.AdditiveBlending,depthWrite:false})));
  var AP=120,apPos=new Float32Array(AP*3),apCol=new Float32Array(AP*3);
  for(var ai=0;ai<AP;ai++){apPos[ai*3]=(Math.random()-.5)*10;apPos[ai*3+1]=.02+Math.random()*.15;apPos[ai*3+2]=(Math.random()-.5)*7;var ac2=ai%3;apCol[ai*3]=ac2===0?.78:0;apCol[ai*3+1]=ac2===0?1:ac2===1?.9:.5;apCol[ai*3+2]=ac2===0?.34:ac2===1?1:.97;}
  var apGeo=new THREE.BufferGeometry(); apGeo.setAttribute('position',new THREE.BufferAttribute(apPos,3)); apGeo.setAttribute('color',new THREE.BufferAttribute(apCol,3));
  scene.add(new THREE.Points(apGeo,new THREE.PointsMaterial({size:.04,vertexColors:true,transparent:true,opacity:.45,blending:THREE.AdditiveBlending,depthWrite:false})));
  var ring2=new THREE.Mesh(new THREE.TorusGeometry(4.5,.008,6,80),new THREE.MeshBasicMaterial({color:0xec4899,transparent:true,opacity:.1})); ring2.rotation.x=-Math.PI*.5; scene.add(ring2);
  var hudSpeed=document.getElementById('hud-speed'),hudSensors=document.querySelectorAll('.hud-sf'),hudSensorVals=document.querySelectorAll('#hud-sensor-vals > div');
  window.addEventListener('resize',function(){camera.aspect=W()/H();camera.updateProjectionMatrix();renderer.setSize(W(),H());});
  var trailHead=0, TLUT=400, trackLUT=[];
  for(var ti=0;ti<TLUT;ti++) trackLUT.push(trackPoint(ti/TLUT));
  function closestTrackDist(wx,wz){var min=99;for(var j=0;j<TLUT;j++){var tp2=trackLUT[j],dx=tp2.x-wx,dz=tp2.z-wz,d=dx*dx+dz*dz;if(d<min)min=d;}return Math.sqrt(min);}
  var trackT=0,curAngle=0,camAngle=0,lastTime=null,sensorFrame=0,elapsedTime=0,robotState=0;
  window._robotSetState=function(s){robotState=s;if(s===3)lastTime=null;};
  renderer.render(scene,camera);
  new IntersectionObserver(function(e){
    if(e[0].isIntersecting&&robotState===0){robotState=1;lastTime=null;}
    else if(!e[0].isIntersecting&&robotState<3){robotState=0;}
  },{threshold:0,rootMargin:'800px'}).observe(canvas);
  var lastRenderTime=0;
  (function loop(now){
    requestAnimationFrame(loop);
    if(robotState===0||robotState===2){lastTime=null;return;}
    if(robotState===1){if(now-lastRenderTime<33)return;lastRenderTime=now;renderer.render(scene,camera);return;}
    if(!lastTime)lastTime=now;
    var dt=Math.min((now-lastTime)/1000,0.04); lastTime=now; elapsedTime+=dt;
    trackT=(trackT+0.09*dt)%1;
    var pos=trackCurve.getPointAt(trackT),ahead=trackCurve.getPointAt((trackT+0.004)%1),dir=ahead.clone().sub(pos).normalize(),targetAngle=Math.atan2(dir.x,dir.z),da=targetAngle-curAngle;
    if(da>Math.PI)da-=Math.PI*2; if(da<-Math.PI)da+=Math.PI*2;
    curAngle+=da*(1-Math.pow(0.01,dt));
    carGroup.position.set(pos.x,0.18,pos.z); carGroup.rotation.y=curAngle;
    wheels.forEach(function(w){w.rotation.x+=0.09*dt*18;});
    antTip.material.opacity=0.5+Math.sin(elapsedTime*3.8)*0.4;
    var idx=trailHead%TRAIL; trailPos[idx*3]=pos.x;trailPos[idx*3+1]=0.05;trailPos[idx*3+2]=pos.z; trailHead++;
    for(var i=0;i<TRAIL;i++){var src=((trailHead-1-i)+TRAIL*4)%TRAIL;trailPos[i*3]=trailPos[src*3];trailPos[i*3+1]=0.05;trailPos[i*3+2]=trailPos[src*3+2];}
    trailGeo.attributes.position.needsUpdate=true;
    sensorFrame++;
    if(sensorFrame%6===0){
      sensorDots.forEach(function(sd,i){var off=i*0.2-0.4,wx=pos.x-Math.sin(curAngle)*0.8+Math.cos(curAngle)*off,wz=pos.z+Math.cos(curAngle)*0.8+Math.sin(curAngle)*off,onLine=closestTrackDist(wx,wz)<0.22;lasers[i].material.opacity=onLine?0.55:0.07;if(hudSensors[i]){hudSensors[i].style.width=onLine?'100%':'8%';hudSensors[i].style.background=onLine?'var(--lime)':'var(--pink)';}if(hudSensorVals[i])hudSensorVals[i].style.color=onLine?'rgba(200,255,87,.8)':'rgba(255,255,255,.18)';});
      if(hudSpeed)hudSpeed.textContent=String(Math.round(45+Math.sin(elapsedTime*1.2)*12)).padStart(2,'0');
    }
    camAngle+=dt*0.18;
    camera.position.x=Math.sin(camAngle)*1.8; camera.position.z=5+Math.cos(camAngle*0.7)*1.0; camera.position.y=9+Math.sin(camAngle*0.5)*0.6;
    camera.lookAt(0,0,0);
    renderer.render(scene,camera);
  })(performance.now());
})();

document.addEventListener('DOMContentLoaded',function(){
  /* 3D Tilt */
  document.querySelectorAll('.tilt-card').forEach(function(card){
    card.addEventListener('mouseenter',function(){card.classList.add('glow');});
    card.addEventListener('mouseleave',function(){card.classList.remove('glow');card.style.transform='';});
    card.addEventListener('mousemove',function(e){var r=card.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;card.style.transform='perspective(600px) rotateX('+(-y*16)+'deg) rotateY('+(x*16)+'deg) scale(1.06)';card.style.setProperty('--mx',((x+.5)*100)+'%');card.style.setProperty('--my',((y+.5)*100)+'%');});
  });

  /* Scroll Reveal */
  var ro=new IntersectionObserver(function(e){e.forEach(function(x){if(x.isIntersecting){x.target.classList.add('vis');ro.unobserve(x.target);}});},{threshold:0,rootMargin:'0px 0px -60px 0px'});
  document.querySelectorAll('.sr').forEach(function(el){ro.observe(el);});

  /* Scrollytelling */
  var ANIM_DURATION=900;
  var so=new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      var el=entry.target, isRobot=el.id==='st2';
      if(entry.isIntersecting){
        el.classList.remove('s-out');
        if(isRobot&&window._robotSetState) window._robotSetState(2);
        requestAnimationFrame(function(){requestAnimationFrame(function(){
          el.classList.add('s-in');
          if(isRobot) setTimeout(function(){if(window._robotSetState)window._robotSetState(3);},ANIM_DURATION);
        });});
      } else if(el.classList.contains('s-in')){
        el.classList.remove('s-in'); el.classList.add('s-out');
        if(isRobot&&window._robotSetState) window._robotSetState(1);
        var cleanup=function(){if(el.classList.contains('s-out'))el.classList.remove('s-out');};
        el.addEventListener('transitionend',cleanup,{once:true}); setTimeout(cleanup,500);
      }
    });
  },{threshold:0.05,rootMargin:'0px 0px -40px 0px'});
  document.querySelectorAll('.story-step').forEach(function(el){so.observe(el);});

  /* Counters */
  var co=new IntersectionObserver(function(e){e.forEach(function(x){if(!x.isIntersecting)return;var el=x.target,tgt=parseInt(el.dataset.target),suf=el.dataset.suffix||'',c=0,inc=tgt/40;var tm=setInterval(function(){c=Math.min(c+inc,tgt);el.textContent=Math.round(c)+suf;if(c>=tgt)clearInterval(tm);},28);co.unobserve(el);});},{threshold:0,rootMargin:'0px 0px -40px 0px'});
  document.querySelectorAll('[data-target]').forEach(function(el){co.observe(el);});

  /* Skill Bars */
  var bo=new IntersectionObserver(function(e){e.forEach(function(x){if(!x.isIntersecting)return;var container=x.target.closest('.bc-p'),bars=container?container.querySelectorAll('.sk-f[data-w]'):[x.target];bars.forEach(function(bar,i){setTimeout(function(){bar.style.width=bar.dataset.w+'%';},i*100);});bo.unobserve(x.target);});},{threshold:0,rootMargin:'0px 0px -30px 0px'});
  document.querySelectorAll('.bc-p').forEach(function(container){var first=container.querySelector('.sk-f[data-w]');if(first)bo.observe(first);});

  /* Activity Grid */
  var ag=document.getElementById('ag');
  if(ag){for(var i=0;i<52;i++){var c2=document.createElement('div');c2.className='ag-c';var lvls=[0,0,0,0,.05,.08,.12,.18,.28,.42,.65,.85,1];var lv=lvls[~~(Math.random()*lvls.length)];if(lv>0)c2.style.setProperty('--cc','rgba(200,255,87,'+lv+')');ag.appendChild(c2);}}

  /* Cookie */
  if(!localStorage.getItem('cookieAccepted')) document.getElementById('cookieBanner').classList.remove('hidden');

  /* Scroll Top */
  var sb=document.getElementById('stb');
  window.addEventListener('scroll',function(){sb.classList.toggle('vis',window.scrollY>500);});
  sb.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'});});

  /* Music */
  (function(){
    var audio=new Audio('music/music_web.mp3'); audio.volume=.5; audio.loop=true; window._mpAudio=audio;
    audio.addEventListener('loadedmetadata',function(){audio.currentTime=1000;document.getElementById('mp-dur').textContent=fmt(audio.duration);},{once:true});
    var fab=document.getElementById('mp-fab'),panel=document.getElementById('mp-panel'),btn=document.getElementById('mp-btn'),icon=document.getElementById('mp-icon'),fill=document.getElementById('mp-fi'),cur=document.getElementById('mp-cur'),vol=document.getElementById('mp-vol'),vp=document.getElementById('mp-vp');
    function fmt(s){return ~~(s/60)+':'+(~~(s%60)).toString().padStart(2,'0');}
    var playing=false, pOpen=false;
    function sp(s){playing=s;icon.className=s?'fas fa-pause':'fas fa-play';s?(panel.classList.add('active'),fab.classList.add('playing')):(panel.classList.remove('active'),fab.classList.remove('playing'));}
    audio.addEventListener('play',function(){sp(true);});
    audio.addEventListener('pause',function(){sp(false);});
    audio.addEventListener('timeupdate',function(){if(!audio.duration)return;fill.style.width=(audio.currentTime/audio.duration*100)+'%';cur.textContent=fmt(audio.currentTime);});
    fab.addEventListener('click',function(){pOpen=!pOpen;panel.classList.toggle('open',pOpen);});
    document.addEventListener('click',function(e){if(pOpen&&!panel.contains(e.target)&&e.target!==fab&&!fab.contains(e.target)){pOpen=false;panel.classList.remove('open');}});
    btn.addEventListener('click',function(e){e.stopPropagation();playing?audio.pause():audio.play().catch(function(){});});
    document.getElementById('mp-trk').addEventListener('click',function(e){if(!audio.duration)return;audio.currentTime=((e.clientX-this.getBoundingClientRect().left)/this.offsetWidth)*audio.duration;});
    function sv(v){audio.volume=v/100;vol.style.setProperty('--vol',v+'%');vp.textContent=v+'%';}
    vol.addEventListener('input',function(){sv(+vol.value);}); sv(50);
    function tryAP(){audio.play().then(function(){pOpen=true;panel.classList.add('open');setTimeout(function(){pOpen=false;panel.classList.remove('open');},3e3);}).catch(function(){var f=function(){audio.play().catch(function(){});document.removeEventListener('click',f);};document.addEventListener('click',f);});}
    window._mpAP=tryAP;
    if(document.getElementById('os')&&document.getElementById('os').style.display==='none') tryAP();
  })();
});

function showModal(){ document.getElementById('loginModal').classList.remove('hidden'); }
function hideModal(){ document.getElementById('loginModal').classList.add('hidden'); }
function acceptCookies(){ localStorage.setItem('cookieAccepted','true'); document.getElementById('cookieBanner').classList.add('hidden'); }
</script>
</body>
</html>