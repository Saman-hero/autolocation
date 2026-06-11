<?php
/**
 * includes/navbar.php
 *
 * Renders the main application navigation bar.
 * Include this file at the top of every page layout (inside <body>).
 *
 * Features:
 *   - navActive()  helper highlights the current page link.
 *   - Role-based visibility: admin-only links (Users, Audit, Config DB)
 *     are hidden from Operator accounts.
 *   - Hamburger toggle menu with vanilla JS (no jQuery dependency):
 *       • Click the button to open/close the nav panel.
 *       • Clicking outside the nav panel closes it automatically.
 *       • Pressing Escape closes it via keyboard.
 *   - Displays the logged-in user's full name from $_SESSION['user_nom'].
 *   - Provides a logout link pointing to the logout route.
 */

// Capture the current URL path once — used by navActive() for all links.
$uri = $_SERVER['REQUEST_URI'];

/**
 * Return the CSS class that marks a nav item as active (current page).
 *
 * Checks whether $path appears anywhere in the current REQUEST_URI.
 * Example: navActive('/reservations') returns 'pnav-active' when the
 * user is on /location/reservations/index.php.
 *
 * @param string $path  URL fragment to look for.
 * @return string       'pnav-active' if matched, empty string otherwise.
 */
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
        <a href="/location/public/index.php?url=dashboard"    class="pnav-item <?= navActive('url=dashboard') ?>"    style="--i:0"><span class="pnav-item-icon">🏠</span> Accueil</a>
        <a href="/location/public/index.php?url=reservations" class="pnav-item <?= navActive('url=reservations') ?>" style="--i:1"><span class="pnav-item-icon">📋</span> Locations</a>
        <a href="/location/public/index.php?url=clients"      class="pnav-item <?= navActive('url=clients') ?>"      style="--i:2"><span class="pnav-item-icon">👤</span> Clients</a>
        <a href="/location/public/index.php?url=vehicles"     class="pnav-item <?= navActive('url=vehicles') ?>"     style="--i:3"><span class="pnav-item-icon">🚗</span> Véhicules</a>
        <a href="/location/public/index.php?url=paiements"    class="pnav-item <?= navActive('url=paiements') ?>"    style="--i:4"><span class="pnav-item-icon">💳</span> Paiements</a>
        <a href="/location/public/index.php?url=maintenance"  class="pnav-item <?= navActive('url=maintenance') ?>"  style="--i:5"><span class="pnav-item-icon">🔧</span> Maintenance</a>
        <a href="/location/public/index.php?url=sinistres"    class="pnav-item <?= navActive('url=sinistres') ?>"    style="--i:6"><span class="pnav-item-icon">⚠️</span> Sinistres</a>
        <a href="/location/public/index.php?url=historique"   class="pnav-item <?= navActive('url=historique') ?>"   style="--i:7"><span class="pnav-item-icon">🕑</span> Historique</a>
        <a href="/location/public/index.php?url=etat-vehicule" class="pnav-item <?= navActive('url=etat-vehicule') ?>" style="--i:8"><span class="pnav-item-icon">📅</span> État véhicules</a>
        <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="/location/public/index.php?url=users"        class="pnav-item <?= navActive('url=users') ?>"        style="--i:9"><span class="pnav-item-icon">⚙️</span> Utilisateurs</a>
        <a href="/location/public/index.php?url=admin/audit"  class="pnav-item <?= navActive('url=admin/audit') ?>"  style="--i:10"><span class="pnav-item-icon">🔍</span> Journal d'audit</a>
        <?php endif; ?>
      </div>
    </div>

    <a class="navbar-brand" href="/location/public/index.php?url=dashboard">
      <div class="brand-name">
        <span class="brand-title">AutoLocation</span>
        <span class="brand-sub">Gestion de flotte</span>
      </div>
    </a>

    <div class="nav-right">
      <?php if (!empty($_SESSION['user_nom'])): ?>
        <span class="nav-user">👤 <?= htmlspecialchars($_SESSION['user_nom']) ?></span>
      <?php endif; ?>
      <a href="/location/public/index.php?url=logout" class="nav-logout-btn">Déconnexion</a>
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
