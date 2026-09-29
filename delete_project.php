<?php
session_start();
include 'config.php'; // เชื่อมต่อฐานข้อมูล

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['project_id'])) {
    $project_id = $_POST['project_id'];

    try {
        // ลบโปรเจกต์จากตาราง projects
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id");
        $stmt->execute(['id' => $project_id]);

        header("Location: dashboard.php"); // กลับไปยังหน้าแดชบอร์ด
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
