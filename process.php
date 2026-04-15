<?php
session_start();
require 'includes/connect.php';

// Authority
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/SL.php");
    exit;
}

$action  = $_POST['action'] ?? '';
$id      = $_POST['id'] ?? null;
$content = trim($_POST['content'] ?? '');

function handleImageUpload(): ?string {
    if (empty($_FILES['post_image']['name']) || $_FILES['post_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize   = 2 * 1024 * 1024; // 2MB
    $uploadDir = 'uploads/';

    $file = $_FILES['post_image'];
    if (!file_exists($file['tmp_name'])) return null;

    $mimeType = mime_content_type($file['tmp_name']);
    if (!in_array($mimeType, $allowed)) die("Invalid file type.");
    if ($file['size'] > $maxSize) die("File too large.");

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $uploadDir . bin2hex(random_bytes(8)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $filename)) return null;

    return $filename;
}

// 2. Action Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create
        if ($action === 'create' && !empty($content)) {
            $imagePath = handleImageUpload();

            // post sub comment. I did use AI here to help me refactor after my table shrink from 7 to 2.
            // Also of Note: I did ask it to add instructional walkthrough for both me at the time getting overwhelmed the next day
            // and for open book prep.
            // Note: Added 'name' to match your SQL schema (NOT NULL)
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, name, content, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'New Post', $content, $imagePath]);
        }

        // Update
        elseif ($action === 'edit' && $id && !empty($content)) {
            // Check if user is uploading a replacement image
            $newImagePath = handleImageUpload();
            
            if ($newImagePath) {
                // 1. Get old image path to delete it
                $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
                $oldPost = $stmt->fetch();

                // 2. Update with new image
                $stmt = $pdo->prepare("UPDATE posts SET content = ?, image_path = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
                $stmt->execute([$content, $newImagePath, $id, $_SESSION['user_id']]);

                // 3. Delete old file from server if update was successful
                if ($oldPost && !empty($oldPost['image_path']) && file_exists($oldPost['image_path'])) {
                    unlink($oldPost['image_path']);
                }
            } else {
                // Just update text content
                $stmt = $pdo->prepare("UPDATE posts SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
                $stmt->execute([$content, $id, $_SESSION['user_id']]);
            }
        }

        // Remove (Updated logic removes image from the uploads folder.)
        elseif ($action === 'delete' && $id) {
            // 1. Fetch info first
            $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $post = $stmt->fetch();

            if ($post) {
                // 2. Delete from Database FIRST
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);

                // 3. ONLY delete file if DB deletion succeeded
                if (!empty($post['image_path']) && file_exists($post['image_path'])) {
                    unlink($post['image_path']);
                }
            }
        }

    } catch (PDOException $e) {
        // If the DB crashes/errors, we catch it here so the script doesn't just "die"
        // and leave files in an inconsistent state.
        error_log("Database Error: " . $e->getMessage());
        die("A database error occurred. Please try again later.");
    }

    header("Location: index.php");
    exit;
}
