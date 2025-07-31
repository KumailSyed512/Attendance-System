<?php
session_start();
session_destroy();
header("Location: students_login.php");
exit();
?>