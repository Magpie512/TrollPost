<?php
session_start(); // ADD THIS LINE
require 'includes/connect.php';

// Block anyone who isn't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/SL.php");
    exit;
}

require 'includes/connect.php';
 
// Block anyone who isn't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/SL.php");
    exit;
}
 
$action  = $_POST['action'] ?? '';
$id      = $_POST['id'] ?? null;
$content = trim($_POST['content'] ?? '');
 
// Image handler
 
function handleImageUpload(): ?string {
    if (empty($_FILES['post_image']['name'])) {
        return null;
    }
 
    $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize   = 2 * 1024 * 1024; // 2 MB
    $uploadDir = 'uploads/';
 
    $file     = $_FILES['post_image'];
    $mimeType = mime_content_type($file['tmp_name']);
 
    if (!in_array($mimeType, $allowed)) {
        die("Only JPEG, PNG, GIF, and WebP images are allowed.");
    }
    if ($file['size'] > $maxSize) {
        die("Image must be under 2 MB.");
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("Upload error.");
    }
 
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
 
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $uploadDir . bin2hex(random_bytes(8)) . '.' . $ext;
 
    if (!move_uploaded_file($file['tmp_name'], $filename)) {
        die("File upload failed.");
    }
 
    return $filename;
}
 
// HID actions
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    if ($action === 'create' && !empty($content)) {
        $imagePath = handleImageUpload();
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $content, $imagePath]);
    }
 
    elseif ($action === 'edit' && $id && !empty($content)) {
        // Only allow editing own posts
        $stmt = $pdo->prepare("UPDATE posts SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        $stmt->execute([$content, $id, $_SESSION['user_id']]);
    }
 
    elseif ($action === 'delete' && $id) {
        // Fetch image path before deleting
        $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $post = $stmt->fetch();
 
        if ($post) {
            if (!empty($post['image_path']) && file_exists($post['image_path'])) {
                unlink($post['image_path']);
            }
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
        }
    }
 
    header("Location: index.php");
    exit;
}