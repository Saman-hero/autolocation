<?php
/**
 * includes/audit.php
 *
 * Provides the audit_log() helper function used throughout the application
 * to record every significant action performed by a user.
 *
 * Every write operation (CREATE / UPDATE / DELETE / START / FINISH / LOGIN …)
 * calls this function so that administrators can trace what happened, who did
 * it, and when — via the audit log viewer at admin/audit.php.
 *
 * The log entries are stored in the `audit_logs` MySQL table with the
 * following columns:
 *   user_id     — FK to users.id (who triggered the action)
 *   user_name   — username snapshot at the time of the action
 *   action      — uppercase verb: CREATE, UPDATE, DELETE, START, FINISH …
 *   table_name  — which DB table was affected (clients, reservations …)
 *   record_id   — primary key of the affected row (nullable)
 *   description — human-readable summary of the change
 *   ip_address  — client IP for network-level traceability
 */

/**
 * Insert one row into audit_logs.
 *
 * Design decisions:
 *   - Wrapped in try/catch so that an audit failure NEVER crashes the main
 *     request — the action already happened; we just log it best-effort.
 *   - Uses prepared statements to prevent SQL injection in the log itself.
 *   - Falls back to 'système' for user_name when no session exists
 *     (e.g. cron jobs or setup scripts).
 *
 * @param PDO    $conn        Active database connection.
 * @param string $action      Verb describing the operation (e.g. 'CREATE').
 * @param string $table       Name of the affected DB table (e.g. 'clients').
 * @param mixed  $record_id   Primary key of the affected record, or null.
 * @param string $description Free-text summary shown in the audit log UI.
 */
function audit_log($conn, string $action, string $table, $record_id, string $description): void {
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (user_id, user_name, action, table_name, record_id, description, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id']   ?? null,           // Logged-in user's ID (null for system)
            $_SESSION['username']  ?? 'système',      // Username at time of action
            strtoupper($action),                      // Normalise to uppercase for consistency
            $table,                                   // Affected table name
            $record_id ?: null,                       // Affected record PK (0 → null)
            $description,                             // Human-readable description
            $_SERVER['REMOTE_ADDR'] ?? null,          // Client IP address
        ]);
    } catch (Exception $e) {
        // Audit failure must never disrupt the user's action.
        // Log to PHP's error log for server-side visibility without showing
        // anything to the end user.
        error_log('audit_log error: ' . $e->getMessage());
    }
}
