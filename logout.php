<?php
/*session_start();

// Complete session cleanup
$_SESSION = [];
session_unset();
session_destroy();
setcookie(session_name(), '', time()-3600, '/');

// Redirect to index.php
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
header('Location: ' . $protocol . $_SERVER['HTTP_HOST'] . '/index.php');
exit;*/
session_start();
session_unset();
session_destroy();

// Redirect to login page
header('Location: index.php');
exit;
?>


