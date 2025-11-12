<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Seeder.php';

class Migration {
    private $pdo;
    private $migrationPath;
    private $seedersPath;

    public function __construct() {
        $db = new Database();
        $this->pdo = $db->connect();
        $this->migrationPath = __DIR__ . '/../database/migrations/';
        $this->seedersPath = __DIR__ . '/../database/seeder/';
    }

    public function runMigrations() {
        echo "[RUNNING] Starting database migrations...\n\n";

        // Create a migration table
        $this->createMigrationsTable();

        // Run init.sql
        if (!$this->isMigrationRun('init_schema')) {
            echo "[RUNNING] Running initial schema...\n";
            $this->runInitSchema();
            $this->recordMigration('init_schema');
        }

        echo "[SUCCESS_MIGRATE] Migrations completed!\n\n";
    }

    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id SERIAL PRIMARY KEY, 
            migration VARCHAR(255) NOT NULL UNIQUE,
            run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
    }

    private function isMigrationRun($migration) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
        return $stmt->fetchColumn() > 0;
    }

    private function runInitSchema() {
        $initSqlPath = __DIR__ . '/../database/init.sql';
        if (file_exists($initSqlPath)) {
            $sql = file_get_contents($initSqlPath);
            
            $this->pdo->exec($sql);
        }
    }

    private function recordMigration($migration) {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration]);
    }

    public function runSeeders() {
        echo "Starting database seeders...\n\n";

        // Create seeders
        $userSeeder = new UserSeeder();
        $storeSeeder = new StoreSeeder();
        $productSeeder = new ProductSeeder();

        // Run seeders in order (users -> stores -> products)
        $userSeeder->run();
        $storeSeeder->run();
        $productSeeder->run();

        echo "[SUCCESS_SEED] All seeders completed!\n";
    }

    public function runAll() {
        echo "------- SETUP DATABASE -------\n\n";
        $this->runMigrations();
        $this->runSeeders();

        echo "[SUCCESS_MIGRATE] Migrations process done!\n";
    }
}
?>