<?php
require 'includes/connect.php';
// I know this is overly commented but YOU SAID OPEN BOOK SO IM OPENING THE BOOK
$action = $_POST['action'] ?? ''; // This will be 'create', 'edit', or 'delete' based on the form submission
$id = $_POST['id'] ?? null; // This will be null for create actions, and should be set for edit and delete actions
$content = $_POST['content'] ?? ''; // This will be the content of the post for create and edit actions, and will be empty for delete actions

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Check if the form was submitted via POST

    if ($action === 'create' && !empty($content)) { // Check if the action is 'create' and that content is not empty
        // Insert a new post with the provided content
        $stmt = $pdo->prepare("INSERT INTO posts (content) VALUES (?)");
        $stmt->execute([$content]);
    } 
    
    elseif ($action === 'edit' && $id && !empty($content)) { // Check if the action is 'edit', that an ID is provided, and that content is not empty
        // Update the post with the provided ID and content
        $stmt = $pdo->prepare("UPDATE posts SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$content, $id]);
    } 
    
    elseif ($action === 'delete' && $id) { // Check if the action is 'delete' and that an ID is provided
        // Delete the post with the provided ID
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: index.php");
    exit;
}