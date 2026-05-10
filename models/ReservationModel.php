<?php

class ReservationModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($filters = []) {
        $sql = "
            SELECT r.*,
                   c.nom AS client_nom, c.prenom AS client_prenom,
                   v.numero AS vehicle_numero, v.marque, v.modele
            FROM reservations r
            JOIN clients  c ON r.client_id  = c.id
            JOIN vehicles v ON r.vehicle_id = v.id
            WHERE 1=1
        ";
        $params = [];
        if (!empty($filters['statut']))     { $sql .= " AND r.statut = :statut";         $params[':statut']     = $filters['statut']; }
        if (!empty($filters['client_id']))  { $sql .= " AND r.client_id = :client_id";   $params[':client_id']  = $filters['client_id']; }
        if (!empty($filters['vehicle_id'])) { $sql .= " AND r.vehicle_id = :vehicle_id"; $params[':vehicle_id'] = $filters['vehicle_id']; }
        if (!empty($filters['from']))       { $sql .= " AND DATE(r.date_debut) >= :from"; $params[':from']      = $filters['from']; }
        if (!empty($filters['to']))         { $sql .= " AND DATE(r.date_debut) <= :to";   $params[':to']        = $filters['to']; }
        if (!empty($filters['q'])) {
            $sql .= " AND (r.reference LIKE :q OR c.nom LIKE :q OR c.prenom LIKE :q OR v.numero LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        $sql .= " ORDER BY r.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT r.*,
                   c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_tel,
                   c.cin AS client_cin, c.permis_numero, c.email AS client_email,
                   v.numero AS vehicle_numero, v.marque, v.modele, v.categorie, v.couleur
            FROM reservations r
            JOIN clients  c ON r.client_id  = c.id
            JOIN vehicles v ON r.vehicle_id = v.id
            WHERE r.id=?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO reservations
            (reference, client_id, vehicle_id, statut, date_debut, date_fin_prevue,
             lieu_depart, lieu_retour, prix_jour, nb_jours, caution, montant_total, commentaire, created_by)
        VALUES
            (:reference, :client_id, :vehicle_id, :statut, :date_debut, :date_fin_prevue,
             :lieu_depart, :lieu_retour, :prix_jour, :nb_jours, :caution, :montant_total, :commentaire, :created_by)";
        $this->conn->prepare($sql)->execute($data);
        return $this->conn->lastInsertId();
    }

    public function update($data) {
        $sql = "UPDATE reservations SET
            statut          = :statut,
            date_debut      = :date_debut,
            date_fin_prevue = :date_fin_prevue,
            lieu_depart     = :lieu_depart,
            lieu_retour     = :lieu_retour,
            prix_jour       = :prix_jour,
            nb_jours        = :nb_jours,
            caution         = :caution,
            montant_total   = :montant_total,
            commentaire     = :commentaire
        WHERE id = :id";
        return $this->conn->prepare($sql)->execute($data);
    }

    public function delete($id) {
        return $this->conn->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
    }

    private function nextReference(): string {
        $year = date('Y');
        $last = $this->conn->query("
            SELECT reference FROM reservations
            WHERE reference LIKE 'LOC-$year-%'
            ORDER BY id DESC LIMIT 1
        ")->fetchColumn();
        $num = 1;
        if ($last) {
            $parts = explode('-', $last);
            $num   = (int)end($parts) + 1;
        }
        return sprintf('LOC-%s-%03d', $year, $num);
    }

    public function generateReference(): string {
        return $this->nextReference();
    }
}
