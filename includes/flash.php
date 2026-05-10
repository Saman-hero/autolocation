<?php if (!empty($_SESSION['flash'])):
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $icons = ['success' => '✓', 'danger' => '✕', 'warning' => '⚠', 'info' => 'ℹ'];
    $icon  = $icons[$f['type']] ?? '';
?>
<div class="alert alert-<?= $f['type'] ?> alert-dismissible fade show mx-3 mt-3 d-flex align-items-center gap-2 shadow-sm" role="alert">
    <span class="fw-bold"><?= $icon ?></span>
    <?= htmlspecialchars($f['msg']) ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['import_errors'])):
    $errs = $_SESSION['import_errors'];
    unset($_SESSION['import_errors']);
?>
<div class="alert alert-warning alert-dismissible fade show mx-3 mt-2 shadow-sm" role="alert">
    <strong>⚠ Lignes non importées :</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ($errs as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
