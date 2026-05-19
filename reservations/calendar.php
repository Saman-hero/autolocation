<?php
require_once "../config/database.php";
$db   = new Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Calendrier des réservations — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
  <style>
    #calendarContainer { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-sm); padding: 1.25rem; }
    .fc-event { font-size: .78rem !important; font-weight: 600; border-radius: 4px !important; }
    .fc-toolbar-title { font-size: 1.1rem !important; font-weight: 700; }
    .fc-button { font-size: .8rem !important; }
    .fc-button-primary { background-color: var(--primary) !important; border-color: var(--primary) !important; }
    .fc-button-primary:hover { background-color: var(--primary-dark) !important; border-color: var(--primary-dark) !important; }
    .fc-button-primary:not(:disabled).fc-button-active { background-color: var(--accent) !important; border-color: var(--accent-dark) !important; }

    /* Legend */
    .legend { display: flex; gap: 1rem; flex-wrap: wrap; font-size: .8rem; }
    .legend-item { display: flex; align-items: center; gap: .4rem; }
    .legend-dot { width: 14px; height: 14px; border-radius: 3px; }

    /* Tooltip */
    .cal-tooltip { position: fixed; z-index: 9999; background: #1a1a2e; color: #fff; padding: 10px 14px; border-radius: 8px; font-size: .8rem; pointer-events: none; box-shadow: 0 4px 20px rgba(0,0,0,.4); max-width: 240px; display: none; }
    .cal-tooltip .tt-title { font-weight: 700; margin-bottom: 4px; font-size: .85rem; }
    .cal-tooltip .tt-row { margin-top: 3px; color: rgba(255,255,255,.75); }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <div>
      <h1 class="page-title">Calendrier des réservations</h1>
      <div class="text-muted small">Vue mensuelle / hebdomadaire / journalière</div>
    </div>
    <div class="d-flex gap-2">
      <a href="index.php" class="btn btn-outline-secondary btn-sm">← Liste</a>
      <a href="add.php" class="btn btn-success btn-sm">+ Nouvelle location</a>
    </div>
  </div>

  <!-- Légende -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="legend">
        <div class="legend-item"><div class="legend-dot" style="background:#94a3b8"></div> En attente</div>
        <div class="legend-item"><div class="legend-dot" style="background:#1a3a5c"></div> Confirmée</div>
        <div class="legend-item"><div class="legend-dot" style="background:#16a34a"></div> En cours</div>
        <div class="legend-item"><div class="legend-dot" style="background:#64748b"></div> Terminée</div>
        <div class="legend-item"><div class="legend-dot" style="background:#dc2626"></div> Annulée</div>
      </div>
    </div>
  </div>

  <div id="calendarContainer">
    <div id="calendar"></div>
  </div>

</div>

<!-- Tooltip -->
<div class="cal-tooltip" id="calTooltip">
  <div class="tt-title" id="ttTitle"></div>
  <div class="tt-row" id="ttRef"></div>
  <div class="tt-row" id="ttClient"></div>
  <div class="tt-row" id="ttVehicle"></div>
  <div class="tt-row" id="ttStatut"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const tooltip = document.getElementById('calTooltip');

  const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    locale:        'fr',
    initialView:   'dayGridMonth',
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    height:      'auto',
    navLinks:    true,
    editable:    false,
    dayMaxEvents: true,

    events: function(info, successCallback, failureCallback) {
      fetch('/location/api/reservations-calendar.php?start=' + info.startStr + '&end=' + info.endStr)
        .then(r => r.json())
        .then(data => successCallback(data))
        .catch(err => { console.error(err); failureCallback(err); });
    },

    eventDidMount: function(info) {
      // Show tooltip on hover
      info.el.addEventListener('mouseenter', function(e) {
        const p = info.event.extendedProps;
        document.getElementById('ttTitle').textContent   = info.event.title;
        document.getElementById('ttRef').textContent     = 'Réf: ' + (p.reference || '');
        document.getElementById('ttClient').textContent  = 'Client: ' + (p.client || '');
        document.getElementById('ttVehicle').textContent = 'Véhicule: ' + (p.vehicle || '');
        document.getElementById('ttStatut').textContent  = 'Statut: ' + (p.statut || '');
        tooltip.style.display = 'block';
      });
      info.el.addEventListener('mousemove', function(e) {
        tooltip.style.left = (e.clientX + 14) + 'px';
        tooltip.style.top  = (e.clientY - 10) + 'px';
      });
      info.el.addEventListener('mouseleave', function() {
        tooltip.style.display = 'none';
      });
    },

    eventClick: function(info) {
      info.jsEvent.preventDefault();
      if (info.event.url) {
        window.location.href = info.event.url;
      }
    },

    eventTimeFormat: {
      hour:   '2-digit',
      minute: '2-digit',
      meridiem: false
    },

    buttonText: {
      today:    "Aujourd'hui",
      month:    'Mois',
      week:     'Semaine',
      day:      'Jour',
      list:     'Liste'
    }
  });

  calendar.render();
});
</script>
</body>
</html>
