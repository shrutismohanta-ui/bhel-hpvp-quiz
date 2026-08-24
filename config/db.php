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
