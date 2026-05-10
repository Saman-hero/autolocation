<?php

class VehicleModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        return $this->conn->query("SELECT * FROM vehicles ORDER BY id DESC")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM vehicles WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO vehicles
            (numero, immatriculation, marque, modele, annee, couleur, nb_places,
             categorie, kilometrage, statut, prix_jour, caution,
             type_vidange, intervalle_vidange, derniere_vidange_km, date_derniere_vidange)
        VALUES
            (:numero, :immatriculation, :marque, :modele, :annee, :couleur, :nb_places,
             :categorie, :kilometrage, :statut, :prix_jour, :caution,
             :type_vidange, :intervalle_vidange, :derniere_vidange_km, :date_derniere_vidange)";
        return $this->conn->prepare($sql)->execute($data);
    }

    public function update($data) {
        $sql = "UPDATE vehicles SET
            numero               = :numero,
            immatriculation      = :immatriculation,
            marque               = :marque,
            modele               = :modele,
            annee                = :annee,
            couleur              = :couleur,
            nb_places            = :nb_places,
            categorie            = :categorie,
            kilometrage          = :kilometrage,
            statut               = :statut,
            prix_jour            = :prix_jour,
            caution              = :caution,
            type_vidange         = :type_vidange,
            intervalle_vidange   = :intervalle_vidange,
            derniere_vidange_km  = :derniere_vidange_km,
            date_derniere_vidange= :date_derniere_vidange
        WHERE id = :id";
        return $this->conn->prepare($sql)->execute($data);
    }

    public function delete($id) {
        return $this->conn->prepare("DELETE FROM vehicles WHERE id=?")->execute([$id]);
    }

    public function getAvailable() {
        return $this->conn->query("SELECT * FROM vehicles WHERE statut='disponible' ORDER BY categorie, marque")->fetchAll();
    }

    public function setLoue($id) {
        return $this->conn->prepare("UPDATE vehicles SET statut='loué' WHERE id=?")->execute([$id]);
    }

    public function setAvailable($id) {
        return $this->conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=?")->execute([$id]);
    }

    public function search($keyword = null, $categorie = null, $statut = null) {
        $sql    = "SELECT * FROM vehicles WHERE 1=1";
        $params = [];
        if (!empty($keyword)) {
            $sql     .= " AND (numero LIKE ? OR marque LIKE ? OR modele LIKE ? OR immatriculation LIKE ?)";
            $params[] = "%$keyword%"; $params[] = "%$keyword%";
            $params[] = "%$keyword%"; $params[] = "%$keyword%";
        }
        if (!empty($categorie)) { $sql .= " AND categorie = ?"; $params[] = $categorie; }
        if (!empty($statut))    { $sql .= " AND statut = ?";    $params[] = $statut; }
        $sql .= " ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
