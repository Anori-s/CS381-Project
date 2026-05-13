<?php
session_start();
// clean-empty session data
$_SESSION = [];
// Delete cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>
