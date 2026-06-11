<?php
/**
 * public/index.php — Front Controller / Application Router
 *
 * This is the single entry point for the entire application.
 * All requests are routed through this file via the URL parameter:
 *
 *   http://localhost/location/public/index.php?url=reservations/add
 *
 * How routing works:
 *   1. The `url` GET parameter is read and sanitised (trimmed of slashes).
 *   2. A switch statement maps each URL slug to a Controller method.
 *   3. The appropriate Controller is instantiated and its method is called.
 *   4. The Controller handles the request (reads DB, validates input …)
 *      and then require()s the corresponding View to render the page.
 *
 * All Controllers and shared helpers are loaded here via require_once so
 * they are available for every route without redundant includes.
 *
 * Default route: 'dashboard' — shown when no `url` parameter is provided.
 * Fallback: 404 response for any unrecognised route.
 */

// ── Bootstrap ──────────────────────────────────────────────────────────────
// config/database.php starts the session, defines flash(), guards auth,
// and provides the Database class — must be loaded first.
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/lang.php";

// ── Load all Controllers ───────────────────────────────────────────────────
// Loaded here once so the switch below can instantiate any of them.
require_once __DIR__ . "/../Controllers/AuthController.php";
require_once __DIR__ . "/../Controllers/DashboardController.php";
require_once __DIR__ . "/../Controllers/ReservationController.php";
require_once __DIR__ . "/../Controllers/ClientController.php";
require_once __DIR__ . "/../Controllers/VehicleController.php";
require_once __DIR__ . "/../Controllers/SinistreController.php";
require_once __DIR__ . "/../Controllers/MaintenanceController.php";
require_once __DIR__ . "/../Controllers/PaiementController.php";
require_once __DIR__ . "/../Controllers/HistoriqueController.php";
require_once __DIR__ . "/../Controllers/UserController.php";
require_once __DIR__ . "/../Controllers/EtatVehiculeController.php";
require_once __DIR__ . "/../Controllers/AdminController.php";
require_once __DIR__ . "/../Controllers/PublicController.php";
require_once __DIR__ . "/../Controllers/GPSController.php";

// ── Resolve the requested route ────────────────────────────────────────────
// Read ?url= from the query string; default to 'dashboard' if absent.
// trim('/', …) removes any leading/trailing slashes for robustness.
$url = trim($_GET['url'] ?? 'dashboard', '/');

