<?php
/**
 * Controllers/UserController.php
 *
 * Admin-only controller for managing user accounts.
 * The constructor enforces role='admin' — non-admins are redirected.
 *
 * Actions:
 *   index()  — list all users ordered by role then surname.
 *   add()    — create a new user account (admin or operator).
 *   edit()   — update a user's name, username, role, or password.
 *   delete() — remove a user account (cannot delete own account).
 *
 * Security rules enforced:
 *   - Passwords are stored as bcrypt hashes (PASSWORD_DEFAULT).
 *   - Username uniqueness is checked before create/update.
 *   - A user cannot change their own role (prevents accidental self-demotion).
 *   - A user cannot delete their own account.
 *   - Editing leaves the password unchanged if the new password field is blank.
 *
 * All write actions are recorded in audit_logs via audit_log().
 */
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/audit.php";

class UserController {

    /** @var PDO */
    private $conn;

    /**
     * Open the DB connection and enforce admin-only access.
     * Any non-admin user is redirected with an error message.
     */
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();

        // Role guard: only admins can manage user accounts.
        if ($_SESSION['user_role'] !== 'admin') {
            flash('danger', 'Accès réservé aux administrateurs.');
            header("Location: /location/public/index.php?url=dashboard"); exit;
        }
    }

    /**
     * GET — Display all user accounts sorted by role then surname.
     * Passwords are included in the SELECT * but must NEVER be displayed in the view.
     */
    public function index() {
        $users = $this->conn->query("SELECT * FROM users ORDER BY role, nom")->fetchAll();
        require __DIR__ . "/../Views/users/index.php";
    }

    /**
     * GET  — Show the create-user form.
     * POST — Validate and create the new account.
     *
     * Validation:
     *   - nom, prenom, username required.
     *   - Password must be ≥ 6 characters and match the confirmation.
     *   - Username must be unique across all users.
     */
    public function add() {
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom      = trim($_POST['nom'] ?? '');
            $prenom   = trim($_POST['prenom'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm'] ?? '';
            $role     = $_POST['role'] ?? 'operateur';

            if (!$nom)                       $errors[] = 'Nom requis.';
            if (!$prenom)                    $errors[] = 'Prénom requis.';
            if (!$username)                  $errors[] = 'Identifiant requis.';
            if (strlen($password) < 6)       $errors[] = 'Mot de passe : 6 caractères minimum.';
            if ($password !== $confirm)      $errors[] = 'Les mots de passe ne correspondent pas.';

            if (!$errors) {
                $chk = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
                $chk->execute([$username]);
                if ($chk->fetch()) $errors[] = "L'identifiant « $username » est déjà utilisé.";
            }

            if (!$errors) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $this->conn->prepare("INSERT INTO users (nom, prenom, username, password, role) VALUES (?,?,?,?,?)")
                     ->execute([$nom, $prenom, $username, $hash, $role]);
                $newId = (int)$this->conn->lastInsertId();
                audit_log($this->conn, 'CREATE', 'users', $newId, "Utilisateur créé : $username ($role)");
                flash('success', "Utilisateur « $username » créé avec succès.");
                header("Location: /location/public/index.php?url=users"); exit;
            }
        }

        require __DIR__ . "/../Views/users/add.php";
    }

    /**
     * GET  — Show the edit-user form pre-filled with existing data.
     * POST — Apply changes. If the password field is blank, the existing hash is kept.
     *
     * Self-role-change guard: a user cannot demote themselves from admin,
     * preventing a situation where no admin remains.
     */
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=users"); exit; }

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) { header("Location: /location/public/index.php?url=users"); exit; }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom      = trim($_POST['nom'] ?? '');
            $prenom   = trim($_POST['prenom'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $role     = $_POST['role'] ?? 'operateur';
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm'] ?? '';

            if (!$nom)      $errors[] = 'Nom requis.';
            if (!$prenom)   $errors[] = 'Prénom requis.';
            if (!$username) $errors[] = 'Identifiant requis.';
            if ($password && strlen($password) < 6)  $errors[] = 'Mot de passe : 6 caractères minimum.';
            if ($password && $password !== $confirm)  $errors[] = 'Les mots de passe ne correspondent pas.';
            if ($id == $_SESSION['user_id'] && $role !== 'admin') $errors[] = 'Vous ne pouvez pas changer votre propre rôle.';

            if (!$errors) {
                $chk = $this->conn->prepare("SELECT id FROM users WHERE username=? AND id!=?");
                $chk->execute([$username, $id]);
                if ($chk->fetch()) $errors[] = "L'identifiant « $username » est déjà utilisé.";
            }

            if (!$errors) {
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $this->conn->prepare("UPDATE users SET nom=?,prenom=?,username=?,password=?,role=? WHERE id=?")
                         ->execute([$nom, $prenom, $username, $hash, $role, $id]);
                } else {
                    $this->conn->prepare("UPDATE users SET nom=?,prenom=?,username=?,role=? WHERE id=?")
                         ->execute([$nom, $prenom, $username, $role, $id]);
                }
                audit_log($this->conn, 'UPDATE', 'users', $id, "Utilisateur modifié : $username");
                flash('success', 'Utilisateur mis à jour.');
                header("Location: /location/public/index.php?url=users"); exit;
            }
        }

        require __DIR__ . "/../Views/users/edit.php";
    }

    /**
     * POST — Delete a user account by PK.
     *
     * Uses POST (not GET) for the ID to prevent accidental deletion via a
     * URL being crawled or shared. Self-deletion is blocked.
     *
     * The username is fetched before deletion to include it in the audit log
     * even after the record no longer exists.
     */
    public function delete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) { // Cannot delete own account
            flash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
            header("Location: /location/public/index.php?url=users"); exit;
        }
        if ($id) {
            $stmt = $this->conn->prepare("SELECT username FROM users WHERE id=?");
            $stmt->execute([$id]);
            $u = $stmt->fetch();
            $this->conn->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            audit_log($this->conn, 'DELETE', 'users', $id, "Utilisateur supprimé : " . ($u ? $u['username'] : "ID $id"));
            flash('success', 'Utilisateur supprimé.');
        }
        header("Location: /location/public/index.php?url=users"); exit;
    }
}
