<?php

class ClientModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($filters = []) {
        $sql    = "SELECT * FROM clients WHERE 1=1";
        $params = [];
        if (!empty($filters['statut']))      { $sql .= " AND statut = :statut";           $params[':statut']      = $filters['statut']; }
        if (!empty($filters['type_client'])) { $sql .= " AND type_client = :type_client"; $params[':type_client'] = $filters['type_client']; }
        if (!empty($filters['q'])) {
            $sql .= " AND (nom LIKE :q OR prenom LIKE :q OR cin LIKE :q OR telephone LIKE :q OR email LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        $sql .= " ORDER BY nom, prenom";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM clients WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO clients
            (nom, prenom, email, telephone, adresse, cin, permis_numero,
             permis_categorie, permis_expiration, type_client, entreprise, statut, notes)
        VALUES
            (:nom, :prenom, :email, :telephone, :adresse, :cin, :permis_numero,
             :permis_categorie, :permis_expiration, :type_client, :entreprise, :statut, :notes)";
        return $this->conn->prepare($sql)->execute($data);
    }

    public function update($data) {
        $sql = "UPDATE clients SET
            nom               = :nom,
            prenom            = :prenom,
            email             = :email,
            telephone         = :telephone,
            adresse           = :adresse,
            cin               = :cin,
            permis_numero     = :permis_numero,
            permis_categorie  = :permis_categorie,
            permis_expiration = :permis_expiration,
            type_client       = :type_client,
            entreprise        = :entreprise,
            statut            = :statut,
            notes             = :notes
        WHERE id = :id";
        return $this->conn->prepare($sql)->execute($data);
    }

    public function delete($id) {
        return $this->conn->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
    }
}
