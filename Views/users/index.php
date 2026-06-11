<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Gestion des utilisateurs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>
<?php include __DIR__ . "/../../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4" style="max-width:900px">

  <div class="page-header">
    <div>
      <div class="text-muted small mb-1">Administration</div>
      <h1 class="page-title">Utilisateurs</h1>
    </div>
    <a href="/location/public/index.php?url=users/add" class="btn btn-success">+ Ajouter utilisateur</a>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Nom complet</th>
            <th>Identifiant</th>
            <th>Rôle</th>
            <th>Créé le</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="text-muted small"><?= $u['id'] ?></td>
            <td class="fw-semibold"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></td>
            <td><code><?= htmlspecialchars($u['username']) ?></code></td>
            <td>
              <?php if ($u['role'] === 'admin'): ?>
                <span class="badge bg-danger">Admin</span>
              <?php else: ?>
                <span class="badge bg-secondary">Opérateur</span>
              <?php endif; ?>
            </td>
            <td class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
            <td class="text-end">
              <a href="/location/public/index.php?url=users/edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
              <?php if ($u['id'] != $_SESSION['user_id']): ?>
              <button class="btn btn-sm btn-outline-danger"
                      onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                Supprimer
              </button>
              <?php else: ?>
              <button class="btn btn-sm btn-outline-secondary" disabled title="Vous ne pouvez pas vous supprimer">
                Supprimer
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger">Confirmer la suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Supprimer l'utilisateur <strong id="deleteUsername"></strong> ? Cette action est irréversible.
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form method="POST" action="delete.php" id="deleteForm">
          <input type="hidden" name="id" id="deleteId">
          <button class="btn btn-danger">Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id, username) {
  document.getElementById('deleteId').value = id;
  document.getElementById('deleteUsername').textContent = username;
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html>
