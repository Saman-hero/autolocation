<?php
/**
 * Controllers/DashboardController.php
 *
 * Builds all data needed by the main dashboard view (Views/dashboard/index.php).
 *
 * The dashboard is a read-only aggregation page — no writes happen here.
 * It collects data from multiple tables via independent COUNT/SUM queries
 * and passes everything to the view as PHP variables.
 *
 * Data prepared for the view:
 *   $s              — KPI counters (vehicles, clients, reservations, revenue).
 *   $recent         — 8 most recently created reservations (widget table).
 *   $retards        — Active reservations whose planned return date has passed.
 *   $alertVehicles  — Vehicles approaching or past their oil-change interval.
 *   $top3           — Top 3 most-rented vehicles (trophy widget).
 *   $mByMonthData   — Reservation count per month (Chart.js bar chart).
 *   $mByMonthCA     — Revenue per month (Chart.js line chart).
 *   $moisLabels     — French month names for chart X-axis labels.
 *   $recentActivity — Last 10 audit log entries (activity feed).
 *   $vUtilization   — Per-vehicle rental count for the utilisation chart.
 *   $rBadge         — Maps reservation status → Bootstrap badge CSS class.
 */
require_once __DIR__ . "/../config/database.php";

class DashboardController {

    /** @var PDO Active database connection. */
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Aggregate all dashboard data and render the dashboard view.
     */
    public function index() {
        $conn = $this->conn;

        // ── KPI counters ────────────────────────────────────────────────────
        // Each query returns a single scalar — cast to int or float immediately.
        $s = [
            // Fleet breakdown by status
            'v_total'      => (int)$conn->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
            'v_dispo'      => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='disponible'")->fetchColumn(),
            'v_loue'       => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='loué'")->fetchColumn(),
            'v_maint'      => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='maintenance'")->fetchColumn(),
            'v_indispo'    => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='indisponible'")->fetchColumn(),

            // Client counters
            'c_total'      => (int)$conn->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
            'c_actif'      => (int)$conn->query("SELECT COUNT(*) FROM clients WHERE statut='actif'")->fetchColumn(),

            // Reservation counters
            'r_total'      => (int)$conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
            'r_encours'    => (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE statut='en cours'")->fetchColumn(),
            'r_mois'       => (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())")->fetchColumn(),

            // Revenue: current month (completed reservations only, keyed on actual return date)
            'ca_mois'      => (float)$conn->query("SELECT COALESCE(SUM(montant_total),0) FROM reservations WHERE statut='terminée' AND YEAR(date_retour_effectif)=YEAR(NOW()) AND MONTH(date_retour_effectif)=MONTH(NOW())")->fetchColumn(),

            // Revenue: previous month (used to compute the month-over-month variation)
            'ca_mois_prec' => (float)$conn->query("SELECT COALESCE(SUM(montant_total),0) FROM reservations WHERE statut='terminée' AND YEAR(date_retour_effectif)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND MONTH(date_retour_effectif)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH))")->fetchColumn(),

            // Open incident count
            'sin_ouverts'  => (int)$conn->query("SELECT COUNT(*) FROM sinistres WHERE statut='ouvert'")->fetchColumn(),
        ];

        // ── Derived KPIs ────────────────────────────────────────────────────
        // Fleet utilisation rate: percentage of vehicles currently rented out.
        $s['taux_utilisation'] = $s['v_total'] > 0 ? round(($s['v_loue'] / $s['v_total']) * 100, 1) : 0;

        // Month-over-month revenue variation in percent.
        // If previous month was 0 and current > 0, report +100 %.
        $s['ca_variation'] = $s['ca_mois_prec'] > 0
            ? round((($s['ca_mois'] - $s['ca_mois_prec']) / $s['ca_mois_prec']) * 100, 1)
            : ($s['ca_mois'] > 0 ? 100 : 0);

        // ── Recent reservations widget (latest 8) ────────────────────────────
        // Displayed in a compact table at the top of the dashboard.
        $recent = $conn->query("
            SELECT r.id, r.reference, r.statut, r.date_debut, r.date_fin_prevue, r.montant_total,
                   c.nom AS client_nom, c.prenom AS client_prenom,
                   v.numero AS vehicle_numero, v.marque, v.modele
            FROM reservations r
            JOIN clients c ON r.client_id = c.id
            JOIN vehicles v ON r.vehicle_id = v.id
            ORDER BY r.created_at DESC LIMIT 8
        ")->fetchAll();

        // ── Overdue rentals alert ────────────────────────────────────────────
        // Active reservations where the planned return date is in the past.
        $retards = $conn->query("
            SELECT r.id, r.reference, c.nom, c.prenom, v.numero, r.date_fin_prevue
            FROM reservations r
            JOIN clients c ON r.client_id = c.id
            JOIN vehicles v ON r.vehicle_id = v.id
            WHERE r.statut = 'en cours' AND r.date_fin_prevue < NOW()
        ")->fetchAll();

        // ── Oil-change alert vehicles ────────────────────────────────────────
        // Vehicles where km driven since last oil change ≥ 85 % of the service interval.
        // The 85 % threshold gives advance warning before the interval is exceeded.
        $alertVehicles = $conn->query("
            SELECT id, numero, marque, modele, kilometrage,
                   intervalle_vidange, derniere_vidange_km,
                   (kilometrage - derniere_vidange_km) AS km_depuis_vidange
            FROM vehicles
            WHERE derniere_vidange_km IS NOT NULL AND intervalle_vidange > 0
              AND (kilometrage - derniere_vidange_km) >= (intervalle_vidange * 0.85)
            ORDER BY (kilometrage - derniere_vidange_km) DESC
        ")->fetchAll();

        // ── Top 3 most-rented vehicles ───────────────────────────────────────
        // Displayed as a podium / trophy widget.
        $top3 = $conn->query("
            SELECT v.id, v.numero, v.marque, v.modele, COUNT(r.id) AS nb_locations
            FROM vehicles v LEFT JOIN reservations r ON r.vehicle_id = v.id
            GROUP BY v.id ORDER BY nb_locations DESC LIMIT 3
        ")->fetchAll();

        // ── Monthly chart data (current year) ────────────────────────────────
        $currentYear  = date('Y');
        $mByMonth     = $conn->query("
            SELECT MONTH(created_at) AS mois, COUNT(*) AS total,
                   COALESCE(SUM(montant_total),0) AS ca
            FROM reservations WHERE YEAR(created_at) = $currentYear
            GROUP BY MONTH(created_at) ORDER BY mois
        ")->fetchAll();

        // French month abbreviations for Chart.js X-axis labels (Jan–Déc).
        $moisLabels   = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];

        // Pre-fill 12-slot arrays with zeros; then map DB results by month index.
        $mByMonthData = array_fill(0, 12, 0); // Reservation counts per month
        $mByMonthCA   = array_fill(0, 12, 0); // Revenue per month

        foreach ($mByMonth as $row) {
            // MONTH() returns 1–12; array index is 0–11.
            $mByMonthData[(int)$row['mois'] - 1] = (int)$row['total'];
            $mByMonthCA[(int)$row['mois'] - 1]   = (float)$row['ca'];
        }

        // ── Recent activity feed (last 10 audit log entries) ─────────────────
        // Wrapped in try/catch because audit_logs may not exist in all environments.
        $recentActivity = [];
        try {
            $recentActivity = $conn->query("
                SELECT user_name, action, table_name, description, created_at
                FROM audit_logs ORDER BY created_at DESC LIMIT 10
            ")->fetchAll();
        } catch (Exception $e) {
            // Silently ignore — audit_logs table may not be set up yet.
        }

        // ── Vehicle utilisation chart data (top 10 vehicles by rental count) ─
        $vUtilization = $conn->query("
            SELECT v.numero, v.marque, v.modele, COUNT(r.id) AS nb
            FROM vehicles v LEFT JOIN reservations r ON r.vehicle_id = v.id
            GROUP BY v.id ORDER BY nb DESC LIMIT 10
        ")->fetchAll();

        // ── Status → Bootstrap badge CSS class map ────────────────────────────
        // Shared with the view so badge classes are not hard-coded in the template.
        $rBadge = [
            'en attente' => 'bg-secondary',
            'confirmée'  => 'bg-primary',
            'en cours'   => 'badge-encours',
            'terminée'   => 'badge-terminee',
            'annulée'    => 'badge-annulee',
        ];

        // Render the dashboard view with all prepared variables in scope.
        require __DIR__ . "/../Views/dashboard/index.php";
    }
}
