<?php
/**
 * projects.php — v2
 * FIX: navbar ไม่บังปุ่มเมื่อเปิด modal (ใช้ร่วมกับ navbar_v2.php)
 */
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];
$stmt_user = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user       = $stmt_user->fetch();
$is_admin   = ($user && $user['role'] == 'admin');
$error_message = "";
$allowed_tags  = ['ระดับประเทศ','ระดับนานาชาติ','ระดับจังหวัด','งานในโรงเรียน','เข้าร่วมกิจกรรม'];
$allowed_link_keys = ['github','youtube','drive'];

/* ── WebP converter ── */
function convertToWebP(string $tmpPath, string $targetDir, int $quality = 82): string|false {
    $mime = mime_content_type($tmpPath);
    $src  = match($mime) {
        'image/jpeg' => imagecreatefromjpeg($tmpPath),
        'image/png'  => imagecreatefrompng($tmpPath),
        'image/gif'  => imagecreatefromgif($tmpPath),
        'image/webp' => imagecreatefromwebp($tmpPath),
        default      => false,
    };
    if (!$src) return false;
    if ($mime === 'image/png') {
        imagepalettetotruecolor($src);
        imagealphablending($src, true);
        imagesavealpha($src, true);
    }
    $filename = $targetDir . uniqid('project_', true) . '.webp';
    $ok = imagewebp($src, $filename, $quality);
    imagedestroy($src);
    return $ok ? $filename : false;
}

/* ── Image field decoder ── */
function decodeImages($raw): array {
    if (!$raw) return [];
    $d = json_decode($raw, true);
    if (is_array($d)) return array_values(array_filter($d));
    return [$raw];
}

/* ── Links field decoder ── */
function decodeLinks($raw): array {
    if (!$raw) return [];
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}

