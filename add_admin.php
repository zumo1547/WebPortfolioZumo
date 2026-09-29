<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];

    // ตรวจสอบว่าผู้ใช้มีอยู่ในระบบหรือไม่
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        // อัพเดทบทบาทของผู้ใช้ให้เป็น 'admin'
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = ?");
        $stmt->execute([$username]);
        $_SESSION['notification'] = "เพิ่มผู้ดูแลระบบ '{$username}' สำเร็จ";
    } else {
        $_SESSION['notification'] = "ไม่พบผู้ใช้ {$username}";
    }
}

header('Location: dashboard.php');
exit;
?>
