<?php
// Include this at the top of every protected page
if (empty($_SESSION['logged_in'])) {
    header("Location: /location/login.php");
    exit;
}
