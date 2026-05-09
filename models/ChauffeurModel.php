<?php

class ChauffeurModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($filters = []) {

        $sql = "
        SELECT 
            c.*,
            v.numero AS vehicle_numero,
            v.marque AS vehicle_marque,
            v.modele AS vehicle_modele,
            COUNT(mt.id) AS total_missions
        FROM chauffeurs c
        LEFT JOIN mission_team mt ON c.id = mt.chauffeur_id
        LEFT JOIN vehicles v ON c.vehicle_id = v.id
        WHERE 1=1
        ";

        // FILTERS
        if (!empty($filters['grade'])) {
            $sql .= " AND c.grade = :grade";
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND c.statut = :statut";
        }

        if (!empty($filters['vehicle'])) {
            $sql .= " AND c.vehicle_id = :vehicle";
        }

        $sql .= " GROUP BY c.id
        ORDER BY 
        CASE c.grade
            WHEN 'adjudant-chef' THEN 1
            WHEN 'adjudant' THEN 2
            WHEN 'sergent-chef' THEN 3
            WHEN 'sergent' THEN 4
            WHEN 'caporal chef' THEN 5
            WHEN 'caporal' THEN 6
            WHEN '1 classe' THEN 7
            WHEN '2 classe' THEN 8
            ELSE 9
        END ASC";

        $stmt = $this->conn->prepare($sql);

        foreach ($filters as $k => $v) {
            if ($v !== "") {
                $stmt->bindValue(":$k", $v);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM chauffeurs WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {

        $sql = "INSERT INTO chauffeurs 
        (nom, prenom, telephone, adresse, date_embauche, grade, matricule, cine, statut, niveau, type_permis, vehicle_id, lieu_detachement, date_detachement)
        VALUES
        (:nom, :prenom, :telephone, :adresse, :date_embauche, :grade, :matricule, :cine, :statut, :niveau, :type_permis, :vehicle_id, :lieu_detachement, :date_detachement)";

        return $this->conn->prepare($sql)->execute($data);
    }

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
            statut=:statut,
            niveau=:niveau,
            type_permis=:type_permis,
            vehicle_id=:vehicle_id,
            lieu_detachement=:lieu_detachement,
            date_detachement=:date_detachement
        WHERE id=:id";

        return $this->conn->prepare($sql)->execute($data);
    }

    public function delete($id) {
        return $this->conn->prepare("DELETE FROM chauffeurs WHERE id=?")->execute([$id]);
    }
}