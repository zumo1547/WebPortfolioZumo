<?php
include 'config.php';

if (isset($_GET['id'])) {
    $projectId = $_GET['id'];

    // ดึงข้อมูลโปรเจคจากฐานข้อมูล
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();

    if ($project) {
        echo json_encode([
            'name' => $project['name'],
            'image' => $project['image'],
            'description' => $project['description']
        ]);
    }
}
?>
