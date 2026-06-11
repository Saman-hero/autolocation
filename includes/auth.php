<?php
/**
 * includes/auth.php
 *
 * Minimal authentication guard for legacy flat-file pages that do not go
 * through the front controller (public/index.php).
 *
 * Usage: require_once at the very top of any PHP page that must be
 * accessible only to logged-in users.
 *
 * How it works:
 *   - $_SESSION['logged_in'] is set to true in AuthController::login()
 *     after successful password verification.
 *   - If the flag is absent (user never logged in, or session expired),
 *     the browser is redirected to the login page immediately.
 */
if (empty($_SESSION['logged_in'])) {
    header("Location: /location/login.php");
    exit; // Stop any further code execution on this page.
}
