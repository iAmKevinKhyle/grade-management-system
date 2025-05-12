<?php
// Absolute path solution for logout

// 1. Determine the base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

// 2. Start and destroy session
session_start();
$_SESSION = array();
session_destroy();

// 3. Redirect to login page with absolute URL
$index_url = $protocol . $host . $path . '/index.php';
header("Location: " . $index_url);
exit();
?>