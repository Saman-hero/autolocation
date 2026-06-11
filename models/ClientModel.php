<?php
/**
 * models/ClientModel.php
 *
 * Data-access layer for the `clients` table.
 * All SQL for client records lives here; controllers call these methods
 * and never write raw queries for client data themselves.
 *
 * Supported fields per client record:
 *   nom, prenom, email, telephone, adresse, cin (national ID),
 *   permis_numero, permis_categorie, permis_expiration,
 *   type_client ('particulier' | 'entreprise'), entreprise, statut, notes.
 */
class ClientModel {

    /** @var PDO Active database connection injected via constructor. */
    private $conn;

    /**
     * @param PDO $db  Active PDO connection (provided by Database::getConnection()).
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Return all clients, optionally filtered.
     *
     * Supported filter keys:
     *   'statut'      — 'actif' | 'inactif'
     *   'type_client' — 'particulier' | 'entreprise'
     *   'q'           — full-text search across nom, prenom, cin, telephone, email
     *
     * All filters use parameterised queries to prevent SQL injection.
     * Results are ordered alphabetically by last name then first name.
     *
     * @param array $filters  Associative array of optional filter values.
     * @return array          Array of client rows (associative arrays).
     */
    public function getAll($filters = []) {
        $sql    = "SELECT * FROM clients WHERE 1=1"; // '1=1' allows safe AND appending
        $params = [];

        // Filter by account status (active / inactive).
        if (!empty($filters['statut']))      { $sql .= " AND statut = :statut";           $params[':statut']      = $filters['statut']; }

        // Filter by client type (individual vs. company).
        if (!empty($filters['type_client'])) { $sql .= " AND type_client = :type_client"; $params[':type_client'] = $filters['type_client']; }

        // Full-text search — matches any of the main identifying fields.
        if (!empty($filters['q'])) {
            $sql .= " AND (nom LIKE :q OR prenom LIKE :q OR cin LIKE :q OR telephone LIKE :q OR email LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%'; // Wildcard on both sides for substring search
        }

        $sql .= " ORDER BY nom, prenom"; // Alphabetical by surname, then first name
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single client by primary key.
     *
     * @param int $id  Client PK.
     * @return array|false  Client row or false if not found.
     */
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM clients WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Insert a new client record.
     *
     * @param array $data  Named placeholders matching the INSERT column list.
     *                     Keys: :nom, :prenom, :email, :telephone, :adresse,
     *                           :cin, :permis_numero, :permis_categorie,
     *                           :permis_expiration, :type_client, :entreprise,
     *                           :statut, :notes
     * @return bool  True on success.
     */
    public function create($data) {
        $sql = "INSERT INTO clients
            (nom, prenom, email, telephone, adresse, cin, permis_numero,
             permis_categorie, permis_expiration, type_client, entreprise, statut, notes)
        VALUES
            (:nom, :prenom, :email, :telephone, :adresse, :cin, :permis_numero,
             :permis_categorie, :permis_expiration, :type_client, :entreprise, :statut, :notes)";
        return $this->conn->prepare($sql)->execute($data);
    }

    /**
     * Update all fields of an existing client record.
     *
     * @param array $data  Named placeholders — same keys as create() plus :id.
     * @return bool  True on success.
     */
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

    /**
     * Permanently delete a client record.
     * Note: the controller should check for linked reservations before calling this.
     *
     * @param int $id  Client PK.
     * @return bool  True on success.
     */
    public function delete($id) {
        return $this->conn->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
    }
}
