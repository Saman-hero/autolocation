<?php
/**
 * Controllers/AuthController.php
 *
 * Handles all authentication flows:
 *   - login()          — username/password form authentication.
 *   - logout()         — session destruction and redirect.
 *   - forgotPassword() — generate a single-use password reset token.
 *   - resetPassword()  — validate the token and set a new password.
 *
 * Security notes:
 *   - Passwords are stored as bcrypt hashes (PHP's password_hash / password_verify).
 *   - Reset tokens are 32 random bytes (hex-encoded) with a 1-hour expiry.
 *   - The forgot-password response uses a neutral message even when the
 *     username does not exist, to prevent user enumeration.
 */
require_once __DIR__ . "/../config/database.php";

class AuthController {

    /**
     * Show the login form (GET) or process the submitted credentials (POST).
     *
     * On successful login, the user's ID, username, full name, and role are
     * stored in the session. The role ('admin' or 'operateur') is read by
     * AdminController and UserController to restrict sensitive routes.
     */
    public function login() {
        // If the user already has an active session, skip the login page.
        if (!empty($_SESSION['user_id'])) {
            header("Location: /location/public/index.php?url=dashboard"); exit;
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            // Basic presence check before hitting the database.
            if ($username === '' || $password === '') {
                $error = 'Veuillez remplir tous les champs.';
            } else {
                $db   = new Database();
                $conn = $db->getConnection();

                // Fetch the user row by username (case-sensitive in MySQL default collation).
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                // password_verify() safely compares the submitted plain-text password
                // against the stored bcrypt hash — timing-safe by design.
                if ($user && password_verify($password, $user['password'])) {
                    // Populate session keys used throughout the application.
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom']; // Display name in navbar
                    $_SESSION['user_role'] = $user['role'];   // 'admin' | 'operateur'
                    $_SESSION['logged_in'] = true;            // Auth guard flag (checked in config/database.php)

                    flash('success', 'Bienvenue, ' . $user['prenom'] . ' !');
                    header("Location: /location/public/index.php?url=dashboard"); exit;
                } else {
                    // Intentionally vague — does not reveal whether the username or password was wrong.
                    $error = 'Identifiant ou mot de passe incorrect.';
                }
            }
        }

        require __DIR__ . "/../Views/auth/login.php"; // Render the login form (with $error if set)
    }

    /**
     * Destroy the current session and redirect to the login page.
     * Clearing $_SESSION before session_destroy() ensures all data is wiped.
     */
    public function logout() {
        $_SESSION = [];       // Clear all session variables
        session_destroy();    // Invalidate the session cookie on the server
        header("Location: /location/public/index.php?url=login");
        exit;
    }

    /**
     * Show the forgot-password form (GET) or generate a reset token (POST).
     *
     * When a valid username is submitted:
     *   1. Any existing unused token for that user is deleted (one active token at a time).
     *   2. A new 64-character hex token is generated (cryptographically secure).
     *   3. The token is stored in `password_resets` with a 1-hour expiry timestamp.
     *   4. The reset URL is shown to the admin (in production this would be emailed).
     *
     * Note: the token is currently displayed on-screen instead of being emailed,
     * because PHP mail() is not configured in the XAMPP development environment.
     */
    public function forgotPassword() {
        $db   = new Database();
        $conn = $db->getConnection();
        $msg = ''; $msgType = ''; $resetLink = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');

            if (!$username) {
                $msg = 'Veuillez saisir votre identifiant.'; $msgType = 'danger';
            } else {
                // Look up the user — only id, nom, prenom needed here.
                $stmt = $conn->prepare("SELECT id, nom, prenom FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if (!$user) {
                    // Neutral message — does not confirm whether the username exists
                    // (prevents user enumeration attacks).
                    $msg = "Si l'identifiant existe, un lien de réinitialisation a été généré."; $msgType = 'info';
                } else {
                    // Generate a 32-byte (64 hex chars) cryptographically secure token.
                    $token     = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

                    // Invalidate any previously issued token for this user.
                    $conn->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);

                    // Store the new token.
                    $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)")
                         ->execute([$user['id'], $token, $expiresAt]);

                    // Build the reset URL using the current protocol and host.
                    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $resetLink = "$protocol://{$_SERVER['HTTP_HOST']}/location/public/index.php?url=reset-password&token=$token";

                    $msg = "Lien généré pour <strong>" . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . "</strong>. Valable 1 heure.";
                    $msgType = 'success';
                }
            }
        }

        require __DIR__ . "/../Views/auth/forgot-password.php"; // $resetLink is shown in this view
    }

    /**
     * Validate a reset token (GET) and process the new password (POST).
     *
     * Token validation checks three conditions simultaneously:
     *   - token exists in password_resets.
     *   - used = 0 (not already consumed).
     *   - expires_at > NOW() (not expired).
     *
     * On successful POST:
     *   - The new password is hashed with bcrypt and saved.
     *   - The token is marked used = 1 (single-use guarantee).
     */
    public function resetPassword() {
        $db   = new Database();
        $conn = $db->getConnection();

        // Read the token from the query string (present in the reset link).
        $token   = trim($_GET['token'] ?? '');
        $errors  = []; $success = false; $reset = null;

        if (!$token) {
            $errors[] = 'Token manquant ou invalide.';
        } else {
            // Join password_resets with users to get the user's name for display.
            $stmt = $conn->prepare("
                SELECT pr.*, u.username, u.nom, u.prenom FROM password_resets pr
                JOIN users u ON pr.user_id = u.id
                WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $reset = $stmt->fetch();

            if (!$reset) $errors[] = 'Ce lien est invalide, expiré ou déjà utilisé.';
        }

        // Only process the form if the token is valid.
        if (!$errors && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm']  ?? '';

            // Password strength and confirmation checks.
            if (strlen($password) < 6)  $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
            if ($password !== $confirm)  $errors[] = 'Les mots de passe ne correspondent pas.';

            if (!$errors) {
                // Hash the new password using bcrypt (PASSWORD_DEFAULT).
                $conn->prepare("UPDATE users SET password = ? WHERE id = ?")
                     ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);

                // Mark the token as used so it cannot be replayed.
                $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")
                     ->execute([$token]);

                $success = true; // View will show a success message and login link
            }
        }

        require __DIR__ . "/../Views/auth/reset-password.php";
    }
}
