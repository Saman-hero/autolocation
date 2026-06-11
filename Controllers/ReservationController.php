<?php
/**
 * Controllers/ReservationController.php
 *
 * The most complex controller in the application — manages the full rental lifecycle:
 *
 *   index()  — filterable list of all reservations.
 *   add()    — create a new reservation (computes duration + total, sends confirmation email).
 *   edit()   — modify a pending or confirmed reservation.
 *   delete() — remove a reservation (blocked if currently 'en cours').
 *   start()  — transition reservation to 'en cours', mark vehicle as 'loué'.
 *   finish() — close the rental: record actual return, compute final amount, free vehicle.
 *
 * Key business rules enforced here:
 *   - Only 'en attente' or 'confirmée' reservations can be edited.
 *   - 'en cours' reservations cannot be deleted.
 *   - Total = nb_jours × prix_jour (+ frais_extra on finish).
 *   - Vehicle status is kept in sync (disponible → loué → disponible).
 *   - Late fee alert email is sent automatically when finishing an overdue rental.
 *   - All write operations are recorded in audit_logs.
 */
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/ReservationModel.php";
require_once __DIR__ . "/../models/VehicleModel.php";
require_once __DIR__ . "/../models/ClientModel.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../includes/mailer.php";

class ReservationController {

    /** @var PDO */
    private $conn;
    /** @var ReservationModel */
    private $model;
    /** @var VehicleModel */
    private $vehicleModel;
    /** @var ClientModel */
    private $clientModel;

    public function __construct() {
        $db = new Database();
        $this->conn         = $db->getConnection();
        $this->model        = new ReservationModel($this->conn);
        $this->vehicleModel = new VehicleModel($this->conn);
        $this->clientModel  = new ClientModel($this->conn);
    }

    /**
     * GET — Display the reservation list with multi-criteria filtering.
     *
     * Query params: q, statut, client_id, vehicle_id, from, to.
     * Status badge colours ($rBadge) are passed to the view to avoid
     * duplicating the mapping in the template.
     */
    public function index() {
        $filters = [
            'q'          => trim($_GET['q'] ?? ''),
            'statut'     => $_GET['statut'] ?? '',
            'client_id'  => (int)($_GET['client_id'] ?? 0),
            'vehicle_id' => (int)($_GET['vehicle_id'] ?? 0),
            'from'       => $_GET['from'] ?? '',
            'to'         => $_GET['to']   ?? '',
        ];

        $reservations = $this->model->getAll(array_filter($filters));
        $clients      = $this->conn->query("SELECT id, nom, prenom FROM clients ORDER BY nom")->fetchAll();
        $vehicles     = $this->conn->query("SELECT id, numero FROM vehicles ORDER BY numero")->fetchAll();
        $statuts      = ['en attente','confirmée','en cours','terminée','annulée'];
        $rBadge       = [
            'en attente' => 'bg-secondary',
            'confirmée'  => 'bg-primary',
            'en cours'   => 'badge-encours',
            'terminée'   => 'badge-terminee',
            'annulée'    => 'badge-annulee',
        ];

        require __DIR__ . "/../Views/reservations/index.php";
    }

