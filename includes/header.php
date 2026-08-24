<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Shared Header Component
 */

require_once __DIR__ . '/functions.php';
$baseUrl = get_base_url();
$pageTitle = $pageTitle ?? 'BHEL-HPVP Vizag Quiz Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | BHEL-HPVP Vizag</title>
    
    <!-- Google Fonts for English, Devanagari (Hindi) & Telugu support -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Noto+Sans+Telugu:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Design System CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
</head>
<body>
    <nav class="bhel-navbar">
        <a href="<?= $baseUrl ?>dashboard.php" class="brand-logo" title="BHEL HPVP Vizag Portal" style="display: flex; align-items: center; gap: 14px; text-decoration: none;">
            <img src="<?= $baseUrl ?>assets/images/bhel_logo.svg" alt="BHEL Logo" style="height: 48px; width: auto;">
            <div class="brand-text" style="display: flex; flex-direction: column; justify-content: center;">
                <h1 style="font-size: 20px; font-weight: 800; color: #FFF; line-height: 1.2; letter-spacing: 0.5px; margin: 0;">BHEL - HPVP</h1>
                <span style="font-size: 14px; font-weight: 700; color: var(--bhel-gold); line-height: 1.2; letter-spacing: 0.5px; margin-top: 1px;">Quiz Portal</span>
            </div>
        </a>

        <?php if (is_logged_in()): ?>
            <div class="nav-user-menu">
                <div class="user-info-pill">
                    <i class="fa-solid fa-user-gear"></i>
                    <span><?= sanitize($_SESSION['full_name']) ?> (<?= sanitize($_SESSION['staff_no']) ?>)</span>
                    <span class="role-badge"><?= sanitize($_SESSION['user_role']) ?></span>
                </div>

                <?php if (is_admin()): ?>
                    <a href="<?= $baseUrl ?>admin/index.php" class="btn btn-outline" style="padding: 6px 12px; font-size: 13px;">
                        <i class="fa-solid fa-gauge-high"></i> Admin Panel
                    </a>
                <?php endif; ?>

                <a href="<?= $baseUrl ?>logout.php" class="btn-nav-logout" title="Logout Session">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        <?php endif; ?>
    </nav>
    <div class="main-container">
