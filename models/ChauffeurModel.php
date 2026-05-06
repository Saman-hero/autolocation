<?php

class ChauffeurModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 🔥 GET ALL (SANS ERREUR)
    public function getAll() {

        $sql = "
        SELECT 
            c.*,
            v.numero AS vehicle_numero,
            v.marque AS vehicle_marque,
            v.modele AS vehicle_modele
        FROM chauffeurs c
        LEFT JOIN mission_team mt 
            ON c.id = mt.chauffeur_id
        LEFT JOIN vehicles v 
            ON mt.vehicle_id = v.id
        ORDER BY c.id DESC
        ";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔥 GET BY ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM chauffeurs WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔥 CREATE
    public function create($data) {
        $sql = "INSERT INTO chauffeurs 
        (nom, prenom, telephone, adresse, date_embauche, grade, matricule, cine, statut)
        VALUES
        (:nom, :prenom, :telephone, :adresse, :date_embauche, :grade, :matricule, :cine, :statut)";

        return $this->conn->prepare($sql)->execute($data);
    }

    // 🔥 UPDATE
    public function update($data) {
        $sql = "UPDATE chauffeurs SET
            nom=:nom,
            prenom=:prenom,
            telephone=:telephone,
            adresse=:adresse,
            date_embauche=:date_embauche,
            grade=:grade,
            matricule=:matricule,
            cine=:cine,
            statut=:statut
        WHERE id=:id";

        return $this->conn->prepare($sql)->execute($data);
    }

    // 🔥 DELETE
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM chauffeurs WHERE id=?");
        return $stmt->execute([$id]);
    }
}