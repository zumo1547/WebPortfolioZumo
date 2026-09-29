<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];

    // ตรวจสอบว่าผู้ใช้ที่ต้องการลบเป็นผู้ดูแลระบบหรือไม่
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    $_SESSION['notification'] = "ลบผู้ดูแลระบบสำเร็จ";
}

header('Location: dashboard.php');
exit;
?>
