<?php
session_start();
session_unset();
session_destroy();
header("Location: ../index.php");
exit;

// upon logout unset delete and throw user to the feed

?>