/* ── Multi-file upload processor ── */
function processUploadedFiles(array $fi, string $dir, int $max = 5): array {
    $saved = [];
    if (!isset($fi['name']) || !is_array($fi['name'])) return $saved;
    if (!file_exists($dir)) mkdir($dir, 0755, true);
    $ok_ext = ['jpg','jpeg','png','gif','webp'];
    $n = min(count($fi['name']), $max);
    for ($i = 0; $i < $n; $i++) {
        if ($fi['error'][$i] !== 0 || $fi['size'][$i] <= 0) continue;
        $ext = strtolower(pathinfo($fi['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $ok_ext) || $fi['size'][$i] >= 20000000) continue;
        $tf = convertToWebP($fi['tmp_name'][$i], $dir);
        if ($tf) $saved[] = $tf;
    }
    return $saved;
}

/* ── Build links JSON from POST ── */
function buildLinksJson(array $post, array $allowed): string {
    $links = [];
    foreach ($allowed as $key) {
        $val = trim($post['link_'.$key] ?? '');
        if ($val !== '' && filter_var($val, FILTER_VALIDATE_URL)) {
            $links[$key] = $val;
        }
    }
    return empty($links) ? 'null' : json_encode($links, JSON_UNESCAPED_UNICODE);
}

/* ──────────────────────────── POST HANDLERS ──────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_admin) {

    /* ADD */
    if (isset($_POST['add_project'])) {
        $name     = filter_input(INPUT_POST,'project_name',FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $desc     = filter_input(INPUT_POST,'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $raw_tags = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [];
        $tags     = array_values(array_filter($raw_tags, fn($t)=>in_array($t,$allowed_tags)));
        if (empty($tags)) $tags = ['งานในโรงเรียน'];
        $tag_json  = json_encode($tags, JSON_UNESCAPED_UNICODE);
        $link_json = buildLinksJson($_POST, $allowed_link_keys);

        if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
            $saved = processUploadedFiles($_FILES['files'], 'uploads/');
            if (!empty($saved)) {
                $img_json = json_encode($saved, JSON_UNESCAPED_UNICODE);
                $pdo->prepare("INSERT INTO projects (name,description,image,tag,links) VALUES (?,?,?,?,?)")
                    ->execute([$name,$desc,$img_json,$tag_json,$link_json]);
                header("Location: projects.php"); exit();
            } else $error_message = "แปลงรูปภาพล้มเหลว! กรุณาตรวจสอบไฟล์";
        } else $error_message = "กรุณาเลือกไฟล์ภาพอย่างน้อย 1 ไฟล์";
    }

    /* EDIT */
    if (isset($_POST['edit_project'])) {
        $pid      = $_POST['project_id'];
        $name     = filter_input(INPUT_POST,'project_name',FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $desc     = filter_input(INPUT_POST,'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $raw_tags = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [];
        $tags     = array_values(array_filter($raw_tags, fn($t)=>in_array($t,$allowed_tags)));
        if (empty($tags)) $tags = ['งานในโรงเรียน'];
        $tag_json  = json_encode($tags, JSON_UNESCAPED_UNICODE);
        $link_json = buildLinksJson($_POST, $allowed_link_keys);

        $exist_raw  = $_POST['existing_image_json'] ?? '[]';
        $exist_imgs = json_decode($exist_raw, true) ?: [];
        $keep       = isset($_POST['keep_images']) && is_array($_POST['keep_images']) ? $_POST['keep_images'] : [];
        foreach ($exist_imgs as $p) { if (!in_array($p,$keep) && file_exists($p)) unlink($p); }
        $final = array_values(array_filter($keep, fn($p)=>file_exists($p)));

        if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
            $new = processUploadedFiles($_FILES['files'], 'uploads/', 5-count($final));
            $final = array_merge($final, $new);
        }
        if (empty($final)) $final = $exist_imgs;

        $img_json = json_encode($final, JSON_UNESCAPED_UNICODE);
        $pdo->prepare("UPDATE projects SET name=?,description=?,image=?,tag=?,links=? WHERE id=?")
            ->execute([$name,$desc,$img_json,$tag_json,$link_json,$pid]);
        header("Location: projects.php"); exit();
    }

    /* DELETE */
    if (isset($_POST['delete_project'])) {
        $pid  = $_POST['project_id'];
        $imgs = decodeImages($_POST['existing_image_json'] ?? '');
        foreach ($imgs as $p) { if ($p && file_exists($p)) unlink($p); }
        $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$pid]);
        header("Location: projects.php"); exit();
    }
}

$projects = $pdo->query("SELECT * FROM projects ORDER BY id DESC")->fetchAll();

function decodeTags($r){
    if(!$r) return ['งานในโรงเรียน'];
    $d=json_decode($r,true);
    return is_array($d)?$d:[$r];
}
function getTagInfo($t){
    return [
        'ระดับประเทศ'    =>['cls'=>'tag-national',     'icon'=>'🏆'],
        'ระดับนานาชาติ'  =>['cls'=>'tag-international','icon'=>'🌏'],
        'ระดับจังหวัด'   =>['cls'=>'tag-provincial',   'icon'=>'🏛️'],
        'งานในโรงเรียน'  =>['cls'=>'tag-school',        'icon'=>'🏫'],
        'เข้าร่วมกิจกรรม'=>['cls'=>'tag-activity',     'icon'=>'🎯'],
    ][$t] ?? ['cls'=>'tag-school','icon'=>'📌'];
}
$tagDefs=[
    'งานในโรงเรียน'  =>['key'=>'school',       'icon'=>'🏫','cls'=>'tag-school'],
    'ระดับจังหวัด'   =>['key'=>'provincial',   'icon'=>'🏛️','cls'=>'tag-provincial'],
    'ระดับประเทศ'    =>['key'=>'national',     'icon'=>'🏆','cls'=>'tag-national'],
    'ระดับนานาชาติ'  =>['key'=>'international','icon'=>'🌏','cls'=>'tag-international'],
    'เข้าร่วมกิจกรรม'=>['key'=>'activity',     'icon'=>'🎯','cls'=>'tag-activity'],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Projects - Portfolio</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="icon" type="image/png" href="assets/Icon portfolio.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ═══ BASE ═══ */
body{background:linear-gradient(135deg,#0f0c29,#302b63,#111827,#a11647,#240b36);background-size:400% 400%;animation:gradientBG 15s ease infinite;font-family:'Noto Sans Thai','Inter','Segoe UI',sans-serif;}
@keyframes gradientBG{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
html{scrollbar-width:thin;scrollbar-color:#a855f7 #111827;}
html::-webkit-scrollbar{width:10px;}html::-webkit-scrollbar-track{background:#111827;}
html::-webkit-scrollbar-thumb{background-color:#a855f7;border-radius:20px;border:2px solid #111827;}

/* ═══ SCROLL REVEAL ═══ */
@keyframes smoothFadeInUp{from{opacity:0;transform:translateY(30px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
.scroll-reveal{opacity:0;}
.scroll-reveal.is-visible{animation:smoothFadeInUp .6s cubic-bezier(.25,.46,.45,.94) forwards;}

/* ═══ CARD ═══ */
.project-card{position:relative;background:#1f2937;border:1px solid rgba(255,255,255,.06);border-radius:1rem;overflow:hidden;transition:transform .3s ease,box-shadow .3s ease;}
.project-card:hover{transform:translateY(-8px);box-shadow:0 0 30px rgba(192,132,252,.6),0 0 15px rgba(236,72,153,.5);}
.project-card::before{content:'';position:absolute;top:0;left:-120%;width:55%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.04),transparent);transition:left .6s ease;pointer-events:none;z-index:2;}
.project-card:hover::before{left:160%;}
.img-count-badge{position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);color:#e5e7eb;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:9999px;display:flex;align-items:center;gap:3px;z-index:3;}

/* ═══ CARD CIRCULAR LINK ICONS ═══ */
.card-link-icons{
  position:absolute;bottom:8px;right:8px;
  display:flex;flex-direction:row;gap:5px;
  z-index:4;pointer-events:none;
}
.project-card:hover .card-link-icons{pointer-events:auto;}
.card-icon-btn{
  width:26px;height:26px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:.68rem;color:#fff;
  text-decoration:none;
  border:1px solid rgba(255,255,255,.2);
  backdrop-filter:blur(8px);
  opacity:0;transform:translateY(6px) scale(.75);
  transition:opacity .28s ease, transform .3s cubic-bezier(.34,1.56,.64,1),
             box-shadow .22s ease;
  position:relative;
}
.project-card:hover .card-icon-btn:nth-child(1){opacity:1;transform:translateY(0) scale(1);transition-delay:.04s;}
.project-card:hover .card-icon-btn:nth-child(2){opacity:1;transform:translateY(0) scale(1);transition-delay:.10s;}
.project-card:hover .card-icon-btn:nth-child(3){opacity:1;transform:translateY(0) scale(1);transition-delay:.16s;}
.card-icon-btn:hover{transform:translateY(-3px) scale(1.18)!important;}
.cicon-github {background:linear-gradient(145deg,#2d333b,#1c2128);box-shadow:0 2px 8px rgba(0,0,0,.55);}
.cicon-github:hover {box-shadow:0 3px 12px rgba(150,180,255,.4);}
.cicon-youtube{background:linear-gradient(145deg,#d93025,#8b0000);box-shadow:0 2px 8px rgba(200,40,30,.45);}
.cicon-youtube:hover{box-shadow:0 3px 12px rgba(255,60,60,.55);}
.cicon-drive  {background:conic-gradient(#fbbc04 0deg 120deg,#34a853 120deg 240deg,#4285f4 240deg 360deg);box-shadow:0 2px 8px rgba(26,115,232,.4);}
.cicon-drive:hover  {box-shadow:0 3px 12px rgba(66,153,225,.55);}
.card-icon-btn::after{display:none;}

/* ═══ GLASS / INPUT / BUTTON ═══ */
.glass-backdrop{background:rgba(17,24,39,.75);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.1);}
.input-field{background-color:rgba(10,10,25,.8);border:1px solid rgba(255,255,255,.1);color:#fff;transition:all .3s ease;width:100%;padding:.75rem 1rem;border-radius:.5rem;}
.input-field:focus{border-color:#a855f7;box-shadow:0 0 20px rgba(168,85,247,.5);outline:none;}
.gradient-button{background:linear-gradient(90deg,#8B5CF6 0%,#EC4899 100%);transition:all .4s ease-in-out;box-shadow:0 0 25px rgba(236,72,153,.5);padding:.75rem 1rem;border-radius:.75rem;font-weight:600;width:100%;text-align:center;}
.gradient-button:hover{background:linear-gradient(90deg,#EC4899 0%,#8B5CF6 100%);transform:scale(1.03);}
#projectSearchInput{transition:all .3s ease;background:#1f2937;border:1px solid #374151;}
#projectSearchInput:focus{border-color:#a855f7;box-shadow:0 0 25px rgba(168,85,247,.6);}

/* ═══ TAG BADGES ═══ */
.tag-badge{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;padding:3px 9px;border-radius:9999px;letter-spacing:.03em;border:1px solid transparent;white-space:nowrap;transition:transform .2s,box-shadow .2s;}
.tag-badge:hover{transform:scale(1.08);}
.tag-national     {background:linear-gradient(135deg,rgba(99,102,241,.22),rgba(59,130,246,.22));border-color:#6366f1;color:#a5b4fc;box-shadow:0 0 7px rgba(99,102,241,.35);}
.tag-international{background:linear-gradient(135deg,rgba(234,179,8,.22),rgba(245,158,11,.22));border-color:#eab308;color:#fde68a;box-shadow:0 0 7px rgba(234,179,8,.35);}
.tag-provincial   {background:linear-gradient(135deg,rgba(34,197,94,.22),rgba(16,185,129,.22));border-color:#22c55e;color:#86efac;box-shadow:0 0 7px rgba(34,197,94,.35);}
.tag-school       {background:linear-gradient(135deg,rgba(236,72,153,.22),rgba(244,114,182,.22));border-color:#ec4899;color:#f9a8d4;box-shadow:0 0 7px rgba(236,72,153,.35);}
.tag-activity     {background:linear-gradient(135deg,rgba(251,146,60,.22),rgba(249,115,22,.22));border-color:#fb923c;color:#fed7aa;box-shadow:0 0 7px rgba(251,146,60,.35);}

/* ═══ MULTI-TAG GRID ═══ */
.multi-tag-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;}
.tag-toggle-label{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:12px;border:2px solid rgba(255,255,255,.07);background:rgba(10,10,25,.7);cursor:pointer;transition:all .22s ease;user-select:none;}
.tag-toggle-label:hover{border-color:rgba(168,85,247,.4);background:rgba(30,20,60,.75);}
.tag-toggle-label input[type=checkbox]{display:none;}
.check-icon{width:22px;height:22px;min-width:22px;border-radius:6px;border:2px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:900;color:transparent;transition:all .2s ease;}
.tag-toggle-label.checked-national     {border-color:#6366f1;background:rgba(99,102,241,.15);box-shadow:0 0 12px rgba(99,102,241,.3);}
.tag-toggle-label.checked-international{border-color:#eab308;background:rgba(234,179,8,.15);box-shadow:0 0 12px rgba(234,179,8,.3);}
.tag-toggle-label.checked-provincial   {border-color:#22c55e;background:rgba(34,197,94,.15);box-shadow:0 0 12px rgba(34,197,94,.3);}
.tag-toggle-label.checked-school       {border-color:#ec4899;background:rgba(236,72,153,.15);box-shadow:0 0 12px rgba(236,72,153,.3);}
.tag-toggle-label.checked-activity     {border-color:#fb923c;background:rgba(251,146,60,.15);box-shadow:0 0 12px rgba(251,146,60,.3);}
.tag-toggle-label.checked-national      .check-icon{background:#6366f1;border-color:#6366f1;color:#fff;}
.tag-toggle-label.checked-international .check-icon{background:#eab308;border-color:#eab308;color:#fff;}
.tag-toggle-label.checked-provincial    .check-icon{background:#22c55e;border-color:#22c55e;color:#fff;}
.tag-toggle-label.checked-school        .check-icon{background:#ec4899;border-color:#ec4899;color:#fff;}
.tag-toggle-label.checked-activity      .check-icon{background:#fb923c;border-color:#fb923c;color:#fff;}
@media(max-width:640px){
  .multi-tag-grid{gap:6px;}
  .tag-toggle-label{padding:8px 10px;gap:7px;border-radius:10px;}
  .check-icon{width:18px;height:18px;min-width:18px;font-size:10px;border-radius:4px;}
}

/* ═══ LINK INPUT ROW (Admin form) ═══ */
.link-input-row{display:flex;align-items:center;gap:10px;background:rgba(10,10,25,.6);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:8px 12px;transition:border-color .2s;}
.link-input-row:focus-within{border-color:rgba(168,85,247,.45);box-shadow:0 0 12px rgba(168,85,247,.15);}
.link-icon-box{width:34px;height:34px;min-width:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.lib-github {background:linear-gradient(135deg,#24292e,#444d56);box-shadow:0 2px 8px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.08);}
.lib-youtube{background:linear-gradient(135deg,#c4302b,#ff0000);box-shadow:0 2px 8px rgba(196,48,43,.5),inset 0 1px 0 rgba(255,200,200,.1);}
.lib-drive  {background:linear-gradient(135deg,#1a73e8,#34a853);box-shadow:0 2px 8px rgba(26,115,232,.4),inset 0 1px 0 rgba(255,255,255,.1);}
.link-input-row input{background:transparent;border:none;color:#e2e8f0;font-size:.82rem;outline:none;flex:1;min-width:0;}
.link-input-row input::placeholder{color:#4b5563;}

/* ═══ FILTER CHIPS ═══ */
.filter-section{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:12px 16px;}
.chips-wrap{position:relative;}
.chips-scroll{display:flex;flex-wrap:wrap;gap:8px;}
@media(max-width:640px){
  .chips-scroll{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;padding-bottom:2px;}
  .chips-scroll::-webkit-scrollbar{display:none;}
  .chips-wrap::after{content:'';position:absolute;right:0;top:0;bottom:0;width:32px;background:linear-gradient(to right,transparent,rgba(10,5,30,.85));pointer-events:none;border-radius:0 16px 16px 0;}
}
.filter-chip{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:9999px;font-size:.78rem;font-weight:700;border:1.5px solid transparent;cursor:pointer;transition:all .22s ease;user-select:none;letter-spacing:.02em;white-space:nowrap;flex-shrink:0;}
.filter-chip:hover{transform:translateY(-2px) scale(1.04);}
.chip-national     {border-color:rgba(99,102,241,.4); color:#a5b4fc;background:rgba(99,102,241,.07);}
.chip-international{border-color:rgba(234,179,8,.4);  color:#fde68a;background:rgba(234,179,8,.07);}
.chip-provincial   {border-color:rgba(34,197,94,.4);  color:#86efac;background:rgba(34,197,94,.07);}
.chip-school       {border-color:rgba(236,72,153,.4); color:#f9a8d4;background:rgba(236,72,153,.07);}
.chip-activity     {border-color:rgba(251,146,60,.4);  color:#fed7aa;background:rgba(251,146,60,.07);}
.filter-chip.active.chip-national     {background:rgba(99,102,241,.28);border-color:#6366f1;box-shadow:0 0 14px rgba(99,102,241,.6);}
.filter-chip.active.chip-international{background:rgba(234,179,8,.28); border-color:#eab308;box-shadow:0 0 14px rgba(234,179,8,.6);}
.filter-chip.active.chip-provincial   {background:rgba(34,197,94,.28); border-color:#22c55e;box-shadow:0 0 14px rgba(34,197,94,.6);}
.filter-chip.active.chip-school       {background:rgba(236,72,153,.28);border-color:#ec4899;box-shadow:0 0 14px rgba(236,72,153,.6);}
.filter-chip.active.chip-activity     {background:rgba(251,146,60,.28);border-color:#fb923c;box-shadow:0 0 14px rgba(251,146,60,.6);}
.clear-chips-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:9999px;border:1.5px solid rgba(239,68,68,.35);color:#fca5a5;background:rgba(239,68,68,.06);font-size:.75rem;font-weight:700;cursor:pointer;transition:all .2s ease;white-space:nowrap;flex-shrink:0;}
.clear-chips-btn:hover{background:rgba(239,68,68,.18);border-color:rgba(239,68,68,.65);}
@keyframes popIn{0%{transform:scale(.6);opacity:0}70%{transform:scale(1.15)}100%{transform:scale(1);opacity:1}}
.count-pop{animation:popIn .3s ease forwards;}
.result-pill{background:rgba(168,85,247,.12);border:1px solid rgba(168,85,247,.28);border-radius:9999px;padding:5px 14px;font-size:.78rem;color:#c4b5fd;display:inline-flex;align-items:center;gap:6px;}
.edit-badge{position:absolute;top:10px;right:10px;background:linear-gradient(135deg,#7c3aed,#db2777);color:#fff;font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:9999px;opacity:0;transform:translateY(-4px);transition:all .25s ease;z-index:3;}
.project-card:hover .edit-badge{opacity:1;transform:translateY(0);}

/* ═══ MODAL ═══ */
.modal-overlay{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;z-index:300;padding:1.25rem;}
@media(max-width:640px){.modal-overlay{align-items:flex-start;padding:4rem .75rem 1rem;}}
.modal-box{transition:opacity .3s ease,transform .3s cubic-bezier(.25,.46,.45,.94);}
#viewBox{scrollbar-width:thin;scrollbar-color:#a855f7 rgba(17,24,39,.5);}
#viewBox::-webkit-scrollbar{width:6px;}
#viewBox::-webkit-scrollbar-track{background:rgba(17,24,39,.4);border-radius:9999px;}
#viewBox::-webkit-scrollbar-thumb{background:#a855f7;border-radius:9999px;}

/* ═══ CAROUSEL ═══ */
.carousel-wrapper{position:relative;overflow:hidden;border-radius:12px;border:2px solid rgba(168,85,247,.35);background:#000;width:100%;}
.carousel-track{display:flex;transition:transform .6s cubic-bezier(.25,.46,.45,.94);will-change:transform;}
.carousel-slide{min-width:100%;display:flex;align-items:center;justify-content:center;}
.carousel-slide img{width:100%;height:auto;max-height:60vh;object-fit:contain;display:block;user-select:none;-webkit-user-drag:none;}
.carousel-btn{position:absolute;top:50%;transform:translateY(-50%);z-index:5;width:38px;height:38px;border-radius:50%;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.15);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.85rem;cursor:pointer;transition:all .2s;opacity:0;}
.carousel-wrapper:hover .carousel-btn{opacity:1;}
.carousel-btn:hover{background:rgba(168,85,247,.7);border-color:#a855f7;transform:translateY(-50%) scale(1.1);}
.carousel-btn-prev{left:10px;}.carousel-btn-next{right:10px;}
.carousel-dots{display:flex;gap:6px;justify-content:center;margin-top:10px;flex-wrap:wrap;}
.carousel-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.15);cursor:pointer;transition:all .3s;padding:0;}
.carousel-dot.active{background:#a855f7;border-color:#a855f7;box-shadow:0 0 8px rgba(168,85,247,.8);transform:scale(1.3);}
.carousel-progress{position:absolute;bottom:0;left:0;height:3px;background:linear-gradient(90deg,#8b5cf6,#ec4899);border-radius:0 3px 3px 0;transition:width linear;}
.carousel-counter{position:absolute;top:10px;left:10px;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);color:#e5e7eb;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:9999px;z-index:4;}
.carousel-label{position:absolute;top:10px;right:10px;background:linear-gradient(135deg,rgba(139,92,246,.8),rgba(236,72,153,.8));color:#fff;font-size:.65rem;font-weight:800;padding:3px 10px;border-radius:9999px;z-index:4;letter-spacing:.04em;}
.carousel-single .carousel-btn,.carousel-single .carousel-dots,.carousel-single .carousel-counter,.carousel-single .carousel-progress,.carousel-single .carousel-label{display:none!important;}

/* ═══ LINK BUTTONS IN MODAL ═══ */
.link-btn-row{
  display:flex;gap:16px;justify-content:center;align-items:center;
  flex-wrap:wrap;margin-bottom:16px;
}
.link-btn{
  position:relative;
  display:inline-flex;align-items:center;justify-content:center;
  width:48px;height:48px;border-radius:50%;
  text-decoration:none;color:#fff;cursor:pointer;
  border:1.5px solid rgba(255,255,255,.15);
  overflow:visible;
  isolation:isolate;
  transition:transform .3s cubic-bezier(.34,1.56,.64,1), filter .2s ease;
  flex-shrink:0;
}
.link-btn:hover{transform:translateY(-5px) scale(1.12);filter:brightness(1.15);}
.link-btn:active{transform:scale(.93);}
.lbtn-gloss{
  position:absolute;inset:0;border-radius:50%;
  overflow:hidden;pointer-events:none;
  background:inherit;
}
.lbtn-gloss::after{
  content:'';position:absolute;top:0;left:0;right:0;height:50%;
  background:linear-gradient(180deg,rgba(255,255,255,.22) 0%,transparent 100%);
  border-radius:50% 50% 0 0;
}
.link-btn i{font-size:1.2rem;position:relative;z-index:2;}
.lbtn-tip{
  position:absolute;bottom:calc(100% + 8px);left:50%;
  transform:translateX(-50%) translateY(4px);
  background:rgba(10,10,22,.92);backdrop-filter:blur(8px);
  color:#e2e8f0;font-size:.6rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
  padding:3px 9px;border-radius:6px;white-space:nowrap;
  border:1px solid rgba(255,255,255,.1);
  pointer-events:none;opacity:0;
  transition:opacity .18s ease, transform .18s ease;
  z-index:10;
}
.link-btn:hover .lbtn-tip{opacity:1;transform:translateX(-50%) translateY(0);}
.lbtn-github{background:linear-gradient(145deg,#3a3f47,#1c2128);box-shadow:0 4px 14px rgba(0,0,0,.5);}
.lbtn-github:hover{box-shadow:0 6px 20px rgba(0,0,0,.6),0 0 16px rgba(180,200,255,.2);}
.lbtn-youtube{background:linear-gradient(145deg,#e8302a,#7a0000);box-shadow:0 4px 14px rgba(160,20,10,.45);}
.lbtn-youtube:hover{box-shadow:0 6px 20px rgba(200,30,20,.55),0 0 20px rgba(255,60,50,.3);}
.lbtn-drive{background:conic-gradient(#fbbc04 0deg 120deg,#34a853 120deg 240deg,#4285f4 240deg 360deg);box-shadow:0 4px 14px rgba(26,115,232,.35);}
.lbtn-drive:hover{box-shadow:0 6px 20px rgba(26,115,232,.5),0 0 20px rgba(66,180,130,.25);}
@keyframes iconPop{0%{transform:scale(0) rotate(-20deg);opacity:0}75%{transform:scale(1.12) rotate(4deg)}100%{transform:scale(1) rotate(0deg);opacity:1}}
.link-btn{animation:iconPop .4s cubic-bezier(.34,1.56,.64,1) both;}
.link-btn:nth-child(2){animation-delay:.07s;}
.link-btn:nth-child(3){animation-delay:.14s;}

/* ═══ FORM HELPERS ═══ */
.form-section-label{font-size:.7rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#6b7280;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.form-section-label::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.06);}
.webp-hint{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;color:#6ee7b7;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:6px;padding:2px 8px;margin-top:4px;}

/* ═══ THUMB STRIP ═══ */
.exist-thumb-wrap{position:relative;display:inline-block;border-radius:8px;overflow:hidden;border:2px solid rgba(255,255,255,.1);}
.exist-thumb-wrap img{width:80px;height:60px;object-fit:cover;display:block;}
.exist-thumb-wrap .thumb-del{position:absolute;top:2px;right:2px;width:18px;height:18px;border-radius:50%;background:rgba(220,38,38,.85);color:#fff;font-size:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .2s;}
.exist-thumb-wrap .thumb-del:hover{transform:scale(1.2);}
.exist-thumb-wrap.deleted{opacity:.3;filter:grayscale(1);}
.exist-thumb-wrap .thumb-label{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.6);color:#e5e7eb;font-size:.55rem;font-weight:700;text-align:center;padding:2px;}

/* ═══ NO RESULTS ═══ */
@keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.float-em{animation:floatY 2.5s ease-in-out infinite;display:inline-block;}
</style>
</head>
<body class="text-white min-h-screen" data-is-admin="<?= $is_admin ? 'true' : 'false' ?>">

<?php include 'navbar.php'; ?>

<section class="py-12 px-4">
<div class="max-w-7xl mx-auto">

  <h2 class="text-3xl font-semibold text-center text-purple-300 mb-6"
      style="text-shadow:0 0 10px rgba(192,132,252,.5)">My Projects</h2>

  <!-- SEARCH -->
  <div class="mb-5 max-w-lg mx-auto">
    <input type="search" id="projectSearchInput"
           placeholder="🔍 ค้นหาโปรเจกต์ (เช่น Roblox, Web, Game)..."
           class="w-full p-3 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-1">
  </div>

  <!-- TAG FILTER (User) -->
  <?php if (!$is_admin): ?>
  <div class="mb-6 max-w-4xl mx-auto">
    <div class="filter-section">
      <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 flex items-center gap-2">
        <i class="fa-solid fa-tags text-purple-600"></i> Filter by Tag
      </p>
      <div class="chips-wrap">
        <div class="chips-scroll">
          <button class="filter-chip chip-national"      data-tag="ระดับประเทศ">🏆 ระดับประเทศ</button>
          <button class="filter-chip chip-international" data-tag="ระดับนานาชาติ">🌏 ระดับนานาชาติ</button>
          <button class="filter-chip chip-provincial"    data-tag="ระดับจังหวัด">🏛️ ระดับจังหวัด</button>
          <button class="filter-chip chip-school"        data-tag="งานในโรงเรียน">🏫 งานในโรงเรียน</button>
          <button class="filter-chip chip-activity"      data-tag="เข้าร่วมกิจกรรม">🎯 เข้าร่วมกิจกรรม</button>
          <button id="clearTagBtn" class="clear-chips-btn hidden"><i class="fa-solid fa-xmark"></i> ล้าง</button>
        </div>
      </div>
    </div>
    <div id="resultBar" class="mt-3 flex justify-start hidden px-1">
      <div class="result-pill">
        <i class="fa-solid fa-filter text-purple-400 text-xs"></i>
        <span id="resultText">แสดง 0 โปรเจกต์</span>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══════════ ADMIN ADD FORM ══════════ -->
  <?php if ($is_admin): ?>
  <div class="glass-backdrop p-6 rounded-2xl shadow-lg mb-8 max-w-2xl mx-auto">
    <h3 class="text-2xl font-semibold text-pink-400 mb-5 flex items-center gap-2">
      <i class="fa-solid fa-plus-circle"></i> Add New Project
    </h3>
    <form method="POST" action="projects.php" enctype="multipart/form-data" class="space-y-5" id="addForm">
      <input type="hidden" name="add_project" value="1">

      <div>
        <p class="form-section-label"><i class="fa-solid fa-info-circle"></i> ข้อมูลโปรเจกต์</p>
        <div class="space-y-3">
          <input type="text" name="project_name" placeholder="ชื่อโปรเจกต์" class="input-field" required>
          <textarea name="description" placeholder="คำอธิบายโปรเจกต์" class="input-field min-h-[90px]" required></textarea>
        </div>
      </div>

      <div>
        <p class="form-section-label"><i class="fa-solid fa-tags"></i> ระดับ / Tag</p>
        <div class="multi-tag-grid" id="addTagGrid">
          <?php foreach ($tagDefs as $tv=>$td): ?>
          <label class="tag-toggle-label" data-key="<?=$td['key']?>" onclick="toggleTagLabel(this,'<?=$td['key']?>')">
            <input type="checkbox" name="tags[]" value="<?=$tv?>">
            <div class="check-icon">✓</div>
            <span class="tag-badge <?=$td['cls']?> pointer-events-none"><?=$td['icon']?> <?=$tv?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <p id="addTagError" class="text-red-400 text-xs mt-2 hidden">⚠️ กรุณาเลือกอย่างน้อย 1 Tag</p>
      </div>

      <!-- LINKS -->
      <div>
        <p class="form-section-label"><i class="fa-solid fa-link"></i> ลิงก์โปรเจกต์ <span class="text-gray-600 font-normal normal-case tracking-normal">(ไม่บังคับ)</span></p>
        <div class="space-y-2">
          <div class="link-input-row">
            <div class="link-icon-box lib-github"><i class="fa-brands fa-github text-white"></i></div>
            <input type="url" name="link_github" placeholder="https://github.com/username/repo">
          </div>
          <div class="link-input-row">
            <div class="link-icon-box lib-youtube"><i class="fa-brands fa-youtube text-white"></i></div>
            <input type="url" name="link_youtube" placeholder="https://youtube.com/watch?v=...">
          </div>
          <div class="link-input-row">
            <div class="link-icon-box lib-drive"><i class="fa-brands fa-google-drive text-white"></i></div>
            <input type="url" name="link_drive" placeholder="https://drive.google.com/...">
          </div>
        </div>
      </div>

      <div>
        <p class="form-section-label"><i class="fa-solid fa-images"></i> รูปภาพ (สูงสุด 5 รูป)</p>
        <p class="text-xs text-gray-500 mb-2">🖼️ รูปแรก = เกียรติบัตร | รูปถัดไป = ภาพกิจกรรม</p>
        <input type="file" name="files[]" accept="image/*" multiple
               class="input-field file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0
                      file:text-sm file:font-semibold file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200"
               onchange="previewAddImages(this)" required>
        <div class="webp-hint mt-1"><i class="fa-solid fa-bolt"></i> แปลงเป็น WebP อัตโนมัติ — jpg/png/gif/webp ≤ 20 MB</div>
        <div id="addPreviewRow" class="flex flex-wrap gap-2 mt-3"></div>
      </div>

      <button type="button" onclick="submitWithTagCheck('addTagGrid','addTagError','addForm')"
              class="gradient-button flex items-center justify-center gap-2">
        <i class="fa-solid fa-upload"></i> Upload Project
      </button>
    </form>
    <?php if (!empty($error_message)): ?>
      <div class="mt-4 p-3 rounded-lg bg-red-900/30 border border-red-700/40 text-red-300 text-sm text-center">
        <i class="fa-solid fa-circle-exclamation mr-1"></i><?=htmlspecialchars($error_message)?>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ══════════ PROJECT GRID ══════════ -->
  <div id="projectGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <?php foreach ($projects as $p):
      $images   = decodeImages($p['image'] ?? '');
      $firstImg = $images[0] ?? 'assets/no-image.png';
      $imgCount = count($images);
      $tags     = decodeTags($p['tag'] ?? '');
      $links    = decodeLinks($p['links'] ?? '');
      $tagsJson = htmlspecialchars(json_encode($tags,  JSON_UNESCAPED_UNICODE),ENT_QUOTES,'UTF-8');
      $imgsJson = htmlspecialchars(json_encode($images, JSON_UNESCAPED_UNICODE),ENT_QUOTES,'UTF-8');
      $linksJson= htmlspecialchars(json_encode($links,  JSON_UNESCAPED_UNICODE),ENT_QUOTES,'UTF-8');
      $tagsCSV  = htmlspecialchars(implode(',',$tags));
    ?>
    <div class="project-card group scroll-reveal p-2 cursor-pointer"
         data-id="<?=$p['id']?>"
         data-name="<?=htmlspecialchars($p['name'])?>"
         data-description="<?=htmlspecialchars($p['description'])?>"
         data-images="<?=$imgsJson?>"
         data-image="<?=htmlspecialchars($firstImg)?>"
         data-tags="<?=$tagsCSV?>"
         data-tags-json="<?=$tagsJson?>"
         data-links="<?=$linksJson?>">

      <div class="overflow-hidden rounded-lg mb-3 relative">
        <img src="<?=htmlspecialchars($firstImg)?>"
             class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-105"
             loading="lazy" onerror="this.src='assets/no-image.png'">
        <?php if($imgCount>1):?>
        <div class="img-count-badge"><i class="fa-solid fa-images" style="font-size:.6rem"></i> <?=$imgCount?></div>
        <?php endif;?>

        <!-- circular link icons -->
        <?php if(!empty($links)):?>
        <div class="card-link-icons" onclick="event.stopPropagation()">
          <?php if(!empty($links['github'])):?>
            <a href="<?=htmlspecialchars($links['github'])?>" target="_blank" rel="noopener"
               class="card-icon-btn cicon-github">
              <i class="fa-brands fa-github"></i>
            </a>
          <?php endif;?>
          <?php if(!empty($links['youtube'])):?>
            <a href="<?=htmlspecialchars($links['youtube'])?>" target="_blank" rel="noopener"
               class="card-icon-btn cicon-youtube">
              <i class="fa-brands fa-youtube"></i>
            </a>
          <?php endif;?>
          <?php if(!empty($links['drive'])):?>
            <a href="<?=htmlspecialchars($links['drive'])?>" target="_blank" rel="noopener"
               class="card-icon-btn cicon-drive">
              <i class="fa-brands fa-google-drive"></i>
            </a>
          <?php endif;?>
        </div>
        <?php endif;?>
      </div>

      <div class="px-2 mb-1 flex flex-wrap gap-1">
        <?php foreach($tags as $t):$ti=getTagInfo($t);?>
          <span class="tag-badge <?=$ti['cls']?>"><?=$ti['icon']?> <?=htmlspecialchars($t)?></span>
        <?php endforeach;?>
      </div>
      <h3 class="text-lg font-semibold text-purple-400 px-2"
          style="font-family:'Noto Sans Thai',sans-serif;font-weight:600;line-height:1.5;"><?=htmlspecialchars($p['name'])?></h3>
      <p class="text-gray-400 line-clamp-2 px-2 pb-2 text-sm"
         style="font-family:'Noto Sans Thai',sans-serif;line-height:1.7;"><?=htmlspecialchars($p['description'])?></p>
      <?php if($is_admin):?><div class="edit-badge"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</div><?php endif;?>
    </div>
    <?php endforeach;?>
  </div>

  <div id="noResultsMessage" class="hidden text-center text-gray-400 mt-12 pb-8">
    <div class="float-em text-5xl mb-4">😥</div>
    <h3 class="text-2xl font-semibold">ไม่พบโปรเจกต์ที่ค้นหา</h3>
    <p class="text-gray-600 mt-1 text-sm">ลองเปลี่ยนคำค้นหา หรือกด "ล้าง"</p>
  </div>
</div>
</section>


<!-- ═══════════════════════════════════════
     MODAL: VIEW (User)
═══════════════════════════════════════ -->
<div id="projectModal" class="modal-overlay bg-black/0 hidden transition-all duration-300">
  <div id="viewBox" class="glass-backdrop p-5 sm:p-7 rounded-2xl shadow-2xl w-full relative modal-box opacity-0 -translate-y-10 scale-95"
       style="max-width:min(600px, calc(100vw - 2rem)); max-height:88vh; overflow-y:auto;">

    <button id="closeModalButton"
            class="sticky top-0 right-0 float-right text-gray-400 hover:text-white text-3xl leading-none z-10 transition">&times;</button>

    <div class="flex flex-col items-center clear-both">
      <div class="w-full mb-4">
        <div class="carousel-wrapper" id="viewCarouselWrapper">
          <div class="carousel-track"    id="viewCarouselTrack"></div>
          <div class="carousel-counter"  id="viewCarouselCounter">1 / 1</div>
          <div class="carousel-label"    id="viewCarouselLabel">📜 เกียรติบัตร</div>
          <div class="carousel-progress" id="viewCarouselProgress" style="width:0%"></div>
          <button class="carousel-btn carousel-btn-prev" onclick="carouselGo(-1)"><i class="fa-solid fa-chevron-left"></i></button>
          <button class="carousel-btn carousel-btn-next" onclick="carouselGo(1)"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
        <div class="carousel-dots" id="viewCarouselDots"></div>
      </div>

      <div class="link-btn-row" id="modalLinkRow"></div>
      <div id="modalTagRow" class="flex flex-wrap gap-2 justify-center mb-3"></div>
      <h3 id="modalName"
          class="text-2xl sm:text-3xl font-bold text-purple-300 text-center mb-3"
          style="font-family:'Noto Sans Thai',sans-serif;font-weight:700;line-height:1.4;"></h3>
      <p id="modalDescription"
         class="text-gray-300 text-base sm:text-lg text-center leading-relaxed mb-8"
         style="font-family:'Noto Sans Thai',sans-serif;line-height:1.8;"></p>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════
     MODAL: EDIT (Admin)
═══════════════════════════════════════ -->
<div id="editModal" class="modal-overlay bg-black/0 hidden transition-all duration-300">
  <div id="editBox" class="glass-backdrop p-5 sm:p-6 rounded-2xl shadow-2xl max-w-xl w-full relative
              modal-box opacity-0 -translate-y-10 scale-95 max-h-[92vh] overflow-y-auto">

    <div class="flex items-center justify-between mb-5">
      <h3 class="text-xl font-bold text-purple-300 flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg bg-purple-600/30 flex items-center justify-center">
          <i class="fa-solid fa-pen-to-square text-purple-400 text-sm"></i>
        </span>Edit Project
      </h3>
      <button id="closeEditModalButton"
              class="w-8 h-8 rounded-full bg-gray-700/50 hover:bg-gray-600 flex items-center justify-center text-gray-400 hover:text-white transition text-lg">&times;</button>
    </div>

    <form method="POST" action="projects.php" enctype="multipart/form-data" class="space-y-4" id="editForm">
      <input type="hidden" name="edit_project" value="1">
      <input type="hidden" id="edit_project_id" name="project_id">
      <input type="hidden" id="edit_image_json" name="existing_image_json">

      <div>
        <p class="form-section-label"><i class="fa-solid fa-info-circle"></i> ข้อมูลโปรเจกต์</p>
        <div class="space-y-3">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">ชื่อโปรเจกต์</label>
            <input type="text" id="edit_project_name" name="project_name" class="input-field" required>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">คำอธิบาย</label>
            <textarea id="edit_description" name="description" class="input-field min-h-[80px]" required></textarea>
          </div>
        </div>
      </div>

      <div>
        <p class="form-section-label"><i class="fa-solid fa-tags"></i> ระดับ / Tag</p>
        <div class="multi-tag-grid" id="editTagGrid">
          <?php foreach($tagDefs as $tv=>$td):?>
          <label class="tag-toggle-label" data-key="<?=$td['key']?>" onclick="toggleTagLabel(this,'<?=$td['key']?>')">
            <input type="checkbox" name="tags[]" value="<?=$tv?>">
            <div class="check-icon">✓</div>
            <span class="tag-badge <?=$td['cls']?> pointer-events-none text-xs"><?=$td['icon']?> <?=$tv?></span>
          </label>
          <?php endforeach;?>
        </div>
        <p id="editTagError" class="text-red-400 text-xs mt-2 hidden">⚠️ กรุณาเลือกอย่างน้อย 1 Tag</p>
      </div>

      <div>
        <p class="form-section-label"><i class="fa-solid fa-link"></i> ลิงก์โปรเจกต์</p>
        <div class="space-y-2">
          <div class="link-input-row">
            <div class="link-icon-box lib-github"><i class="fa-brands fa-github text-white"></i></div>
            <input type="url" id="edit_link_github"  name="link_github"  placeholder="https://github.com/...">
          </div>
          <div class="link-input-row">
            <div class="link-icon-box lib-youtube"><i class="fa-brands fa-youtube text-white"></i></div>
            <input type="url" id="edit_link_youtube" name="link_youtube" placeholder="https://youtube.com/...">
          </div>
          <div class="link-input-row">
            <div class="link-icon-box lib-drive"><i class="fa-brands fa-google-drive text-white"></i></div>
            <input type="url" id="edit_link_drive"   name="link_drive"   placeholder="https://drive.google.com/...">
          </div>
        </div>
        <p class="text-xs text-gray-600 mt-1.5">* ลบ URL ออกแล้ว Save = ลบลิงก์นั้น</p>
      </div>

      <div>
        <p class="form-section-label"><i class="fa-solid fa-images"></i> รูปภาพปัจจุบัน</p>
        <div id="editExistingThumbs" class="flex flex-wrap gap-2 mb-3"></div>
        <p class="form-section-label"><i class="fa-solid fa-plus"></i> เพิ่มรูปใหม่ (ไม่บังคับ)</p>
        <input type="file" name="files[]" accept="image/*" multiple
               class="input-field file:mr-3 file:py-1.5 file:px-4 file:rounded-full file:border-0
                      file:text-xs file:font-semibold file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200"
               onchange="previewNewImages(this)">
        <div class="webp-hint mt-1"><i class="fa-solid fa-bolt"></i> แปลงเป็น WebP อัตโนมัติ</div>
        <div id="editNewPreviewRow" class="flex flex-wrap gap-2 mt-3"></div>
      </div>

      <button type="button" onclick="submitWithTagCheck('editTagGrid','editTagError','editForm')"
              class="gradient-button flex items-center justify-center gap-2">
        <i class="fa-solid fa-save"></i> Save Changes
      </button>
    </form>

    <div class="mt-5 p-4 rounded-xl border border-red-900/40 bg-red-950/20">
      <p class="form-section-label text-red-600/70 mb-3"><i class="fa-solid fa-triangle-exclamation text-red-600"></i> Danger Zone</p>
      <form method="POST" action="projects.php" onsubmit="return confirm('ลบโปรเจกต์นี้? ไม่สามารถย้อนกลับได้!');">
        <input type="hidden" name="delete_project"  value="1">
        <input type="hidden" id="delete_project_id" name="project_id">
        <input type="hidden" id="delete_image_json" name="existing_image_json">
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 bg-red-700/80 hover:bg-red-600
                       text-white py-2.5 px-4 rounded-lg font-semibold transition-all text-sm">
          <i class="fa-solid fa-trash"></i> Delete Project
        </button>
      </form>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════ -->
<script>
/* ─── TAG HELPERS ─── */
const TAG_META={
  'ระดับประเทศ'    :{cls:'tag-national',      icon:'🏆'},
  'ระดับนานาชาติ'  :{cls:'tag-international', icon:'🌏'},
  'ระดับจังหวัด'   :{cls:'tag-provincial',    icon:'🏛️'},
  'งานในโรงเรียน'  :{cls:'tag-school',         icon:'🏫'},
  'เข้าร่วมกิจกรรม':{cls:'tag-activity',      icon:'🎯'},
};
function toggleTagLabel(el,key){
  const cb=el.querySelector('input[type="checkbox"]');cb.checked=!cb.checked;
  ['national','international','provincial','school','activity'].forEach(k=>el.classList.remove('checked-'+k));
  if(cb.checked)el.classList.add('checked-'+key);
}
function submitWithTagCheck(gId,eId,fId){
  const n=document.getElementById(gId).querySelectorAll('input:checked').length;
  const e=document.getElementById(eId);
  if(!n){e.classList.remove('hidden');e.scrollIntoView({behavior:'smooth',block:'center'});return;}
  e.classList.add('hidden');document.getElementById(fId).submit();
}
function setEditTagGrid(sel){
  document.getElementById('editTagGrid').querySelectorAll('.tag-toggle-label').forEach(l=>{
    const cb=l.querySelector('input');const k=l.dataset.key;const on=sel.includes(cb.value);
    cb.checked=on;
    ['national','international','provincial','school','activity'].forEach(k2=>l.classList.remove('checked-'+k2));
    if(on)l.classList.add('checked-'+k);
  });
}
function makeBadge(tag){
  const m=TAG_META[tag]||{cls:'tag-school',icon:'📌'};
  const s=document.createElement('span');s.className='tag-badge '+m.cls;s.textContent=m.icon+' '+tag;return s;
}

/* ─── FILE PREVIEWS ─── */
function previewAddImages(input){
  const row=document.getElementById('addPreviewRow');row.innerHTML='';
  const lbs=['📜 เกียรติบัตร','🖼️ รูปที่ 2','🖼️ รูปที่ 3','🖼️ รูปที่ 4','🖼️ รูปที่ 5'];
  [...input.files].slice(0,5).forEach((f,i)=>{
    const r=new FileReader();r.onload=e=>{
      const w=document.createElement('div');w.className='exist-thumb-wrap';
      w.innerHTML=`<img src="${e.target.result}"><div class="thumb-label">${lbs[i]||'🖼️ '+(i+1)}</div>`;
      row.appendChild(w);};r.readAsDataURL(f);});
}
function previewNewImages(input){
  const row=document.getElementById('editNewPreviewRow');row.innerHTML='';
  [...input.files].slice(0,5).forEach((f,i)=>{
    const r=new FileReader();r.onload=e=>{
      const w=document.createElement('div');w.className='exist-thumb-wrap';
      w.innerHTML=`<img src="${e.target.result}"><div class="thumb-label">ใหม่ ${i+1}</div>`;
      row.appendChild(w);};r.readAsDataURL(f);});
}

/* ─── LINK BUTTON BUILDER ─── */
const LINK_DEFS={
  github :{cls:'lbtn-github',  fa:'fa-brands fa-github',       tip:'GitHub'},
  youtube:{cls:'lbtn-youtube', fa:'fa-brands fa-youtube',      tip:'YouTube'},
  drive  :{cls:'lbtn-drive',   fa:'fa-brands fa-google-drive', tip:'Drive'},
};
function buildLinkButtons(links, container){
  container.innerHTML='';
  let any=false;
  ['github','youtube','drive'].forEach(key=>{
    const url=(links||{})[key];
    if(!url) return;
    any=true;
    const d=LINK_DEFS[key];
    const a=document.createElement('a');
    a.href=url; a.target='_blank'; a.rel='noopener';
    a.className='link-btn '+d.cls;
    a.onclick=e=>e.stopPropagation();
    a.innerHTML=`<div class="lbtn-gloss"></div><i class="${d.fa}"></i><span class="lbtn-tip">${d.tip}</span>`;
    container.appendChild(a);
  });
  container.classList.toggle('hidden',!any);
}

/* ─── CAROUSEL ENGINE ─── */
let carouselImages=[], carouselIndex=0, carouselTimer=null, carouselProgressTimer=null;
const SLIDE_INTERVAL=4500, PROG_TICK=50;
const LABELS=['📜 เกียรติบัตร','🖼️ รูปกิจกรรม','🖼️ รูปกิจกรรม','🖼️ รูปกิจกรรม','🖼️ รูปกิจกรรม'];

const _track   =document.getElementById('viewCarouselTrack');
const _counter =document.getElementById('viewCarouselCounter');
const _label   =document.getElementById('viewCarouselLabel');
const _progress=document.getElementById('viewCarouselProgress');
const _dots    =document.getElementById('viewCarouselDots');
const _wrapper =document.getElementById('viewCarouselWrapper');

function buildCarousel(images){
  carouselImages=images; carouselIndex=0;
  _track.innerHTML=''; _dots.innerHTML='';
  const single=images.length<=1;
  _wrapper.classList.toggle('carousel-single',single);
  images.forEach((src,i)=>{
    const slide=document.createElement('div'); slide.className='carousel-slide';
    const img=document.createElement('img'); img.src=src; img.alt='รูปที่ '+(i+1);
    img.onerror=()=>{ img.src='assets/no-image.png'; };
    slide.appendChild(img); _track.appendChild(slide);
    if(!single){
      const dot=document.createElement('button'); dot.className='carousel-dot'+(i===0?' active':'');
      dot.addEventListener('click',()=>{ carouselGoTo(i); resetAutoSlide(); });
      _dots.appendChild(dot);
    }
  });
  updateCarouselUI(0,false);
}
function updateCarouselUI(idx,animate=true){
  const n=carouselImages.length; if(!n) return;
  carouselIndex=((idx%n)+n)%n;
  _track.style.transition=animate?'transform .6s cubic-bezier(.25,.46,.45,.94)':'none';
  _track.style.transform=`translateX(-${carouselIndex*100}%)`;
  _counter.textContent=(carouselIndex+1)+' / '+n;
  _label.textContent=LABELS[carouselIndex]||'🖼️ รูปกิจกรรม';
  _dots.querySelectorAll('.carousel-dot').forEach((d,i)=>d.classList.toggle('active',i===carouselIndex));
  resetProgressBar();
}
function resetProgressBar(){
  clearInterval(carouselProgressTimer);
  _progress.style.transition='none'; _progress.style.width='0%';
  if(carouselImages.length<=1) return;
  let elapsed=0;
  carouselProgressTimer=setInterval(()=>{
    elapsed+=PROG_TICK;
    _progress.style.transition=`width ${PROG_TICK}ms linear`;
    _progress.style.width=Math.min((elapsed/SLIDE_INTERVAL)*100,100)+'%';
    if(elapsed>=SLIDE_INTERVAL) clearInterval(carouselProgressTimer);
  },PROG_TICK);
}
function startAutoSlide(){
  if(carouselImages.length<=1) return;
  clearInterval(carouselTimer);
  carouselTimer=setInterval(()=>updateCarouselUI(carouselIndex+1,true),SLIDE_INTERVAL);
}
function stopAutoSlide(){ clearInterval(carouselTimer); clearInterval(carouselProgressTimer); carouselTimer=null; }
function resetAutoSlide(){ stopAutoSlide(); startAutoSlide(); }
function carouselGo(dir){ updateCarouselUI(carouselIndex+dir,true); resetAutoSlide(); }
function carouselGoTo(idx){ updateCarouselUI(idx,true); resetAutoSlide(); }

_wrapper.addEventListener('mouseenter',()=>{ clearInterval(carouselTimer); clearInterval(carouselProgressTimer); });
_wrapper.addEventListener('mouseleave',resetAutoSlide);
let txStart=0;
_wrapper.addEventListener('touchstart',e=>{ txStart=e.touches[0].clientX; },{passive:true});
_wrapper.addEventListener('touchend',e=>{ if(Math.abs(e.changedTouches[0].clientX-txStart)>40) carouselGo(e.changedTouches[0].clientX<txStart?1:-1); },{passive:true});

/* ─────────────────────────────────────────
   MODAL HELPERS
   ✅ FIX: เรียก nbPushDown() / nbPopUp()
      เพื่อให้ navbar ไม่บัง modal
───────────────────────────────────────── */
const viewModal=document.getElementById('projectModal');
const viewBox  =document.getElementById('viewBox');
const editModal=document.getElementById('editModal');
const editBox  =document.getElementById('editBox');

function _nbDown(){ if(typeof nbPushDown==='function') nbPushDown(); }
function _nbUp()  { if(typeof nbPopUp  ==='function') nbPopUp();   }

function showModal(m,b){
  _nbDown();   /* ← ลด z-index navbar ก่อนเปิด modal */
  m.classList.remove('hidden');
  setTimeout(()=>{
    m.classList.add('bg-black/75');
    b.classList.remove('opacity-0','-translate-y-10','scale-95');
    b.classList.add('opacity-100','translate-y-0','scale-100');
  },10);
}
function hideModal(m,b){
  b.classList.add('opacity-0','-translate-y-10','scale-95');
  b.classList.remove('opacity-100','translate-y-0','scale-100');
  m.classList.remove('bg-black/75');
  setTimeout(()=>{
    m.classList.add('hidden');
    _nbUp(); /* ← คืน z-index navbar หลัง modal ปิดแล้ว */
  },300);
}

function openViewModal(name,desc,images,tags,links){
  buildCarousel(images);
  buildLinkButtons(links||{}, document.getElementById('modalLinkRow'));
  document.getElementById('modalName').textContent=name;
  document.getElementById('modalDescription').textContent=desc;
  const row=document.getElementById('modalTagRow'); row.innerHTML='';
  (tags||[]).forEach(t=>row.appendChild(makeBadge(t)));
  viewBox.scrollTop=0; showModal(viewModal,viewBox);
  setTimeout(startAutoSlide,800);
}
function closeModal(){ stopAutoSlide(); hideModal(viewModal,viewBox); }

function openEditModal(id,name,desc,images,tags,links){
  document.getElementById('edit_project_id').value=id;
  document.getElementById('edit_project_name').value=name;
  document.getElementById('edit_description').value=desc;
  document.getElementById('edit_image_json').value=JSON.stringify(images);
  document.getElementById('delete_project_id').value=id;
  document.getElementById('delete_image_json').value=JSON.stringify(images);

  document.getElementById('edit_link_github').value =(links&&links.github) ||'';
  document.getElementById('edit_link_youtube').value=(links&&links.youtube)||'';
  document.getElementById('edit_link_drive').value  =(links&&links.drive)  ||'';

  const thumbs=document.getElementById('editExistingThumbs'); thumbs.innerHTML='';
  document.querySelectorAll('input[name="keep_images[]"]').forEach(el=>el.remove());
  const lbs=['📜 เกียรติบัตร','🖼️ รูปที่ 2','🖼️ รูปที่ 3','🖼️ รูปที่ 4','🖼️ รูปที่ 5'];
  images.forEach((path,i)=>{
    const hid=document.createElement('input');
    hid.type='hidden';hid.name='keep_images[]';hid.value=path;
    document.getElementById('editForm').appendChild(hid);
    const w=document.createElement('div'); w.className='exist-thumb-wrap';
    w.innerHTML=`<img src="${path}" onerror="this.src='assets/no-image.png'">
      <div class="thumb-label">${lbs[i]||'🖼️ '+(i+1)}</div>
      <button type="button" class="thumb-del" title="ลบรูปนี้"
              onclick="toggleThumbDelete(this,'${path.replace(/'/g,"\\'")}')">✕</button>`;
    thumbs.appendChild(w);
  });
  document.getElementById('editNewPreviewRow').innerHTML='';
  const fi=document.querySelector('#editForm input[type="file"]'); if(fi) fi.value='';
  setEditTagGrid(tags||[]);
  editBox.scrollTop=0; showModal(editModal,editBox);
}
function closeEditModal(){ hideModal(editModal,editBox); }

function toggleThumbDelete(btn,path){
  const wrap=btn.closest('.exist-thumb-wrap');
  const hid=document.querySelector(`#editForm input[name="keep_images[]"][value="${CSS.escape(path)}"]`);
  const del=wrap.classList.contains('deleted');
  if(del){ wrap.classList.remove('deleted'); if(hid)hid.disabled=false; btn.title='ลบรูปนี้';btn.textContent='✕';btn.style.background=''; }
  else   { wrap.classList.add('deleted');    if(hid)hid.disabled=true;  btn.title='ยกเลิก';   btn.textContent='↩';btn.style.background='rgba(251,146,60,.85)'; }
}

document.getElementById('closeModalButton').addEventListener('click',closeModal);
viewModal.addEventListener('click',e=>{ if(e.target===viewModal)closeModal(); });
document.getElementById('closeEditModalButton').addEventListener('click',closeEditModal);
editModal.addEventListener('click',e=>{ if(e.target===editModal)closeEditModal(); });
document.addEventListener('keydown',e=>{
  if(!viewModal.classList.contains('hidden')){
    if(e.key==='ArrowRight')carouselGo(1);
    if(e.key==='ArrowLeft') carouselGo(-1);
  }
  if(e.key==='Escape'){
    if(!viewModal.classList.contains('hidden'))closeModal();
    if(!editModal.classList.contains('hidden'))closeEditModal();
  }
});

/* ─── MAIN INIT ─── */
document.addEventListener('DOMContentLoaded',()=>{
  const searchInput=document.getElementById('projectSearchInput');
  const allCards=[...document.querySelectorAll('.project-card')];
  const noResults=document.getElementById('noResultsMessage');
  const isAdmin=document.body.dataset.isAdmin==='true';
  let activeTags=new Set();

  function applyFilters(){
    const term=searchInput?searchInput.value.trim().toLowerCase():'';
    let count=0;
    allCards.forEach(card=>{
      const n=card.dataset.name.toLowerCase(), d=card.dataset.description.toLowerCase();
      const ct=(card.dataset.tags||'').split(',').map(t=>t.trim()).filter(Boolean);
      const ms=!term||n.includes(term)||d.includes(term);
      const mt=activeTags.size===0||ct.some(t=>activeTags.has(t));
      if(ms&&mt){card.style.display='';count++;}else card.style.display='none';
    });
    if(noResults)noResults.classList.toggle('hidden',count>0);
    const rb=document.getElementById('resultBar'),rt=document.getElementById('resultText');
    if(!isAdmin&&rb&&rt){
      const show=activeTags.size>0||term.length>0; rb.classList.toggle('hidden',!show);
      if(show){ rt.classList.remove('count-pop');void rt.offsetWidth;rt.classList.add('count-pop'); rt.textContent='แสดง '+count+' โปรเจกต์'; }
    }
  }
  if(searchInput)searchInput.addEventListener('input',applyFilters);

  document.querySelectorAll('.filter-chip').forEach(chip=>{
    chip.addEventListener('click',()=>{
      const tag=chip.dataset.tag;
      if(activeTags.has(tag)){activeTags.delete(tag);chip.classList.remove('active');}
      else{activeTags.add(tag);chip.classList.add('active');}
      const cb=document.getElementById('clearTagBtn'); if(cb)cb.classList.toggle('hidden',activeTags.size===0);
      applyFilters();
    });
  });
  const clearBtn=document.getElementById('clearTagBtn');
  if(clearBtn){clearBtn.addEventListener('click',()=>{
    activeTags.clear();
    document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));
    clearBtn.classList.add('hidden'); applyFilters();
  });}

  const obs=new IntersectionObserver((entries)=>{
    entries.forEach((e,i)=>{ if(e.isIntersecting){setTimeout(()=>e.target.classList.add('is-visible'),i*100);obs.unobserve(e.target);} });
  },{threshold:.1});
  allCards.forEach(el=>obs.observe(el));

  allCards.forEach(card=>{
    card.addEventListener('click',()=>{
      let images=[], tags=[], links={};
      try{images=JSON.parse(card.dataset.images||'[]');}catch(e){images=[card.dataset.image].filter(Boolean);}
      try{tags  =JSON.parse(card.dataset.tagsJson||'[]');}catch(e){tags=(card.dataset.tags||'').split(',').filter(Boolean);}
      try{links =JSON.parse(card.dataset.links||'{}');}catch(e){links={};}
      const id=card.dataset.id,name=card.dataset.name,desc=card.dataset.description;
      if(isAdmin)openEditModal(id,name,desc,images,tags,links);
      else       openViewModal(name,desc,images,tags,links);
    });
  });
});
</script>
</body>
</html>