// ── Route dispatch ─────────────────────────────────────────────────────────
// Each case maps a URL slug to exactly one Controller::method() call.
// Controllers instantiate themselves (open DB, load models), process the
// request (validate POST, run queries), then require() the View to render.
switch ($url) {

    // ── Auth ───────────────────────────────────────────────────────────────
    // Public routes — no login required (auth guard skips these in config/database.php).
    case 'login':           (new AuthController())->login();           break; // Show/process login form
    case 'logout':          (new AuthController())->logout();          break; // Destroy session, redirect to login
    case 'forgot-password': (new AuthController())->forgotPassword();  break; // Generate password-reset token
    case 'reset-password':  (new AuthController())->resetPassword();   break; // Validate token + set new password

    // ── Dashboard ──────────────────────────────────────────────────────────
    // Aggregates KPIs, revenue charts, late returns, and oil change alerts.
    case 'dashboard':       (new DashboardController())->index();      break;

    // ── Reservations ───────────────────────────────────────────────────────
    // Full reservation lifecycle: list → create → edit → start → finish → delete.
    case 'reservations':         (new ReservationController())->index();  break; // Filterable reservation list
    case 'reservations/add':     (new ReservationController())->add();    break; // New reservation form + save
    case 'reservations/edit':    (new ReservationController())->edit();   break; // Edit pending/confirmed reservation
    case 'reservations/delete':  (new ReservationController())->delete(); break; // Delete (blocked if 'en cours')
    case 'reservations/view':    (new ReservationController())->view();   break; // Reservation detail + payments + sinistres
    case 'reservations/print':   (new ReservationController())->print();  break; // Printable rental contract (new tab)
    case 'reservations/start':   (new ReservationController())->start();  break; // Mark as started, set vehicle to 'loué'
    case 'reservations/finish':  (new ReservationController())->finish(); break; // Close reservation, compute final total

    // ── Clients ────────────────────────────────────────────────────────────
    // CRUD for client records (individuals and companies).
    case 'clients':         (new ClientController())->index();  break; // Client list with search + filters
    case 'clients/add':     (new ClientController())->add();    break; // Add new client (CIN duplicate check)
    case 'clients/view':    (new ClientController())->view();   break; // Client profile + stats + reservation history
    case 'clients/edit':    (new ClientController())->edit();   break; // Edit existing client
    case 'clients/delete':  (new ClientController())->delete(); break; // Delete client record

    // ── Vehicles ───────────────────────────────────────────────────────────
    // CRUD for the vehicle fleet with category and status filtering.
    case 'vehicles':        (new VehicleController())->index();  break; // Fleet list with search + filters
    case 'vehicles/add':    (new VehicleController())->add();    break; // Register new vehicle
    case 'vehicles/edit':   (new VehicleController())->edit();   break; // Edit vehicle details + oil change info
    case 'vehicles/delete': (new VehicleController())->delete(); break; // Remove vehicle from fleet

    // ── Sinistres ──────────────────────────────────────────────────────────
    // Declare and manage incident/accident reports linked to vehicles.
    case 'sinistres':        (new SinistreController())->index();  break; // Incident list with type/status filters
    case 'sinistres/add':    (new SinistreController())->add();    break; // Declare a new incident
    case 'sinistres/delete': (new SinistreController())->delete(); break; // Delete incident record

    // ── Maintenance ────────────────────────────────────────────────────────
    // Schedule and track maintenance work; automatically updates vehicle status.
    case 'maintenance':        (new MaintenanceController())->index();  break; // Maintenance log with filters
    case 'maintenance/add':    (new MaintenanceController())->add();    break; // Log new maintenance entry
    case 'maintenance/edit':   (new MaintenanceController())->edit();   break; // Update existing maintenance record
    case 'maintenance/delete': (new MaintenanceController())->delete(); break; // Delete maintenance record

    // ── Paiements ──────────────────────────────────────────────────────────
    // Record and list payments against reservations (cash, card, transfer …).
    case 'paiements':      (new PaiementController())->index(); break; // Payment list with reservation/type filters
    case 'paiements/add':  (new PaiementController())->add();   break; // Log a new payment for a reservation

    // ── Historique ─────────────────────────────────────────────────────────
    // Full searchable history of all reservations regardless of status.
    case 'historique':     (new HistoriqueController())->index(); break;

    // ── Users ──────────────────────────────────────────────────────────────
    // Admin-only: manage operator and admin accounts.
    case 'users':          (new UserController())->index();  break; // User list (admin only)
    case 'users/add':      (new UserController())->add();    break; // Create new user account
    case 'users/edit':     (new UserController())->edit();   break; // Edit user details / reset password
    case 'users/delete':   (new UserController())->delete(); break; // Delete user (cannot delete own account)

    // ── État Véhicule ──────────────────────────────────────────────────────
    // Pre/post-rental inspection reports: fuel level, cleanliness, damage.
    case 'etat-vehicule':       (new EtatVehiculeController())->index(); break; // List all inspection sheets
    case 'etat-vehicule/add':   (new EtatVehiculeController())->add();   break; // Record departure or return condition
    case 'etat-vehicule/view':  (new EtatVehiculeController())->view();  break; // View a single inspection report

    // ── Admin ──────────────────────────────────────────────────────────────
    // Admin-only: audit log viewer with filters and pagination.
    case 'admin/audit':    (new AdminController())->audit(); break;

    // ── Public booking ─────────────────────────────────────────────────────
    case 'public':           (new PublicController())->index();        break;
    case 'public/book':      (new PublicController())->book();         break;
    case 'public/calendar':  (new PublicController())->calendar();     break;
    case 'public/confirmation': (new PublicController())->confirmation(); break;

    // ── GPS ────────────────────────────────────────────────────────────────
    case 'gps':              (new GPSController())->index();           break;
    case 'gps/update':       (new GPSController())->update();          break;

    // ── 404 Fallback ───────────────────────────────────────────────────────
    // Any unrecognised route returns a plain 404 response.
    default:
        http_response_code(404);
        echo "<h1>404 — Page introuvable</h1>";
        break;
}
