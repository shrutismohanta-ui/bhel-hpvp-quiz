<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Database Installer & Auto Setup Script
 */

session_start();

$message = '';
$error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Check if already installed
$installed = false;
try {
    $dsn = "mysql:host=localhost;dbname=hpvpbhelweb;charset=utf8mb4";
    $testPdo = new PDO($dsn, 'hpvpbhelweb', 'webbhelhpvp', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $testPdo->query("SHOW TABLES LIKE 'quiz_users'");
    if ($stmt->rowCount() > 0) {
        $installed = true;
    }
} catch (Exception $e) {
    $installed = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $rootHost = $_POST['root_host'] ?? 'localhost';
    $rootUser = $_POST['root_user'] ?? 'root';
    $rootPass = $_POST['root_pass'] ?? '';

    try {
        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ];

        // Step 1: Connect as Root or admin user to set up db
        $rootPdo = new PDO("mysql:host=$rootHost", $rootUser, $rootPass, $pdoOptions);

        // Attempt repair on corrupt MariaDB system tables if needed
        try {
            $rootPdo->exec("REPAIR TABLE mysql.db");
            $rootPdo->exec("REPAIR TABLE mysql.user");
        } catch (Exception $repErr) {
            // Ignore repair failures
        }

        // Create Database if not exists
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `hpvpbhelweb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Try creating dedicated user & grant privileges
        try {
            $rootPdo->exec("CREATE USER IF NOT EXISTS 'hpvpbhelweb'@'localhost' IDENTIFIED BY 'webbhelhpvp'");
            $rootPdo->exec("GRANT ALL PRIVILEGES ON `hpvpbhelweb`.* TO 'hpvpbhelweb'@'localhost'");
            $rootPdo->exec("FLUSH PRIVILEGES");
        } catch (Exception $userErr) {
            // If mysql.db is corrupt, GRANT will throw 1034. Continue using root/current connection safely.
        }

        // Step 2: Connect to hpvpbhelweb database
        $dbPdo = null;
        try {
            $dbPdo = new PDO("mysql:host=localhost;dbname=hpvpbhelweb;charset=utf8mb4", 'hpvpbhelweb', 'webbhelhpvp', $pdoOptions);
        } catch (Exception $connErr) {
            // Fallback to root connection if dedicated user privilege creation was bypassed
            $dbPdo = new PDO("mysql:host=$rootHost;dbname=hpvpbhelweb;charset=utf8mb4", $rootUser, $rootPass, $pdoOptions);
        }

        // Step 3: Read and Execute schema.sql query by query
        $schemaFile = __DIR__ . '/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("schema.sql file not found!");
        }

        $sql = file_get_contents($schemaFile);

        // Split into individual SQL statements to avoid unbuffered query issues
        $rawQueries = explode(';', $sql);
        foreach ($rawQueries as $rawQ) {
            // Strip comment lines
            $lines = explode("\n", $rawQ);
            $cleanLines = [];
            foreach ($lines as $line) {
                $trimmedLine = trim($line);
                if ($trimmedLine !== '' && strpos($trimmedLine, '--') !== 0) {
                    $cleanLines[] = $line;
                }
            }
            $cleanQuery = trim(implode("\n", $cleanLines));
            if (!empty($cleanQuery)) {
                $stmt = $dbPdo->prepare($cleanQuery);
                $stmt->execute();
                $stmt->closeCursor();
            }
        }

        $message = "Database `hpvpbhelweb` and tables with prefix `quiz_` created & seeded successfully!";
        $installed = true;
    } catch (Exception $e) {
        $error = "Installation Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - BHEL-HPVP Vizag Quiz Application</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bhel-navy: #0F2C59;
            --bhel-blue: #1A5F7A;
            --bhel-gold: #FFC107;
            --bg-dark: #0A192F;
            --card-bg: #112240;
            --text-light: #E6F1FF;
            --text-muted: #8892B0;
            --success: #10B981;
            --danger: #EF4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body {
            background: var(--bg-dark);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 35px;
            max-width: 550px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .header { text-align: center; margin-bottom: 25px; }
        .logo-badge {
            background: linear-gradient(135deg, var(--bhel-navy), var(--bhel-blue));
            color: var(--bhel-gold);
            font-weight: 800;
            font-size: 24px;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 2px solid var(--bhel-gold);
        }
        h1 { font-size: 22px; margin-bottom: 8px; color: #FFF; }
        p.subtitle { font-size: 14px; color: var(--text-muted); }
        .alert {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #FCA5A5; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #6EE7B7; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; color: var(--text-light); }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            background: #0A192F;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #FFF;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        input:focus { border-color: var(--bhel-gold); box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.15); }
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: #FFF;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); }
        .btn-primary { background: linear-gradient(135deg, var(--bhel-blue), var(--bhel-navy)); border: 1px solid var(--bhel-gold); color: var(--bhel-gold); }
        .info-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px dashed rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        .info-box strong { color: var(--bhel-gold); }
    </style>
</head>
<body>
    <div class="setup-card">
        <div class="header">
            <img src="assets/images/bhel_logo.svg" alt="BHEL HPVP Vizag Logo" style="height: 54px; width: auto; margin-bottom: 12px;">
            <h1>BHEL-HPVP Vizag Quiz Database Setup</h1>
            <p class="subtitle">1-Click Database Creation & Schema Installer</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($installed): ?>
            <div class="info-box">
                <strong>Database Ready!</strong><br>
                Target Database: <code>hpvpbhelweb</code><br>
                User: <code>hpvpbhelweb</code> | Password: <code>webbhelhpvp</code><br>
                Table Prefix: <code>quiz_</code><br><br>
                <strong>Pre-seeded Credentials:</strong><br>
                • Admin Account: Staff No: <code>ADMIN001</code> | Password: <code>bhel123</code><br>
                • Employee 1: Staff No: <code>EMP1001</code> | Password: <code>bhel123</code><br>
                • Employee 2: Staff No: <code>EMP1002</code> | Password: <code>bhel123</code>
            </div>
            <a href="index.php" class="btn">Launch BHEL-HPVP Quiz Portal &rarr;</a>
        <?php else: ?>
            <div class="info-box">
                This installer will create database <strong>hpvpbhelweb</strong>, configure user <strong>hpvpbhelweb</strong> with password <strong>webbhelhpvp</strong>, and seed trilingual sample quizzes (English, Hindi, Telugu).
            </div>
            <form method="POST" action="install.php">
                <input type="hidden" name="action" value="install">
                <div class="form-group">
                    <label>MySQL Host</label>
                    <input type="text" name="root_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>MySQL Admin Username (e.g. root for XAMPP)</label>
                    <input type="text" name="root_user" value="root" required>
                </div>
                <div class="form-group">
                    <label>MySQL Admin Password (Leave blank for default XAMPP)</label>
                    <input type="password" name="root_pass" placeholder="Enter root password if any">
                </div>
                <button type="submit" class="btn btn-primary">Initialize Database & Seed Data Now</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
