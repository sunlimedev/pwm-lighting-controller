<?php
// start session if necessary
if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

// check authentication
if (!isset($_SESSION['user_id']))
{
    header("Location: /index.php");
    exit();
}
?>
