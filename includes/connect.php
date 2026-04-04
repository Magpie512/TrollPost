<?php 
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "127.0.0.1"; // LH
$db = "trollpost"; //database name
$user = "root"; //username
$password = ""; //password

// $host = "172.31.22.43"; //hostname
// $db = "Mars200561234"; //database name
// $user = "Mars200561234"; //username
// $password = "TrOH_Y_OI2"; //password

//points to the database
$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
