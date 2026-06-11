<?php
/**
 * includes/flash.php
 *
 * Renders one-time notification banners stored in the session.
 * Include this file inside page layouts, right after the <body> / navbar,
 * so messages appear at the top of every page that follows a redirect.
 *
 * Two independent notification channels are handled:
 *
 *   $_SESSION['flash']
 *       A single message set via flash($type, $msg) in config/database.php.
 *       Used for success/error feedback after any CRUD operation.
 *
 *   $_SESSION['import_errors']
 *       An array of row-level errors produced during a CSV/Excel import.
 *       Displayed as a bulleted list inside a warning banner.
 *
 * Each channel is consumed (unset) immediately after rendering so the
 * message only appears once, even if the user refreshes the page.
 */

// ── Channel 1: Single flash message ───────────────────────────────────────
?>
<?php if (!empty($_SESSION['flash'])):
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']); // Consume immediately — show once only.

    // Map alert type to a visible Unicode icon for quick recognition.
    $icons = ['success' => '✓', 'danger' => '✕', 'warning' => '⚠', 'info' => 'ℹ'];
    $icon  = $icons[$f['type']] ?? '';
?>
<!-- Bootstrap dismissible alert — type drives the colour (success/danger/warning/info) -->
<div class="alert alert-<?= $f['type'] ?> alert-dismissible fade show mx-3 mt-3 d-flex align-items-center gap-2 shadow-sm" role="alert">
    <span class="fw-bold"><?= $icon ?></span>
    <?= htmlspecialchars($f['msg']) /* Escape to prevent XSS in message text */ ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php
// ── Channel 2: Import error list ───────────────────────────────────────────
// Set by import scripts when individual rows fail validation during bulk import.
?>
<?php if (!empty($_SESSION['import_errors'])):
    $errs = $_SESSION['import_errors'];
    unset($_SESSION['import_errors']); // Consume immediately.
?>
<div class="alert alert-warning alert-dismissible fade show mx-3 mt-2 shadow-sm" role="alert">
    <strong>⚠ Lignes non importées :</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ($errs as $e): ?>
        <li><?= htmlspecialchars($e) /* Escape each error string */ ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
