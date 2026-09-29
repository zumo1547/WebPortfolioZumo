<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();
require 'config.php';

require __DIR__.'/PHPMailer/PHPMailer.php';
require __DIR__.'/PHPMailer/SMTP.php';
require __DIR__.'/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

$error="";
$success="";

function sendOTPMail($to,$otp){
global $smtpUsername, $smtpPassword, $smtpFromAddress;

$mail = new PHPMailer(true);

try{

$mail->isSMTP();
$mail->Host='smtp.gmail.com';
$mail->SMTPAuth=true;
$mail->Username=$smtpUsername;
$mail->Password=$smtpPassword;
$mail->SMTPSecure='tls';
$mail->Port=587;

$mail->setFrom($smtpFromAddress,'Zumo Portfolio');
$mail->addAddress($to);

$mail->isHTML(true);
$mail->Subject='OTP Reset Password';

$mail->Body="
<h2>Reset Password</h2>
<p>OTP ของคุณคือ</p>
<h1>$otp</h1>
<p>OTP หมดอายุใน 10 นาที</p>
";

$mail->send();

return true;

}catch(Exception $e){

return false;

}

}

if($_SERVER["REQUEST_METHOD"]=="POST"){

$email = trim($_POST["email"]);

if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
$error="รูปแบบอีเมลไม่ถูกต้อง";
}
else{

$stmt=$pdo->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);

if($stmt->rowCount()==0){

$success="หากอีเมลนี้มีอยู่ในระบบ ระบบจะส่ง OTP ให้";

}else{

$otp=rand(100000,999999);
$expires=date("Y-m-d H:i:s",strtotime("+10 minutes"));

$pdo->prepare("DELETE FROM password_resets WHERE email=?")->execute([$email]);

$insert=$pdo->prepare("INSERT INTO password_resets (email,token,expires_at) VALUES (?,?,?)");

$insert->execute([$email,$otp,$expires]);

if(sendOTPMail($email,$otp)){

$_SESSION["reset_email"]=$email;

header("Location: verify_otp.php");
exit;

}else{

$error="ส่งอีเมลไม่สำเร็จ";

}

}

}

}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Forgot Password</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<link rel="icon" type="image/png" href="assets/Icon portfolio.png">

<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600;700;800&family=Noto+Sans+Thai:wght@400;500;600&family=Share+Tech+Mono&display=swap" rel="stylesheet">

<style>

*{box-sizing:border-box;margin:0;padding:0;}

body{
background:#07040f;
font-family:'Noto Sans Thai','Kanit',sans-serif;
min-height:100vh;
overflow-x:hidden;
color:#fff;
}

/* background grid */
body::before{
content:'';
position:fixed;
inset:0;
z-index:0;
pointer-events:none;
background-image:
linear-gradient(rgba(168,85,247,.035)1px,transparent 1px),
linear-gradient(90deg,rgba(168,85,247,.035)1px,transparent 1px);
background-size:52px 52px;
}

/* container */
.panel{
background:rgba(10,7,22,.88);
border:1px solid rgba(168,85,247,.18);
border-radius:22px;
overflow:hidden;
box-shadow:
0 0 60px rgba(124,58,237,.12),
inset 0 1px 0 rgba(255,255,255,.04);
}

/* input */
.form-input{
width:100%;
background:rgba(255,255,255,.04);
border:1px solid rgba(168,85,247,.2);
border-radius:12px;
padding:12px 16px 12px 42px;
color:#fff;
font-size:.9rem;
outline:none;
}

.form-input::placeholder{color:#4b5563;}

.form-input:focus{
border-color:rgba(168,85,247,.55);
box-shadow:0 0 18px rgba(168,85,247,.12);
}

/* button */
.btn-primary{
width:100%;
padding:13px;
border:none;
border-radius:12px;
background:linear-gradient(135deg,#7c3aed,#db2777);
color:#fff;
font-weight:700;
font-size:.95rem;
cursor:pointer;
box-shadow:0 4px 20px rgba(124,58,237,.35);
}

.btn-primary:hover{
transform:translateY(-2px);
box-shadow:0 8px 28px rgba(124,58,237,.5);
}

/* messages */
.error{
background:rgba(239,68,68,.12);
border:1px solid rgba(239,68,68,.3);
border-radius:10px;
padding:10px 14px;
color:#fca5a5;
font-size:.82rem;
margin-bottom:12px;
}

.success{
background:rgba(52,211,153,.1);
border:1px solid rgba(52,211,153,.3);
border-radius:10px;
padding:10px 14px;
color:#6ee7b7;
font-size:.82rem;
margin-bottom:12px;
}

</style>
</head>

<body class="flex items-center justify-center min-h-screen px-4">

<div class="w-full max-w-sm z-10">

<!-- logo / title -->
<div class="text-center mb-8">

<div style="
width:60px;
height:60px;
border-radius:50%;
background:linear-gradient(135deg,#7c3aed,#db2777);
display:flex;
align-items:center;
justify-content:center;
margin:0 auto 14px;
box-shadow:0 0 24px rgba(168,85,247,.5);
font-size:1.6rem;
">
🔑
</div>

<h1 style="
font-weight:800;
font-size:1.6rem;
background:linear-gradient(135deg,#e879f9,#f472b6,#38bdf8);
-webkit-background-clip:text;
-webkit-text-fill-color:transparent;
">
Forgot Password
</h1>

<p style="
font-family:'Share Tech Mono',monospace;
font-size:.65rem;
color:rgba(168,85,247,.5);
letter-spacing:.14em;
margin-top:4px;
">
 // RESET_ACCESS.PHP
</p>

</div>

<!-- panel -->
<div class="panel">

<div style="
height:2px;
background:linear-gradient(90deg,#7c3aed,#ec4899,#38bdf8,#ec4899,#7c3aed);
background-size:200% 100%;
"></div>

<div style="padding:28px 26px">

<p style="color:#9ca3af;font-size:.85rem;margin-bottom:20px;">
กรอกอีเมลที่ใช้สมัครสมาชิก
ระบบจะส่งรหัส OTP เพื่อรีเซ็ตรหัสผ่าน
</p>

<?php if($error){ ?>
<div class="error">
<i class="fas fa-exclamation-triangle"></i>
<?php echo $error ?>
</div>
<?php } ?>

<?php if($success){ ?>
<div class="success">
<i class="fas fa-check-circle"></i>
<?php echo $success ?>
</div>
<?php } ?>

<form method="POST" style="display:flex;flex-direction:column;gap:14px;">

<div style="position:relative">

<i class="fas fa-at"
style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.82rem;"></i>

<input type="email"
name="email"
class="form-input"
placeholder="อีเมล"
required>

</div>

<button type="submit" class="btn-primary">
<i class="fas fa-paper-plane"></i>
ส่ง OTP
</button>

</form>

<div style="height:1px;background:linear-gradient(90deg,transparent,rgba(168,85,247,.18),transparent);margin:20px 0;"></div>

<p style="text-align:center;font-size:.82rem;color:#6b7280;">
<a href="login.php" style="color:#a855f7;text-decoration:none;">
<i class="fas fa-arrow-left"></i>
กลับหน้าเข้าสู่ระบบ
</a>
</p>

</div>

</div>

</div>

</body>
</html>