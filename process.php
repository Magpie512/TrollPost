<?php
session_start();
require 'includes/connect.php';
require 'includes/sanitize.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: pages/SL.php");
    exit;
}

$action  = sanitizeText($_POST['action'] ?? '', 20);
$id      = isset($_POST['id']) ? (int) $_POST['id'] : null;
$content = sanitizePostContent($_POST['content'] ?? '');

function handleImageUpload(): ?string {
    if (empty($_FILES['post_image']['name']) || $_FILES['post_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file      = $_FILES['post_image'];
    $uploadDir = 'uploads/';

    if (!validateImageUpload($file)) {
        die("Invalid or oversized image file.");
    }

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $uploadDir . bin2hex(random_bytes(8)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $filename)) return null;

    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create
        if ($action === 'create' && !empty($content)) {
            $imagePath = handleImageUpload();
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, name, content, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'New Post', $content, $imagePath]);
        }

        // Update
        elseif ($action === 'edit' && $id && !empty($content)) {
            $newImagePath = handleImageUpload();

            if ($newImagePath) {
                $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
                $oldPost = $stmt->fetch();

                $stmt = $pdo->prepare("UPDATE posts SET content = ?, image_path = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
                $stmt->execute([$content, $newImagePath, $id, $_SESSION['user_id']]);

                if ($oldPost && !empty($oldPost['image_path']) && file_exists($oldPost['image_path'])) {
                    unlink($oldPost['image_path']);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE posts SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
                $stmt->execute([$content, $id, $_SESSION['user_id']]);
            }
        }

        // Delete
        elseif ($action === 'delete' && $id) {
            $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $post = $stmt->fetch();

            if ($post) {
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);

                if (!empty($post['image_path']) && file_exists($post['image_path'])) {
                    unlink($post['image_path']);
                }
            }
        }

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        die("A database error occurred. Please try again later.");
    }

    header("Location: /~Mars200561234/TrollPost/index.php");
    exit;
}