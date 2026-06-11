<?php
/**
 * Controllers/ClientController.php
 *
 * Handles all HTTP actions for the client module:
 *   index()  — list clients with optional search/filter.
 *   add()    — create a new client (with CIN duplicate detection).
 *   edit()   — update an existing client record.
 *   delete() — permanently remove a client.
 *
 * All write actions are logged to audit_logs via audit_log().
 * The model (ClientModel) handles the actual SQL; this controller
 * is responsible for input validation and flow control.
 */
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/ClientModel.php";
require_once __DIR__ . "/../includes/audit.php";

class ClientController {

    /** @var PDO */
    private $conn;

    /** @var ClientModel */
    private $model;

    public function __construct() {
        $db = new Database();
        $this->conn  = $db->getConnection();
        $this->model = new ClientModel($this->conn);
    }

    /**
     * GET — Display the client list.
     *
     * Collects optional filter parameters from the query string and passes them
     * to ClientModel::getAll(). array_filter() removes empty strings so the model
     * does not append unnecessary WHERE clauses.
     *
     * Query params:
     *   q      — keyword search (name, CIN, phone, email).
     *   statut — 'actif' | 'inactif'.
     *   type   — 'particulier' | 'entreprise'.
     */
    public function index() {
        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'statut' => $_GET['statut'] ?? '',
            'type'   => $_GET['type'] ?? '',
        ];
        // array_filter removes keys whose values are empty strings.
        $clients = $this->model->getAll(array_filter($filters));
        require __DIR__ . "/../Views/clients/index.php";
    }

    /**
     * GET  — Show the new-client form.
     * POST — Validate and save the new client.
     *
     * Validation:
     *   - nom and prenom are required.
     *   - If a CIN is provided, it must be unique in the database.
     *
     * Optional fields are stored as NULL when left blank (ternary ?: null).
     * The new client is always created with statut = 'actif'.
     */
    public function add() {
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom    = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $cin    = trim($_POST['cin'] ?? '');

            // Required field validation.
            if (!$nom)    $errors[] = 'Le nom est requis.';
            if (!$prenom) $errors[] = 'Le prénom est requis.';

            // CIN uniqueness check — only runs if a CIN was provided and no other errors exist.
            if (!$errors && $cin) {
                $chk = $this->conn->prepare("SELECT id FROM clients WHERE cin=?");
                $chk->execute([$cin]);
                if ($chk->fetch()) $errors[] = "Le CIN « $cin » est déjà enregistré.";
            }

            if (!$errors) {
                // Pass all fields as named PDO placeholders; optional fields → NULL if empty.
                $this->model->create([
                    ':nom'               => $nom,
                    ':prenom'            => $prenom,
                    ':email'             => trim($_POST['email']) ?: null,
                    ':telephone'         => trim($_POST['telephone']) ?: null,
                    ':adresse'           => trim($_POST['adresse']) ?: null,
                    ':cin'               => $cin ?: null,
                    ':permis_numero'     => trim($_POST['permis_numero']) ?: null,
                    ':permis_categorie'  => $_POST['permis_categorie'] ?: 'B', // Default category B
                    ':permis_expiration' => $_POST['permis_expiration'] ?: null,
                    ':type_client'       => $_POST['type_client'] ?: 'particulier',
                    ':entreprise'        => trim($_POST['entreprise']) ?: null,
                    ':statut'            => 'actif', // New clients are always active
                    ':notes'             => trim($_POST['notes']) ?: null,
                ]);

                // Capture the auto-generated PK for the audit log entry.
                $newId = (int)$this->conn->lastInsertId();
                audit_log($this->conn, 'CREATE', 'clients', $newId, "Client créé : $nom $prenom" . ($cin ? " (CIN: $cin)" : ''));
                flash('success', 'Client ajouté avec succès.');
                header("Location: /location/public/index.php?url=clients");
                exit;
            }
        }

        // Render the add form — $errors array is available to the view.
        require __DIR__ . "/../Views/clients/add.php";
    }

    /**
     * GET  — Show the edit form pre-populated with existing client data.
     * POST — Validate and apply the updates.
     *
     * CIN uniqueness check on edit only runs if the CIN was actually changed
     * (prevents a false duplicate error when saving without modifying the CIN).
     *
     * Query params:
     *   id — Client PK (required; redirects to index if missing or not found).
     */
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        // Guard: redirect if no valid ID provided.
        if (!$id) { header("Location: /location/public/index.php?url=clients"); exit; }

        $c = $this->model->getById($id);
        // Guard: redirect if the client does not exist.
        if (!$c) { header("Location: /location/public/index.php?url=clients"); exit; }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom    = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $cin    = trim($_POST['cin'] ?? '');

            if (!$nom)    $errors[] = 'Le nom est requis.';
            if (!$prenom) $errors[] = 'Le prénom est requis.';

            // CIN duplicate check: only needed if the CIN changed, and exclude the current record.
            if (!$errors && $cin && $cin !== $c['cin']) {
                $chk = $this->conn->prepare("SELECT id FROM clients WHERE cin=? AND id != ?");
                $chk->execute([$cin, $id]);
                if ($chk->fetch()) $errors[] = "Le CIN « $cin » est déjà utilisé.";
            }

            if (!$errors) {
                $this->model->update([
                    ':id'                => $id,
                    ':nom'               => $nom,
                    ':prenom'            => $prenom,
                    ':email'             => trim($_POST['email']) ?: null,
                    ':telephone'         => trim($_POST['telephone']) ?: null,
                    ':adresse'           => trim($_POST['adresse']) ?: null,
                    ':cin'               => $cin ?: null,
                    ':permis_numero'     => trim($_POST['permis_numero']) ?: null,
                    ':permis_categorie'  => $_POST['permis_categorie'] ?: 'B',
                    ':permis_expiration' => $_POST['permis_expiration'] ?: null,
                    ':type_client'       => $_POST['type_client'] ?: 'particulier',
                    ':entreprise'        => trim($_POST['entreprise']) ?: null,
                    ':statut'            => $_POST['statut'] ?: 'actif',
                    ':notes'             => trim($_POST['notes']) ?: null,
                ]);
                audit_log($this->conn, 'UPDATE', 'clients', $id, "Client modifié : $nom $prenom");
                flash('success', 'Client mis à jour.');
                header("Location: /location/public/index.php?url=clients/view&id=$id");
                exit;
            }
        }

        // Render the edit form with $c (existing data) and $errors in scope.
        require __DIR__ . "/../Views/clients/edit.php";
    }

    /**
     * GET — Display the client profile page with stats and reservation history.
     * Redirects to the list if the ID is invalid or the client does not exist.
     */
    public function view() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=clients"); exit; }

        $c = $this->model->getById($id);
        if (!$c) {
            flash('danger', 'Client introuvable.');
            header("Location: /location/public/index.php?url=clients"); exit;
        }

        $stmt = $this->conn->prepare("
            SELECT r.*, v.marque, v.modele, v.numero
            FROM reservations r
            JOIN vehicles v ON r.vehicle_id = v.id
            WHERE r.client_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$id]);
        $reservations = $stmt->fetchAll();

        $totalLocations = count($reservations);
        $totalCA        = array_sum(array_column($reservations, 'montant_total'));
        $enCours        = count(array_filter($reservations, fn($r) => $r['statut'] === 'en cours'));

        require __DIR__ . "/../Views/clients/view.php";
    }

    /**
     * GET — Delete a client and redirect to the list.
     *
     * The client record is fetched first so its name can be included
     * in the audit log entry even after deletion.
     *
     * Note: no check for linked reservations — deleting a client with
     * active reservations will break foreign key integrity if not enforced at DB level.
     *
     * Query params:
     *   id — Client PK.
     */
    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $c = $this->model->getById($id);
            if ($c) {
                $this->model->delete($id);
                audit_log($this->conn, 'DELETE', 'clients', $id, "Client supprimé : {$c['nom']} {$c['prenom']}");
                flash('success', 'Client supprimé.');
            }
        }
        header("Location: /location/public/index.php?url=clients");
        exit;
    }
}
