<?php
/**
 * models/VehicleModel.php
 *
 * Data-access layer for the `vehicles` table.
 * Encapsulates all SQL operations related to the fleet.
 *
 * Vehicle statuses managed by this model:
 *   'disponible'  — vehicle is available to rent.
 *   'loué'        — vehicle is currently out on a reservation.
 *   'maintenance' — vehicle is undergoing a service or repair.
 *   'indisponible'— manually marked unavailable by an administrator.
 *
 * Oil change tracking fields:
 *   type_vidange, intervalle_vidange, derniere_vidange_km, date_derniere_vidange
 *   — used by the dashboard to raise alerts when a vehicle is approaching its
 *     oil-change interval (≥ 85 % of the interval since last change).
 */
class VehicleModel {

    /** @var PDO Active database connection injected via constructor. */
    private $conn;

    /**
     * @param PDO $db  Active PDO connection.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Return all vehicles ordered by most recently added.
     *
     * @return array  All vehicle rows.
     */
    public function getAll() {
        return $this->conn->query("SELECT * FROM vehicles ORDER BY id DESC")->fetchAll();
    }

    /**
     * Fetch a single vehicle by primary key.
     *
     * @param int $id  Vehicle PK.
     * @return array|false  Vehicle row or false if not found.
     */
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM vehicles WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Insert a new vehicle into the fleet.
     *
     * @param array $data  Named placeholders for all insertable columns.
     *                     Keys: :numero, :immatriculation, :marque, :modele,
     *                           :annee, :couleur, :nb_places, :categorie,
     *                           :kilometrage, :statut, :prix_jour, :caution,
     *                           :type_vidange, :intervalle_vidange,
     *                           :derniere_vidange_km, :date_derniere_vidange
     * @return bool  True on success.
     */
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

    /**
     * Update all fields of an existing vehicle record.
     *
     * @param array $data  Named placeholders — same keys as create() plus :id.
     * @return bool  True on success.
     */
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

    /**
     * Permanently delete a vehicle record.
     *
     * @param int $id  Vehicle PK.
     * @return bool  True on success.
     */
    public function delete($id) {
        return $this->conn->prepare("DELETE FROM vehicles WHERE id=?")->execute([$id]);
    }

    /**
     * Return only vehicles with status 'disponible', sorted by category then brand.
     * Used when populating the vehicle selector on the new-reservation form —
     * ensures operators cannot book a vehicle already out or in maintenance.
     *
     * @return array  Available vehicle rows.
     */
    public function getAvailable() {
        return $this->conn->query("SELECT * FROM vehicles WHERE statut='disponible' ORDER BY categorie, marque")->fetchAll();
    }

    /**
     * Mark a vehicle as rented ('loué').
     * Called when a reservation transitions to 'en cours'.
     *
     * @param int $id  Vehicle PK.
     * @return bool
     */
    public function setLoue($id) {
        return $this->conn->prepare("UPDATE vehicles SET statut='loué' WHERE id=?")->execute([$id]);
    }

    /**
     * Mark a vehicle as available ('disponible').
     * Called when a reservation is finished or cancelled.
     *
     * @param int $id  Vehicle PK.
     * @return bool
     */
    public function setAvailable($id) {
        return $this->conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=?")->execute([$id]);
    }

    /**
     * Search vehicles with optional keyword and filter criteria.
     *
     * Keyword searches across: numero, marque, modele, immatriculation.
     * Separate filters for categorie and statut are ANDed together.
     * Uses positional placeholders (?) because the keyword is repeated
     * for each column rather than a single named placeholder.
     *
     * @param string|null $keyword   Partial text to search across key fields.
     * @param string|null $categorie Exact category match (e.g. 'SUV').
     * @param string|null $statut    Exact status match (e.g. 'disponible').
     * @return array  Matching vehicle rows ordered newest first.
     */
    public function search($keyword = null, $categorie = null, $statut = null) {
        $sql    = "SELECT * FROM vehicles WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            // The same keyword is compared against four different columns,
            // so the placeholder value must be added four times to the params array.
            $sql     .= " AND (numero LIKE ? OR marque LIKE ? OR modele LIKE ? OR immatriculation LIKE ?)";
            $params[] = "%$keyword%"; $params[] = "%$keyword%";
            $params[] = "%$keyword%"; $params[] = "%$keyword%";
        }
        if (!empty($categorie)) { $sql .= " AND categorie = ?"; $params[] = $categorie; }
        if (!empty($statut))    { $sql .= " AND statut = ?";    $params[] = $statut; }

        $sql .= " ORDER BY id DESC"; // Newest vehicles appear first
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
