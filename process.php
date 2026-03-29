<?php
require 'includes/connect.php';

// Block anyone who isn't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null;
$content = $_POST['content'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'create' && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $content]);
    } 
    
    elseif ($action === 'edit' && $id && !empty($content)) {
        $stmt = $pdo->prepare("UPDATE posts SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$content, $id]);
    } 
    
    elseif ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: index.php");
    exit;
}

//thank god for w3s and the discord nerds helping me out with lines 10 to 12