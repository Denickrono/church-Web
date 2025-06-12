<?php
session_start();
session_unset();
session_destroy();
header('Location: pastor_login.php');
exit;
?>