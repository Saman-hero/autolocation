<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Protect every page that includes this file
// login.php and logout.php are excluded by checking the script name
$_currentScript = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
if (!in_array($_currentScript, ['login.php', 'logout.php']) && empty($_SESSION['logged_in'])) {
    header("Location: /location/login.php");
    exit;
}

class Database {

    private $host = "localhost";
    private $db_name = "location";
    private $username = "root";
    private $password = "";

    public $conn;

    public function getConnection() {

        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8",
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch(Exception $e) {
            die("Erreur DB: " . $e->getMessage());
        }

        return $this->conn;
    }
}