<?php
session_start();
session_unset();
session_destroy();

// ย้ายกลับไปหน้า Login
header("Location: login.php");
exit;
?>
