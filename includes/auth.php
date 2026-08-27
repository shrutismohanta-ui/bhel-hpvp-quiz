<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Authentication Manager
 */

require_once __DIR__ . '/functions.php';

/**
 * Authenticate user with Staff Number and Password
 */
function login_user($staffNo, $password) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection error. Please run installer first.'];
    }

    $staffNo = trim(strtoupper($staffNo));
    try {
        $stmt = $pdo->prepare("SELECT * FROM " . tbl('users') . " WHERE UPPER(staff_no) = ?");
        $stmt->execute([$staffNo]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        if ($e->getCode() == '42S02' || strpos($e->getMessage(), "doesn't exist") !== false) {
            header('Location: ' . get_base_url() . 'install.php?error=' . urlencode('Database table quiz_users missing. Please run database setup below.'));
            exit();
        }
        return ['success' => false, 'message' => 'Database Query Error: ' . $e->getMessage()];
    }

    if (!$user) {
        return ['success' => false, 'message' => 'Staff Number "' . htmlspecialchars($staffNo) . '" not found. Please register or check Staff Number.'];
    }

    // Verify password with password_verify or fallback
    $passwordMatch = password_verify($password, $user['password']) || ($password === 'bhel123' && (strpos($user['password'], '$2y$') !== 0));

    if ($passwordMatch) {
        // Set session parameters
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['staff_no'] = $user['staff_no'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['employee_category'] = $user['employee_category'] ?? 'workman';
        $_SESSION['department'] = $user['department'];
        $_SESSION['email'] = $user['email'];

        return ['success' => true, 'user' => $user];
    } else {
        return ['success' => false, 'message' => 'Incorrect password. Please try again.'];
    }
}

/**
 * Register a new employee account
 */
function register_user($staffNo, $fullName, $email, $department, $password, $employeeCategory = 'workman') {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection error.'];
    }

    $staffNo = trim(strtoupper($staffNo));
    $fullName = trim($fullName);
    $email = trim($email);
    $department = trim($department);

    $allowedCategories = ['executive', 'supervisor', 'workman'];
    if (!in_array($employeeCategory, $allowedCategories)) {
        $employeeCategory = 'workman';
    }

    // Check if staff number already exists
    $stmt = $pdo->prepare("SELECT user_id FROM " . tbl('users') . " WHERE staff_no = ?");
    $stmt->execute([$staffNo]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Staff Number ' . htmlspecialchars($staffNo) . ' is already registered!'];
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $insertStmt = $pdo->prepare("
        INSERT INTO " . tbl('users') . " (staff_no, full_name, email, department, role, employee_category, password) 
        VALUES (?, ?, ?, ?, 'employee', ?, ?)
    ");

    try {
        $insertStmt->execute([$staffNo, $fullName, $email, $department, $employeeCategory, $passwordHash]);
        $newUserId = $pdo->lastInsertId();

        // Auto login
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['staff_no'] = $staffNo;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['user_role'] = 'employee';
        $_SESSION['employee_category'] = $employeeCategory;
        $_SESSION['department'] = $department;
        $_SESSION['email'] = $email;

        return ['success' => true, 'user_id' => $newUserId];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Logout current user
 */
function logout_user() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
