<?php
session_start();
session_unset();
session_destroy();
header("Location: /~Mars200561234/TrollPost/index.php");
exit;