<?php

class MissionModel {

    private $conn;
    private $table = "missions";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 🔹 GET ALL
    public function getAll() {
        return $this->conn->query("SELECT * FROM missions ORDER BY id DESC")->fetchAll();
    }

    // 🔹 CREATE
    public function create($data) {
        $sql = "INSERT INTO missions (reference, description, statut)
                VALUES (:reference, :description, :statut)";

        $this->conn->prepare($sql)->execute($data);

        return $this->conn->lastInsertId();
    }

    // 🔹 GET BY ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM missions WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // 🔥 DELETE
    public function delete($id) {
        $sql = "DELETE FROM missions WHERE id = :id";
        return $this->conn->prepare($sql)->execute([":id" => $id]);
    }
}