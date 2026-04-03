<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Not logged in -  send to login page
if (empty($_SESSION["user_id"])) {
    header("Location: pages/SL.php");
    exit();
}

// Logged in as admin - send to admin panel
if ($_SESSION["isadmin"] == 1) {
    header("Location: pages/admin.php");
    exit();
}

// Otherwise regular user - let them pass, g