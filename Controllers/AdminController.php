<?php
/**
 * Controllers/AdminController.php
 *
 * Admin-only controller. All methods are restricted to users with role='admin'.
 * The role check runs in the constructor so no individual method needs to repeat it.
 *
 * Currently provides:
 *   audit() — paginated, filterable audit log viewer.
 *
 * Future admin features (setup DB, system config …) would be added here.
 */
require_once __DIR__ . "/../config/database.php";

class AdminController {

    /** @var PDO */
    private $conn;

    /**
     * Open the DB connection and enforce admin-only access.
     * Any non-admin user is redirected to the dashboard with an error flash.
     * This guard runs before any method in this class.
     */
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();

        // Role check: only 'admin' users may access any AdminController method.
        if ($_SESSION['user_role'] !== 'admin') {
            flash('danger', 'Accès réservé aux administrateurs.');
            header("Location: /location/public/index.php?url=dashboard"); exit;
        }
    }

    /**
     * GET — Display the audit log with pagination and multi-criteria filters.
     *
     * Filter options:
     *   user   — partial match on user_name OR exact user_id.
     *   action — exact action verb (CREATE, UPDATE, DELETE …).
     *   from   — log entry date lower bound.
     *   to     — log entry date upper bound.
     *
     * Pagination: 20 entries per page. LIMIT/OFFSET are bound as PDO::PARAM_INT
     * to avoid type-coercion issues in MySQL.
     *
     * $actionColors  — maps action verbs to Bootstrap badge colours in the view.
     * $distinctActions — list of all existing action verbs for the filter dropdown.
     */
    public function audit() {
        $conn    = $this->conn;
        $perPage = 20;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $fUser   = trim($_GET['user']   ?? '');
        $fAction = trim($_GET['action'] ?? '');
        $fFrom   = $_GET['from'] ?? '';
        $fTo     = $_GET['to']   ?? '';

        $where = "WHERE 1=1"; $params = [];
        if ($fUser)   { $where .= " AND (al.user_name LIKE :user OR al.user_id = :uid)"; $params[':user'] = "%$fUser%"; $params[':uid'] = (int)$fUser; }
        if ($fAction) { $where .= " AND al.action = :action"; $params[':action'] = strtoupper($fAction); }
        if ($fFrom)   { $where .= " AND DATE(al.created_at) >= :from"; $params[':from'] = $fFrom; }
        if ($fTo)     { $where .= " AND DATE(al.created_at) <= :to";   $params[':to']   = $fTo; }

        $countStmt = $conn->prepare("SELECT COUNT(*) FROM audit_logs al $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $params[':limit'] = $perPage; $params[':offset'] = $offset;
        $stmt = $conn->prepare("SELECT al.* FROM audit_logs al $where ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, in_array($k, [':limit',':offset']) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $logs = $stmt->fetchAll();

        $actionColors = [
            'CREATE' => 'bg-success', 'UPDATE' => 'bg-primary', 'DELETE' => 'bg-danger',
            'LOGIN'  => 'bg-info',    'LOGOUT' => 'bg-secondary','START'  => 'bg-warning text-dark',
            'FINISH' => 'badge-terminee',
        ];
        $distinctActions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

        require __DIR__ . "/../Views/admin/audit.php";
    }
}