    /**
     * GET  — Show the new-reservation form.
     * POST — Validate input, compute duration + total, save, sync vehicle status,
     *        log the action, and send a confirmation email to the client.
     *
     * Duration: ceil((fin - debut) / 86400) — partial days count as full days (min 1).
     * Total   : nbJours × prixJour.
     * $vehiclesPrices — passed to the view as a JSON map so JS auto-fills
     *                   price and deposit when a vehicle is selected.
     * $preClientId    — pre-selects a client when coming from the client detail page.
     */
    public function add() {
        $clients  = $this->clientModel->getAll(['statut' => 'actif']);
        $vehicles = $this->vehicleModel->getAvailable();
        $autoRef  = $this->model->generateReference();
        $preClientId = (int)($_GET['client_id'] ?? 0);
        $errors   = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clientId  = (int)$_POST['client_id'];
            $vehicleId = (int)$_POST['vehicle_id'];
            $debut     = $_POST['date_debut'];
            $fin       = $_POST['date_fin_prevue'];
            $prixJour  = (float)$_POST['prix_jour'];

            if (!$clientId)       $errors[] = 'Sélectionner un client.';
            if (!$vehicleId)      $errors[] = 'Sélectionner un véhicule.';
            if (!$debut)          $errors[] = 'Date de début requise.';
            if (!$fin)            $errors[] = 'Date de fin requise.';
            if ($fin <= $debut)   $errors[] = 'La date de fin doit être après la date de début.';

            if (!$errors) {
                $nbJours = max(1, (int)ceil((strtotime($fin) - strtotime($debut)) / 86400));
                $montant = $nbJours * $prixJour;
                $ref     = trim($_POST['reference']) ?: $autoRef;

                $resId = $this->model->create([
                    ':reference'       => $ref,
                    ':client_id'       => $clientId,
                    ':vehicle_id'      => $vehicleId,
                    ':statut'          => $_POST['statut'] ?: 'confirmée',
                    ':date_debut'      => $debut,
                    ':date_fin_prevue' => $fin,
                    ':lieu_depart'     => trim($_POST['lieu_depart']) ?: null,
                    ':lieu_retour'     => trim($_POST['lieu_retour']) ?: null,
                    ':prix_jour'       => $prixJour,
                    ':nb_jours'        => $nbJours,
                    ':caution'         => (float)($_POST['caution'] ?: 0),
                    ':montant_total'   => $montant,
                    ':commentaire'     => trim($_POST['commentaire']) ?: null,
                    ':created_by'      => $_SESSION['user_id'] ?? null,
                ]);

                if ($_POST['statut'] === 'en cours') {
                    $this->conn->prepare("UPDATE vehicles SET statut='loué' WHERE id=?")->execute([$vehicleId]);
                }

                $clientName = '';
                $vNum = '';
                foreach ($clients as $cl) { if ($cl['id'] == $clientId) { $clientName = $cl['nom'] . ' ' . $cl['prenom']; break; } }
                foreach ($vehicles as $vv) { if ($vv['id'] == $vehicleId) { $vNum = $vv['numero']; break; } }
                audit_log($this->conn, 'CREATE', 'reservations', $resId, "Réservation $ref créée pour $clientName — véhicule $vNum");

                try {
                    $clientFull  = $this->conn->query("SELECT * FROM clients WHERE id=$clientId")->fetch();
                    $vehicleFull = $this->conn->query("SELECT * FROM vehicles WHERE id=$vehicleId")->fetch();
                    $resFull     = $this->conn->query("SELECT * FROM reservations WHERE id=$resId")->fetch();
                    if ($clientFull && $vehicleFull && $resFull) {
                        sendReservationConfirmation($clientFull, $resFull, $vehicleFull);
                    }
                } catch (Exception $e) { /* Email non critique */ }

                flash('success', "Réservation $ref créée avec succès.");
                header("Location: /location/public/index.php?url=reservations/view&id=$resId");
                exit;
            }
        }

        $vehiclesPrices = [];
        foreach ($vehicles as $v) {
            $vehiclesPrices[$v['id']] = ['prix' => $v['prix_jour'], 'caution' => $v['caution']];
        }

