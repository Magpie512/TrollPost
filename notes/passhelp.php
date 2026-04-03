<?php
require '../includes/connect.php';

$newHash = password_hash('adminadmin', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->execute([$newHash]);
echo "Done!";
?>