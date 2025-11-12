<?php
// Load .env file
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

class Database {
    private $host;
    private $port;
    private $dbname;
    private $user;
    private $password;
    private $pdo;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->port = $_ENV['DB_PORT'] ?? '5432';
        $this->dbname = $_ENV['DB_NAME'] ?? 'nimonspedia_db';
        $this->user = $_ENV['DB_USER'] ?? 'postgres';
        $this->password = $_ENV['DB_PASSWORD'] ?? 'password';
    }

    public function connect() {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO(
                    "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}",
                    $this->user,
                    $this->password
                );
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }
}
?>