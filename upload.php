<?php
session_start();
include 'config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่าผู้ใช้เป็นแอดมินหรือไม่
$user_id = $_SESSION['user_id'];
$sql = "SELECT role FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// หากไม่ใช่แอดมินแสดงข้อความแจ้งว่าไม่มีสิทธิ์
if ($user['role'] != 'admin') {
    echo "คุณไม่มีสิทธิ์อัปโหลดไฟล์!";
    exit();
}

// ตรวจสอบการอัปโหลดไฟล์
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["file"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // ตรวจสอบว่าเป็นไฟล์รูปภาพหรือไม่
    if (in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            // รีไดเรกต์กลับไปที่หน้า projects.php
            header("Location: projects.php");
            exit();
        } else {
            echo "เกิดข้อผิดพลาดในการอัปโหลดไฟล์!";
        }
    } else {
        echo "ไฟล์ที่อัปโหลดไม่ใช่รูปภาพที่รองรับ!";
    }
}
?>
