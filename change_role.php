<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];

    // เปลี่ยนบทบาทผู้ใช้
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $user_id]);

    $_SESSION['notification'] = "เปลี่ยนบทบาทผู้ใช้สำเร็จ";
}

header('Location: dashboard.php');
exit;
?>
