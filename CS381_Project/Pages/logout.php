<?php
session_start();
//empty session data
$_SESSION = [];
// Delete cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/'); //set time to 1 hour ago to delete
}
session_destroy(); //destroy for server

// Redirect to login
header('Location: login.php');
exit();
?>
