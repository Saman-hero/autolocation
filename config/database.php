<?php
/**
 * config/database.php
 *
 * Central bootstrap file included by every page in the application.
 * Responsibilities:
 *   1. Start the PHP session (once, safely).
 *   2. Provide the flash() helper for one-time user notifications.
 *   3. Guard protected pages — redirect to login if the user is not authenticated.
 *   4. Define the Database class that opens a PDO connection to MySQL.
 */

// ── 1. Session ─────────────────────────────────────────────────────────────
// Only call session_start() if a session is not already active.
// This prevents "headers already sent" errors when the file is included
// more than once in the same request.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── 5. Internationalization ──────────────────────────────────────────────
// Load language support so __() is available everywhere.
if (file_exists(__DIR__ . '/lang.php')) {
    require_once __DIR__ . '/lang.php';
} else {
    // Fallback: define simple pass-through if lang.php doesn't exist
    if (!function_exists('__')) {
        function __(string $key): string { return $key; }
    }
    if (!function_exists('getLanguages')) {
        function getLanguages(): array { return ['fr' => ['label' => 'Français', 'flag' => '🇫🇷', 'dir' => 'ltr']]; }
    }
}

// ── 2. Flash helper (original) ──────────────────────────────────────────
/**
 * Store a one-time notification message in the session.
 * The message is consumed and displayed once by includes/flash.php.
 *
 * @param string $type  Bootstrap alert type: 'success' | 'danger' | 'warning' | 'info'
 * @param string $msg   Human-readable message shown to the user.
 */
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// ── 3. Authentication guard ────────────────────────────────────────────────
// The application uses a single front controller (public/index.php) so we
// cannot rely on the script filename alone. We check both the filename
// (legacy flat files kept for compatibility) and the ?url= route parameter.
$_currentScript = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
$_currentUrl    = trim($_GET['url'] ?? '', '/');

// Routes that do NOT require the user to be logged in.
$_publicRoutes  = ['login', 'logout', 'forgot-password', 'reset-password', 'public', 'public/book', 'public/calendar', 'public/confirmation'];
$_publicPages   = ['login.php', 'logout.php', 'forgot-password.php', 'reset-password.php'];

$_isPublic = in_array($_currentScript, $_publicPages)
          || in_array($_currentUrl,    $_publicRoutes);

// If the page is protected and the user is not authenticated, redirect to login.
if (!$_isPublic && empty($_SESSION['logged_in'])) {
    header("Location: /location/public/index.php?url=login");
    exit;
}

// ── 4. Database class ──────────────────────────────────────────────────────
/**
 * Database
 *
 * Thin wrapper around PDO. Instantiate it anywhere you need a DB connection,
 * then call getConnection() to receive the PDO object.
 *
 * Configuration is hard-coded here for XAMPP local development.
 * In production, move credentials to an environment variable or .env file.
 */
class Database {

    // MySQL server host — 'localhost' works for XAMPP on the same machine.
    private $host = "localhost";

    // Default MySQL port; change if your XAMPP uses a non-standard port.
    private $port = 3306;

    // Name of the MySQL database to connect to.
    private $db_name = "location";

    // MySQL username — 'root' is the XAMPP default with no password.
    private $username = "root";

    // MySQL password — empty string for the default XAMPP root account.
    private $password = "";

    // Holds the active PDO connection after getConnection() is called.
    public $conn;

    /**
     * Open (or re-open) a PDO connection and return it.
     *
     * PDO settings applied:
     *   - ERRMODE_EXCEPTION  → SQL errors throw exceptions instead of silent failures.
     *   - FETCH_ASSOC        → fetch() returns associative arrays (column name keys).
     *   - charset=utf8       → ensures correct handling of French characters (é, à …).
     *
     * @return PDO
     */
    public function getConnection() {

        // Reset any previous connection before opening a new one.
        $this->conn = null;

        try {
            // Build the DSN (Data Source Name) string for PDO.
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8",
                $this->username,
                $this->password
            );

            // Throw PDOException on any SQL error — makes bugs visible immediately.
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Return rows as associative arrays by default (e.g. $row['nom']).
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch(Exception $e) {
            // Fatal: stop execution and display the DB error message.
            // In production this should log the error and show a generic page
            // to avoid leaking connection details to end users.
            die("Erreur DB: " . $e->getMessage());
        }

        return $this->conn;
    }
}