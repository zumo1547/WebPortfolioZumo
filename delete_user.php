<?php
session_start();
include 'config.php'; // เชื่อมต่อฐานข้อมูล

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    try {
        // ลบผู้ใช้จากตาราง users
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);

        // ลบข้อมูลอื่นๆ ที่เกี่ยวข้องกับผู้ใช้ เช่น logs หรือข้อมูลที่เกี่ยวข้อง
        $stmt = $pdo->prepare("DELETE FROM user_logs WHERE user_id = :id");
        $stmt->execute(['id' => $user_id]);

        header("Location: dashboard.php"); // กลับไปยังหน้าแดชบอร์ด
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
