<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Portal Landing & Employee Login Page with Security CAPTCHA
 */

require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

// Generate CAPTCHA Code if not set
if (empty($_SESSION['captcha_code']) || isset($_GET['refresh_captcha'])) {
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $captchaCode = '';
    for ($i = 0; $i < 5; $i++) {
        $captchaCode .= $chars[rand(0, strlen($chars) - 1)];
    }
    $_SESSION['captcha_code'] = $captchaCode;
    
    if (isset($_GET['refresh_captcha'])) {
        echo json_encode(['code' => $_SESSION['captcha_code']]);
        exit();
    }
}

$error = $_GET['error'] ?? '';
$message = $_GET['message'] ?? '';

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $staffNo = $_POST['staff_no'] ?? '';
        $password = $_POST['password'] ?? '';
        $userCaptcha = strtoupper(trim($_POST['captcha_input'] ?? ''));

        // Verify CAPTCHA
        if (empty($userCaptcha) || $userCaptcha !== $_SESSION['captcha_code']) {
            $error = 'Security CAPTCHA verification failed. Please enter the code shown in the box.';
            
            // Regenerate fresh CAPTCHA code
            $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
            $captchaCode = '';
            for ($i = 0; $i < 5; $i++) {
                $captchaCode .= $chars[rand(0, strlen($chars) - 1)];
            }
            $_SESSION['captcha_code'] = $captchaCode;
        } else {
            $res = login_user($staffNo, $password);
            if ($res['success']) {
                if ($_SESSION['user_role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = $res['message'];
                // Regenerate fresh CAPTCHA code
                $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                $captchaCode = '';
                for ($i = 0; $i < 5; $i++) {
                    $captchaCode .= $chars[rand(0, strlen($chars) - 1)];
                }
                $_SESSION['captcha_code'] = $captchaCode;
            }
        }
    }
}

$pageTitle = 'Employee & Admin Login';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 460px; margin: 40px auto;">
    <div class="card" style="box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
        <div style="display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 25px; text-align: left;">
            <img src="assets/images/bhel_logo.svg" alt="BHEL Logo" style="height: 56px; width: auto;">
            <div style="display: flex; flex-direction: column; justify-content: center;">
                <h2 style="font-size: 22px; font-weight: 800; color: #FFF; line-height: 1.2; margin: 0;">BHEL - HPVP</h2>
                <div style="font-size: 15px; font-weight: 700; color: var(--bhel-gold); line-height: 1.2; margin-top: 2px;">Quiz Portal</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= sanitize($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label><i class="fa-solid fa-id-badge" style="color: var(--bhel-blue-accent);"></i> Staff / Employee Number</label>
                <input type="text" name="staff_no" class="form-control" placeholder="e.g. EMP1001 or ADMIN001" required uppercase autofocus>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-lock" style="color: var(--bhel-gold);"></i> Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>

            <!-- SECURITY CAPTCHA WIDGET -->
            <div class="form-group" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-light); padding: 16px; border-radius: 10px; margin-top: 20px;">
                <label style="color: var(--bhel-gold); font-size: 13px; margin-bottom: 8px; display: block;">
                    <i class="fa-solid fa-shield-halved"></i> Security Verification (Anti-Bot CAPTCHA) *
                </label>

                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                    <!-- CAPTCHA NOISE IMAGE -->
                    <img id="captcha-img" src="captcha.php" alt="Anti-Bot CAPTCHA" style="height: 46px; border-radius: 8px; border: 1px solid var(--bhel-gold); cursor: pointer; flex: 1; object-fit: cover;" onclick="refreshCaptcha()" title="Click to refresh CAPTCHA">

                    <!-- REFRESH CAPTCHA BUTTON -->
                    <button type="button" onclick="refreshCaptcha()" class="btn btn-outline" style="padding: 12px; font-size: 14px;" title="Refresh CAPTCHA Image">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </div>

                <input type="text" id="captcha-input" name="captcha_input" class="form-control" placeholder="Enter CAPTCHA code shown in image" required style="letter-spacing: 3px; font-weight: 700; text-transform: uppercase;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px; margin-top: 10px;">
                Log In to Quiz Portal <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <!-- Quick Demo Credentials Box -->
        <div style="margin-top: 25px; padding-top: 18px; border-top: 1px dashed var(--border-light); font-size: 13px; color: var(--text-secondary);">
            <div style="font-weight: 600; color: var(--bhel-gold); margin-bottom: 10px;">
                <i class="fa-solid fa-key"></i> Quick Demo Logins:
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="quickLogin('ADMIN001', 'bhel123')" class="btn btn-outline" style="flex: 1; padding: 8px; font-size: 12px;">
                    <i class="fa-solid fa-user-shield"></i> Admin Demo
                </button>
                <button type="button" onclick="quickLogin('EMP1001', 'bhel123')" class="btn btn-outline" style="flex: 1; padding: 8px; font-size: 12px;">
                    <i class="fa-solid fa-user"></i> Employee Demo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function refreshCaptcha() {
    document.getElementById('captcha-img').src = 'captcha.php?new=1&t=' + new Date().getTime();
    document.getElementById('captcha-input').value = '';
}

function quickLogin(staffNo, pass) {
    document.querySelector('input[name="staff_no"]').value = staffNo;
    document.querySelector('input[name="password"]').value = pass;
    document.getElementById('captcha-input').focus();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
