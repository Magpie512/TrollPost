<?php
require 'includes/connect.php';

$newHash = password_hash('yournewpassword', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'BorkBork'");
$stmt->execute([$newHash]);
echo "Done!";
?>