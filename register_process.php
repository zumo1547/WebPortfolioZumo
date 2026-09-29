<?php
session_start();
include 'config.php'; // ใช้ PDO ที่ได้ตั้งค่าใน config.php

// ตรวจสอบว่าเมื่อมีการ submit ฟอร์มให้ทำการลงทะเบียน
if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // เข้ารหัสรหัสผ่าน

    // ตรวจสอบว่าผู้ใช้มีอยู่ในระบบแล้วหรือไม่
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->rowCount() > 0) {
        echo "<p class='text-center text-red-500'>Username already taken!</p>";
    } else {
        // เพิ่มข้อมูลผู้ใช้ใหม่
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        if ($stmt->execute(['username' => $username, 'email' => $email, 'password' => $password])) {
            // ส่งผู้ใช้ไปหน้า login เมื่อสมัครสำเร็จ
            header("Location: login.php");
            exit();
        } else {
            echo "<p class='text-center text-red-500'>Error in registration!</p>";
        }
    }
}
?>
