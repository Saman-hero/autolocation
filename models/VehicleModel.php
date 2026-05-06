<?php

class VehicleModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // GET ALL
    public function getAll() {
        return $this->conn->query("SELECT * FROM vehicles ORDER BY id DESC")->fetchAll();
    }

    // GET BY ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM vehicles WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // CREATE
    public function create($data) {
        $sql = "INSERT INTO vehicles
        (numero, marque, modele, type, annee, kilometrage, statut)
        VALUES (:numero, :marque, :modele, :type, :annee, :kilometrage, :statut)";

        return $this->conn->prepare($sql)->execute($data);
    }

    // UPDATE
    public function update($data) {
        $sql = "UPDATE vehicles SET
        numero=:numero,
        marque=:marque,
        modele=:modele,
        type=:type,
        annee=:annee,
        kilometrage=:kilometrage,
        statut=:statut
        WHERE id=:id";

        return $this->conn->prepare($sql)->execute($data);
    }

    // DELETE
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM vehicles WHERE id=?");
        return $stmt->execute([$id]);
    }

    // ⭐ DISPONIBLES (IMPORTANT POUR MISSIONS)
    public function getAvailable() {
        return $this->conn->query("
            SELECT * FROM vehicles
            WHERE statut = 'disponible'
            ORDER BY id DESC
        ")->fetchAll();
    }

    // ⭐ METTRE EN MISSION
    public function setOnMission($id) {
        $stmt = $this->conn->prepare("
            UPDATE vehicles SET statut='en mission' WHERE id=?
        ");
        return $stmt->execute([$id]);
    }

    // ⭐ LIBÉRER (option future)
    public function setAvailable($id) {
        $stmt = $this->conn->prepare("
            UPDATE vehicles SET statut='disponible' WHERE id=?
        ");
        return $stmt->execute([$id]);
    }
}