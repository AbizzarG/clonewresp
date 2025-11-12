<?php
require_once __DIR__ . '/Database.php';

class BaseSeeder {
    protected $pdo;

    public function __construct() {
        $db = new Database();
        $this->pdo = $db->connect();
    }

    protected function runSqlFile($filePath) {
        if (!file_exists($filePath)) {
            echo "Error file not found for: $filePath\n";
            return false;
        }

        $sql = file_get_contents($filePath);
        try {
            $this->pdo->exec($sql);
            echo "Executed: " . basename($filePath) . "\n";
            return true;
        } catch (PDOException $e) {
            echo "Error executing " . basename($filePath) . ": " . $e->getMessage() . "\n";
            return false;
        }
    }
}

class UserSeeder extends BaseSeeder {
    public function run() {
        echo "Seeding Users...\n";
        $filePath = __DIR__ . '/../database/seeder/user_seeder_001.sql';
        $this->runSqlFile($filePath);
        echo "[SUCCESS] Users successfully seeded!\n";
    }

    public function createUser($email, $password, $role, $name, $address = '', $balance = 0) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (email, password, role, name, address, balance) VALUES (?,?,?,?,?,?)";
        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute([$email, $hashedPassword, $role, $name, $address, $balance]);
            echo "Created user: $email\n";
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() == 23505) { // Unique constraint violation
                echo "[ERROR_SEEDING] User with the email: $email already exists!\n";
            } else {
                echo "[ERROR_USER_CREATION] Error creating user " . $e->getMessage() . "\n";
            }
            return false;
        }
    }

    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT user_id, email, role, name, balance FROM users ORDER BY created_at");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsersByRole($role) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at");
        $stmt->execute([$role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class StoreSeeder extends BaseSeeder {
    public function run() {
        echo "Seeding Stores...\n";
        $filePath = __DIR__ . '/../database/seeder/store_seeder_001.sql';
        $this->runSqlFile($filePath);
        echo "[SUCCESS] Stores successfully seeded!\n";
    }
}

class ProductSeeder extends BaseSeeder {
    public function run() {
        echo "Seeding Products...\n";

        // Run initial product seeder
        $filePath1 = __DIR__ . '/../database/seeder/product_seeder_001.sql';
        $this->runSqlFile($filePath1);

        // Run update seeder with images and rich descriptions
        $filePath2 = __DIR__ . '/../database/seeder/product_seeder_002_with_images.sql';
        $this->runSqlFile($filePath2);

        echo "[SUCCESS] Products successfully seeded!\n";
    }
}
?>