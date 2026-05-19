<?php
/**
 * Audit logging helper
 * Usage: audit_log($conn, 'CREATE', 'clients', $id, 'Client X créé');
 */
function audit_log($conn, string $action, string $table, $record_id, string $description): void {
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (user_id, user_name, action, table_name, record_id, description, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id']   ?? null,
            $_SESSION['username']  ?? 'système',
            strtoupper($action),
            $table,
            $record_id ?: null,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {
        // Fail silently — audit should never break main flow
        // Optionally log to error_log:
        error_log('audit_log error: ' . $e->getMessage());
    }
}
