<?php
// **********************************************
// ********* Database Configuration *********
// **********************************************

require __DIR__ . '/config.example.php';
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// **********************************************
// ********* Error Reporting *********
// **********************************************

// 🔥 เปิดตอนพัฒนา
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ❗ เวลาขึ้น Production ให้เปลี่ยนเป็น:
// ini_set('display_errors', 0);

$dsn = "mysql:host=$servername;dbname=$database;charset=$charset";

try {

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

} catch (PDOException $e) {

    // ไม่โชว์รายละเอียด DB จริง (กัน hack)
    die("❌ ระบบฐานข้อมูลมีปัญหา กรุณาติดต่อผู้ดูแลระบบ");

}
?>