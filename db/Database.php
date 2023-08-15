<?php
namespace app\core\db;
use PDO;
use app\core\Application;

class Database {
    public PDO $pdo;
    public function __construct(array $config) {
        $dsn = $config['dsn'] ?? '';
        $user = $config['user'] ?? '';
        $password = $config['password'] ?? '';
        $this -> pdo = new PDO($dsn, $user, $password);
        $this -> pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function applyMigrations() {
        $this -> createMigrationsTable();
        $appliedMigrations = $this -> getAppliedMigrations();

        $files = scandir(Application::$ROOT_DIR . '/migrations');

        $toApplyMigrations = array_diff($files, $appliedMigrations);
        $newMigrations = [];
        foreach ($toApplyMigrations as $migration) {
            if ($migration === '.' || $migration === '..') {
                continue;
            }
            require Application::$ROOT_DIR.'/migrations/' . $migration;
            $className = pathinfo($migration, PATHINFO_FILENAME);
            $instance = new $className();
            $this -> log("Applying migration $migration");
            $instance -> up();
            $this -> log("Applied migration $migration");
            $newMigrations[] = $migration;
        }

        if (!empty($newMigrations)) {
            $this -> saveMigrations($newMigrations);
        } else {
            $this -> log("All migrations are applied");       
        }
    }

    public function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR (255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=INNODB;";
        $this -> pdo -> exec($sql);
    }

    public function getAppliedMigrations() {
        $sql = "SELECT migration FROM migrations";
        $statement = $this -> pdo -> prepare($sql);
        $statement -> execute();

        return $statement -> fetchAll(PDO::FETCH_COLUMN);
    }

    public function saveMigrations(array $migrations) {
        $values = implode(",", array_map(fn($m) => "('$m')", $migrations));
        $sql = "INSERT INTO migrations (migration) VALUES $values";
        $statement = $this -> pdo -> prepare($sql);
        $statement -> execute();
    }

    public function prepare($sql) {
        return $this -> pdo -> prepare($sql);
    }

    protected function log($message) {
        echo "[" . date("Y-m-d H:i:s") . "] - " . $message . PHP_EOL;
    }
}