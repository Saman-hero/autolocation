<?php
$uri = $_SERVER['REQUEST_URI'];
function navActive(string $path): string {
    global $uri;
    return strpos($uri, $path) !== false ? 'pnav-active' : '';
}
?>
<nav id="mainNav">
  <div class="nav-inner">

    <div class="pnav-wrap" id="pnavWrap">
      <button class="pnav-btn" id="pnavBtn">
        <span class="pnav-icon" id="pnavIcon">☰</span>
        Menu
      </button>

      <div class="pnav-panel" id="pnavPanel">
        <a href="/location/index.php"               class="pnav-item <?= navActive('/location/index') ?>"       style="--i:0"><span class="pnav-item-icon">🏠</span> Accueil</a>
        <a href="/location/reservations/index.php"  class="pnav-item <?= navActive('/reservations') ?>"         style="--i:1"><span class="pnav-item-icon">📋</span> Locations</a>
        <a href="/location/clients/index.php"       class="pnav-item <?= navActive('/clients') ?>"              style="--i:2"><span class="pnav-item-icon">👤</span> Clients</a>
        <a href="/location/vehicles/index.php"      class="pnav-item <?= navActive('/vehicles') ?>"             style="--i:3"><span class="pnav-item-icon">🚗</span> Véhicules</a>
        <a href="/location/paiements/index.php"     class="pnav-item <?= navActive('/paiements') ?>"            style="--i:4"><span class="pnav-item-icon">💳</span> Paiements</a>
        <a href="/location/maintenance/index.php"   class="pnav-item <?= navActive('/maintenance') ?>"          style="--i:5"><span class="pnav-item-icon">🔧</span> Maintenance</a>
        <a href="/location/sinistres/index.php"     class="pnav-item <?= navActive('/sinistres') ?>"            style="--i:6"><span class="pnav-item-icon">⚠️</span> Sinistres</a>
        <a href="/location/historique/index.php"    class="pnav-item <?= navActive('/historique') ?>"           style="--i:7"><span class="pnav-item-icon">🕑</span> Historique</a>
        <a href="/location/reservations/calendar.php"  class="pnav-item <?= navActive('/calendar') ?>"             style="--i:8"><span class="pnav-item-icon">📅</span> Calendrier</a>
        <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="/location/users/index.php"         class="pnav-item <?= navActive('/users') ?>"                style="--i:9"><span class="pnav-item-icon">⚙️</span> Utilisateurs</a>
        <a href="/location/admin/audit.php"         class="pnav-item <?= navActive('/admin/audit') ?>"          style="--i:10"><span class="pnav-item-icon">🔍</span> Journal d'audit</a>
        <a href="/location/admin/setup-db.php"      class="pnav-item <?= navActive('/admin/setup') ?>"          style="--i:11"><span class="pnav-item-icon">🗄️</span> Config DB</a>
        <?php endif; ?>
      </div>
    </div>

    <a class="navbar-brand" href="/location/index.php">
      <div class="brand-name">
        <span class="brand-title">AutoLocation</span>
        <span class="brand-sub">Gestion de flotte</span>
      </div>
    </a>

    <div class="nav-right">
      <?php if (!empty($_SESSION['user_nom'])): ?>
        <span class="nav-user">👤 <?= htmlspecialchars($_SESSION['user_nom']) ?></span>
      <?php endif; ?>
      <a href="/location/logout.php" class="nav-logout-btn">Déconnexion</a>
    </div>

  </div>
</nav>

<script>
(function () {
  var btn   = document.getElementById('pnavBtn');
  var panel = document.getElementById('pnavPanel');
  var icon  = document.getElementById('pnavIcon');
  var wrap  = document.getElementById('pnavWrap');
  var open  = false;

  function openMenu()  { open = true;  panel.classList.add('pnav-open');    icon.textContent = '✕'; btn.classList.add('pnav-btn-active'); }
  function closeMenu() { open = false; panel.classList.remove('pnav-open'); icon.textContent = '☰'; btn.classList.remove('pnav-btn-active'); }

  btn.addEventListener('click', function(e) { e.stopPropagation(); open ? closeMenu() : openMenu(); });
  document.addEventListener('click',   function(e) { if (!wrap.contains(e.target)) closeMenu(); });
  document.addEventListener('keydown',  function(e) { if (e.key === 'Escape') closeMenu(); });
})();
</script>
