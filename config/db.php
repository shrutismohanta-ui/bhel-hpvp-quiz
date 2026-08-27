<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Database Configuration (PDO)
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'hpvpbhelweb');
define('DB_USER', 'hpvpbhelweb');
define('DB_PASS', 'webbhelhpvp');
define('DB_PREFIX', 'quiz_');

// Helper to get table name with prefix
function tbl($tableName) {
    return DB_PREFIX . $tableName;
}

/**
 * Get PDO Database Connection
 * @return PDO|null
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $authErr) {
            // Fallback to default XAMPP root connection if user creation was bypassed
            $pdo = new PDO($dsn, 'root', '', $options);
        }

        // Check if database tables have been initialized
        $currentScript = basename($_SERVER['PHP_SELF'] ?? '');
        if ($currentScript !== 'install.php') {
            try {
                $checkTable = $pdo->query("SHOW TABLES LIKE '" . DB_PREFIX . "users'");
                if ($checkTable->rowCount() === 0) {
                    header('Location: ' . get_base_url() . 'install.php?error=' . urlencode('Database tables have not been created yet. Please initialize the database below.'));
                    exit();
                }
            } catch (Exception $tblErr) {
                header('Location: ' . get_base_url() . 'install.php?error=' . urlencode('Database setup required: ' . $tblErr->getMessage()));
                exit();
            }
        }

        // Auto-migration for schema upgrades (employee_category, languages, target_categories)
        static $migrated = false;
        if (!$migrated) {
            $migrated = true;
            try {
                // Check quiz_users for employee_category
                $colUsers = $pdo->query("SHOW COLUMNS FROM " . tbl('users') . " LIKE 'employee_category'");
                if ($colUsers && $colUsers->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE " . tbl('users') . " ADD COLUMN `employee_category` ENUM('executive', 'supervisor', 'workman') NOT NULL DEFAULT 'workman' AFTER `role`");
                }

                // Check quiz_quizzes for languages
                $colLang = $pdo->query("SHOW COLUMNS FROM " . tbl('quizzes') . " LIKE 'languages'");
                if ($colLang && $colLang->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE " . tbl('quizzes') . " ADD COLUMN `languages` VARCHAR(50) NOT NULL DEFAULT 'en' AFTER `description_te`");
                }

                // Check quiz_quizzes for target_categories
                $colCat = $pdo->query("SHOW COLUMNS FROM " . tbl('quizzes') . " LIKE 'target_categories'");
                if ($colCat && $colCat->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE " . tbl('quizzes') . " ADD COLUMN `target_categories` VARCHAR(100) NOT NULL DEFAULT 'executive,supervisor,workman' AFTER `languages`");
                }
            } catch (Exception $migErr) {
                // Ignore migration error if tables do not exist yet
            }
        }

        return $pdo;
    } catch (PDOException $e) {
        // If database connection fails, offer setup/install option
        if (basename($_SERVER['PHP_SELF'] ?? '') !== 'install.php') {
            header('Location: ' . get_base_url() . 'install.php?error=' . urlencode('Database Connection Failed: ' . $e->getMessage()));
            exit();
        }
        return null;
    }
}
