<?php
session_start();
session_unset();
session_destroy();
header('Location: /me2u/pages/login.php');
exit;
?>