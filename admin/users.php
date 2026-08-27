<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * User & Employee Roster Management
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = getDBConnection();
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Toggle Role
if (isset($_GET['action']) && $_GET['action'] === 'toggle_role' && isset($_GET['user_id'])) {
    $uId = (int)$_GET['user_id'];
    // Prevent demoting self
    if ($uId === (int)$_SESSION['user_id']) {
        header('Location: users.php?error=' . urlencode('You cannot change your own administrator role.'));
        exit();
    }
    $uStmt = $pdo->prepare("SELECT role FROM " . tbl('users') . " WHERE user_id = ?");
    $uStmt->execute([$uId]);
    $u = $uStmt->fetch();
    if ($u) {
        $newRole = $u['role'] === 'admin' ? 'employee' : 'admin';
        $up = $pdo->prepare("UPDATE " . tbl('users') . " SET role = ? WHERE user_id = ?");
        $up->execute([$newRole, $uId]);
        header('Location: users.php?message=' . urlencode('User role updated to ' . strtoupper($newRole)));
        exit();
    }
}

// Handle Update Category
if (isset($_GET['action']) && $_GET['action'] === 'update_category' && isset($_GET['user_id']) && isset($_GET['category'])) {
    $uId = (int)$_GET['user_id'];
    $cat = $_GET['category'];
    if (in_array($cat, ['executive', 'supervisor', 'workman'])) {
        $up = $pdo->prepare("UPDATE " . tbl('users') . " SET employee_category = ? WHERE user_id = ?");
        $up->execute([$cat, $uId]);
        header('Location: users.php?message=' . urlencode('Employee category updated to ' . ucfirst($cat)));
        exit();
    }
}

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $staffNo = trim($_POST['staff_no'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role = $_POST['role'] ?? 'employee';
    $empCategory = $_POST['employee_category'] ?? 'workman';
    $password = $_POST['password'] ?? 'bhel123';

    if (empty($staffNo) || empty($fullName)) {
        $error = 'Staff Number and Full Name are required.';
    } else {
        $res = register_user($staffNo, $fullName, $email, $department, $password, $empCategory);
        if ($res['success']) {
            if ($role === 'admin') {
                $up = $pdo->prepare("UPDATE " . tbl('users') . " SET role = 'admin' WHERE user_id = ?");
                $up->execute([$res['user_id']]);
            }
            header('Location: users.php?message=' . urlencode('New user ' . htmlspecialchars($staffNo) . ' added successfully!'));
            exit();
        } else {
            $error = $res['message'];
        }
    }
}

// Fetch all users
$users = $pdo->query("SELECT * FROM " . tbl('users') . " ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Employee Roster Management';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fa-solid fa-users" style="color: var(--bhel-gold);"></i> Employee & User Management</h2>
        <p>BHEL HPVP Vizag Staff Directory & Access Control</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= sanitize($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($error) ?></div>
<?php endif; ?>

<div class="grid-2" style="align-items: flex-start; margin-bottom: 30px;">
    <!-- Add User Card -->
    <div class="card">
        <h3 style="font-size: 18px; color: #FFF; margin-bottom: 20px;">
            <i class="fa-solid fa-user-plus" style="color: var(--bhel-blue-accent);"></i> Register New Staff Account
        </h3>

        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="add_user">

            <div class="form-group">
                <label>Staff Number *</label>
                <input type="text" name="staff_no" class="form-control" placeholder="e.g. EMP1009" required>
            </div>

            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" class="form-control" placeholder="Enter employee full name" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="staff@bhel.in">
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" class="form-control" value="Operations" placeholder="Department">
                </div>

                <div class="form-group">
                    <label>Employee Category *</label>
                    <select name="employee_category" class="form-control" required>
                        <option value="executive">Executive</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="workman" selected>Workman</option>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Account Role</label>
                    <select name="role" class="form-control">
                        <option value="employee">Employee</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" value="bhel123" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-check"></i> Register User Account
            </button>
        </form>
    </div>

    <!-- User List Card -->
    <div class="card">
        <h3 style="font-size: 18px; color: #FFF; margin-bottom: 20px;">
            <i class="fa-solid fa-address-book" style="color: var(--bhel-gold);"></i> Staff Directory (<?= count($users) ?>)
        </h3>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Staff No</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): 
                        $empCat = $u['employee_category'] ?? 'workman';
                        $catBadge = 'badge-info';
                        if ($empCat === 'executive') $catBadge = 'badge-warning';
                        elseif ($empCat === 'supervisor') $catBadge = 'badge-accent';
                        elseif ($empCat === 'workman') $catBadge = 'badge-success';
                    ?>
                        <tr>
                            <td><code><?= sanitize($u['staff_no']) ?></code></td>
                            <td>
                                <strong style="color: #FFF;"><?= sanitize($u['full_name']) ?></strong>
                                <div style="font-size: 11px; color: var(--text-secondary);"><?= sanitize($u['department']) ?></div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <span class="badge <?= $catBadge ?>"><?= ucfirst($empCat) ?></span>
                                    <select onchange="location.href='users.php?action=update_category&user_id=<?= $u['user_id'] ?>&category=' + this.value;" style="background: rgba(255,255,255,0.05); color: #FFF; border: 1px solid var(--border-light); border-radius: 4px; font-size: 10px; padding: 2px;">
                                        <option value="executive" <?= $empCat === 'executive' ? 'selected' : '' ?>>Executive</option>
                                        <option value="supervisor" <?= $empCat === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                        <option value="workman" <?= $empCat === 'workman' ? 'selected' : '' ?>>Workman</option>
                                    </select>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge-warning' : 'badge-info' ?>">
                                    <?= strtoupper($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?action=toggle_role&user_id=<?= $u['user_id'] ?>" class="btn btn-outline" style="padding: 4px 8px; font-size: 11px;">
                                        Make <?= $u['role'] === 'admin' ? 'Employee' : 'Admin' ?>
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: var(--text-muted);">(You)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
