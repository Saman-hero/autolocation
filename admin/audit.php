<?php
require_once "../config/database.php";

if ($_SESSION['user_role'] !== 'admin') {
    flash('danger', 'Accès réservé aux administrateurs.');
    header("Location: /location/index.php"); exit;
}

$db   = new Database();
$conn = $db->getConnection();

$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$fUser   = trim($_GET['user']   ?? '');
$fAction = trim($_GET['action'] ?? '');
$fFrom   = $_GET['from'] ?? '';
$fTo     = $_GET['to']   ?? '';

$where  = "WHERE 1=1";
$params = [];

if ($fUser) {
    $where .= " AND (al.user_name LIKE :user OR al.user_id = :uid)";
    $params[':user'] = "%$fUser%";
    $params[':uid']  = (int)$fUser;
}
if ($fAction) {
    $where .= " AND al.action = :action";
    $params[':action'] = strtoupper($fAction);
}
if ($fFrom) {
    $where .= " AND DATE(al.created_at) >= :from";
    $params[':from'] = $fFrom;
}
if ($fTo) {
    $where .= " AND DATE(al.created_at) <= :to";
    $params[':to'] = $fTo;
}

// Count
$countStmt = $conn->prepare("SELECT COUNT(*) FROM audit_logs al $where");
$countStmt->execute($params);
$total    = (int)$countStmt->fetchColumn();
$pages    = max(1, (int)ceil($total / $perPage));

// Fetch
$params[':limit']  = $perPage;
$params[':offset'] = $offset;
$stmt = $conn->prepare("SELECT al.* FROM audit_logs al $where ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) {
    if (in_array($k, [':limit', ':offset'])) {
        $stmt->bindValue($k, $v, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($k, $v);
    }
}
$stmt->execute();
$logs = $stmt->fetchAll();

$actionColors = [
    'CREATE' => 'bg-success',
    'UPDATE' => 'bg-primary',
    'DELETE' => 'bg-danger',
    'LOGIN'  => 'bg-info',
    'LOGOUT' => 'bg-secondary',
    'START'  => 'bg-warning text-dark',
    'FINISH' => 'badge-terminee',
];
$distinctActions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Journal d'audit — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <div>
      <h1 class="page-title">Journal d'audit</h1>
      <div class="text-muted small"><?= number_format($total) ?> entrée(s) au total</div>
    </div>
    <a href="setup-db.php" class="btn btn-outline-secondary btn-sm">Config DB</a>
  </div>

  <!-- Filtres -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label fw-semibold">Utilisateur</label>
          <input type="text" name="user" class="form-control" placeholder="Nom ou ID…" value="<?= htmlspecialchars($fUser) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Action</label>
          <select name="action" class="form-select">
            <option value="">Toutes</option>
            <?php foreach ($distinctActions as $a): ?>
              <option value="<?= htmlspecialchars($a) ?>" <?= $fAction === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Du</label>
          <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($fFrom) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Au</label>
          <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($fTo) ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Filtrer</button>
          <a href="audit.php" class="btn btn-outline-secondary ms-1">Réinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($logs)): ?>
        <div class="empty-state">
          <span class="empty-icon">📋</span>
          <p>Aucune entrée d'audit trouvée</p>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Date/Heure</th>
              <th>Utilisateur</th>
              <th>Action</th>
              <th>Table</th>
              <th>ID</th>
              <th>Description</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($logs as $i => $log): ?>
            <tr style="--i:<?= $i ?>">
              <td class="text-muted small"><?= $log['id'] ?></td>
              <td class="text-muted small" style="white-space:nowrap"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
              <td>
                <span class="fw-semibold"><?= htmlspecialchars($log['user_name'] ?? '—') ?></span>
                <?php if ($log['user_id']): ?><span class="text-muted small ms-1">#<?= $log['user_id'] ?></span><?php endif; ?>
              </td>
              <td>
                <span class="badge <?= $actionColors[$log['action']] ?? 'bg-secondary' ?>">
                  <?= htmlspecialchars($log['action']) ?>
                </span>
              </td>
              <td class="text-muted small font-monospace"><?= htmlspecialchars($log['table_name'] ?? '—') ?></td>
              <td class="text-muted small"><?= $log['record_id'] ?: '—' ?></td>
              <td class="small"><?= htmlspecialchars($log['description'] ?? '') ?></td>
              <td class="text-muted small font-monospace"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
        <div class="text-muted small">Page <?= $page ?> / <?= $pages ?> &mdash; <?= number_format($total) ?> résultats</div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹</a></li>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end   = min($pages, $page + 2);
            for ($p = $start; $p <= $end; $p++):
            ?>
              <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
              <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">›</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