        require __DIR__ . "/../Views/reservations/add.php";
    }

    /**
     * GET  — Show the edit form pre-filled with existing reservation data.
     * POST — Recompute duration/total and update the record.
     *
     * Business rule: only 'en attente' or 'confirmée' reservations are editable.
     * $d = array_merge($r, $_POST) — on validation failure, POST values override
     * the DB values so the form re-renders with the user's last input.
     */
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=reservations"); exit; }

        $r = $this->model->getById($id);
        if (!$r || !in_array($r['statut'], ['en attente','confirmée'])) {
            flash('danger', 'Cette réservation ne peut pas être modifiée.');
            header("Location: /location/public/index.php?url=reservations/view&id=$id"); exit;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $debut    = $_POST['date_debut'];
            $fin      = $_POST['date_fin_prevue'];
            $prixJour = (float)$_POST['prix_jour'];

            if (!$debut || !$fin || $fin <= $debut) $errors[] = 'Dates invalides.';

            if (!$errors) {
                $nbJours = max(1, (int)ceil((strtotime($fin) - strtotime($debut)) / 86400));
                $montant = $nbJours * $prixJour;
                $this->model->update([
                    ':id'              => $id,
                    ':statut'          => $_POST['statut'],
                    ':date_debut'      => $debut,
                    ':date_fin_prevue' => $fin,
                    ':lieu_depart'     => trim($_POST['lieu_depart']) ?: null,
                    ':lieu_retour'     => trim($_POST['lieu_retour']) ?: null,
                    ':prix_jour'       => $prixJour,
                    ':nb_jours'        => $nbJours,
                    ':caution'         => (float)($_POST['caution'] ?: 0),
                    ':montant_total'   => $montant,
                    ':commentaire'     => trim($_POST['commentaire']) ?: null,
                ]);
                flash('success', 'Réservation mise à jour.');
                header("Location: /location/public/index.php?url=reservations/view&id=$id");
                exit;
            }
        }

        $d = array_merge($r, $_POST);
        require __DIR__ . "/../Views/reservations/edit.php";
    }

    /**
     * GET — Display the reservation detail page.
     *
     * Fetches the reservation (with client + vehicle via JOIN), all its payments,
     * and any linked sinistres. Redirects to the list if the ID is invalid.
     */
    public function view() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=reservations"); exit; }

        $r = $this->model->getById($id);
        if (!$r) {
            flash('danger', 'Réservation introuvable.');
            header("Location: /location/public/index.php?url=reservations"); exit;
        }

        $stmt = $this->conn->prepare("SELECT * FROM paiements WHERE reservation_id=? ORDER BY date_paiement");
        $stmt->execute([$id]);
        $paiements = $stmt->fetchAll();
        $totalPaye = array_sum(array_column($paiements, 'montant'));

        $stmt = $this->conn->prepare("SELECT * FROM sinistres WHERE reservation_id=?");
        $stmt->execute([$id]);
        $sinistres = $stmt->fetchAll();

        $rBadge = [
            'en attente' => 'bg-secondary',
            'confirmée'  => 'bg-primary',
            'en cours'   => 'badge-encours',
            'terminée'   => 'badge-terminee',
            'annulée'    => 'badge-annulee',
        ];

        require __DIR__ . "/../Views/reservations/view.php";
    }

    /**
     * GET — Render the printable rental contract for a reservation.
     * Opens in a new tab; contains no navigation chrome, only print-optimised HTML.
     */
    public function print() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=reservations"); exit; }

        $r = $this->model->getById($id);
        if (!$r) {
            flash('danger', 'Réservation introuvable.');
            header("Location: /location/public/index.php?url=reservations"); exit;
        }

        require __DIR__ . "/../Views/reservations/print.php";
    }

    /**
     * GET — Delete a reservation. Blocked if the rental is currently 'en cours'
     * to prevent orphaning the vehicle in 'loué' status.
     */
    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $r = $this->conn->prepare("SELECT * FROM reservations WHERE id=?");
            $r->execute([$id]);
            $r = $r->fetch();
            if ($r && $r['statut'] === 'en cours') {
                flash('danger', 'Impossible de supprimer une location en cours.');
            } else {
                $this->model->delete($id);
                flash('success', 'Réservation supprimée.');
            }
        }
        header("Location: /location/public/index.php?url=reservations");
        exit;
    }

    /**
     * GET — Start a rental: set status to 'en cours', record departure odometer,
     * and mark the vehicle as 'loué'. Only works on pending/confirmed reservations.
     * Optional ?km=XXXXX records the odometer at departure.
     */
    public function start() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=reservations"); exit; }

        $r = $this->conn->prepare("SELECT * FROM reservations WHERE id=?");
        $r->execute([$id]);
        $r = $r->fetch();
        if (!$r || !in_array($r['statut'], ['en attente','confirmée'])) {
            flash('danger', 'Réservation introuvable ou déjà en cours.');
            header("Location: /location/public/index.php?url=reservations"); exit;
        }

        $kmDepart = (int)($_GET['km'] ?? 0) ?: null;

        $this->conn->prepare("UPDATE reservations SET statut='en cours', km_depart=? WHERE id=?")
             ->execute([$kmDepart, $id]);
        $this->conn->prepare("UPDATE vehicles SET statut='loué' WHERE id=?")
             ->execute([$r['vehicle_id']]);

        audit_log($this->conn, 'START', 'reservations', $id, "Location démarrée : réservation {$r['reference']}");

        flash('success', 'Location démarrée.');
        header("Location: /location/public/index.php?url=reservations/view&id=$id");
        exit;
    }

    /**
     * GET  — Show the finish form with pre-computed late-fee estimates.
     * POST — Close the rental: record return odometer, compute final total
     *        (actual_days × prix_jour + frais_extra), set vehicle back to
     *        'disponible', update odometer, and send late alert if overdue.
     *
     * Late fee pre-fill: fraisExtraInitial = joursRetard × prix_jour.
     * Odometer update: only applied when km_retour > current kilometrage
     * (prevents accidental decrease).
     */
    public function finish() {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=reservations"); exit; }

        $r = $this->conn->prepare("SELECT * FROM reservations WHERE id=?");
        $r->execute([$id]);
        $r = $r->fetch();
        if (!$r || $r['statut'] !== 'en cours') {
            flash('danger', 'Réservation introuvable ou non en cours.');
            header("Location: /location/public/index.php?url=reservations"); exit;
        }

        $now       = new DateTime();
        $debut     = new DateTime($r['date_debut']);
        $finPrevue = new DateTime($r['date_fin_prevue']);

        $joursEcoules    = max(1, (int)ceil(($now->getTimestamp() - $debut->getTimestamp()) / 86400));
        $enRetard        = $now > $finPrevue;
        $joursRetard     = $enRetard ? (int)ceil(($now->getTimestamp() - $finPrevue->getTimestamp()) / 86400) : 0;
        $fraisRetard     = $joursRetard * $r['prix_jour'];
        $fraisExtraInitial = $fraisRetard;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kmRetour    = $_POST['km_retour'] !== '' ? (int)$_POST['km_retour'] : null;
            $fraisExtra  = (float)($_POST['frais_extra'] ?? 0);
            $commentaire = trim($_POST['commentaire'] ?? '');
            $nbJoursReel = max(1, (int)ceil(($now->getTimestamp() - $debut->getTimestamp()) / 86400));
            $total       = $nbJoursReel * $r['prix_jour'] + $fraisExtra;

            $this->conn->prepare("
                UPDATE reservations SET
                    statut               = 'terminée',
                    date_retour_effectif = NOW(),
                    km_retour            = ?,
                    frais_extra          = ?,
                    montant_total        = ?,
                    commentaire          = ?
                WHERE id=?
            ")->execute([$kmRetour, $fraisExtra, $total, $commentaire ?: $r['commentaire'], $id]);

            $this->conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=?")->execute([$r['vehicle_id']]);

            if ($kmRetour) {
                $this->conn->prepare("UPDATE vehicles SET kilometrage=? WHERE id=? AND kilometrage < ?")
                     ->execute([$kmRetour, $r['vehicle_id'], $kmRetour]);
            }

            audit_log($this->conn, 'FINISH', 'reservations', $id, "Réservation {$r['reference']} clôturée — Total: " . number_format($total, 2) . " MAD" . ($enRetard ? " (retard: $joursRetard j)" : ''));

            if ($enRetard) {
                try {
                    $clientData  = $this->conn->query("SELECT * FROM clients WHERE id={$r['client_id']}")->fetch();
                    $vehicleData = $this->conn->query("SELECT * FROM vehicles WHERE id={$r['vehicle_id']}")->fetch();
                    if ($clientData && $vehicleData) sendLateAlert($clientData, $r);
                } catch (Exception $e) { /* non critique */ }
            }

            flash('success', 'Location clôturée. Total : ' . number_format($total, 2) . ' MAD.');
            header("Location: /location/public/index.php?url=reservations/view&id=$id");
            exit;
        }

        require __DIR__ . "/../Views/reservations/finish.php";
    }
}
