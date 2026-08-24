<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Submission & AJAX Response Backend
 */

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'save_response') {
    // AJAX Request to record continuous answer selection
    header('Content-Type: application/json');

    $attemptId = (int)($_POST['attempt_id'] ?? 0);
    $questionId = (int)($_POST['question_id'] ?? 0);
    $selectedOption = isset($_POST['selected_option']) && $_POST['selected_option'] !== '' ? (int)$_POST['selected_option'] : null;
    $isMarkedReview = isset($_POST['is_marked_review']) ? (int)$_POST['is_marked_review'] : 0;

    // Verify ownership of attempt
    $attStmt = $pdo->prepare("SELECT status FROM " . tbl('attempts') . " WHERE attempt_id = ? AND user_id = ?");
    $attStmt->execute([$attemptId, $userId]);
    $att = $attStmt->fetch();

    if (!$att || $att['status'] !== 'in_progress') {
        echo json_encode(['success' => false, 'message' => 'Invalid or completed attempt.']);
        exit();
    }

    // Insert or Update response
    $checkStmt = $pdo->prepare("SELECT response_id FROM " . tbl('attempt_responses') . " WHERE attempt_id = ? AND question_id = ?");
    $checkStmt->execute([$attemptId, $questionId]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        $upStmt = $pdo->prepare("
            UPDATE " . tbl('attempt_responses') . " 
            SET selected_option = ?, is_marked_review = ?
            WHERE attempt_id = ? AND question_id = ?
        ");
        $upStmt->execute([$selectedOption, $isMarkedReview, $attemptId, $questionId]);
    } else {
        $insStmt = $pdo->prepare("
            INSERT INTO " . tbl('attempt_responses') . " (attempt_id, question_id, selected_option, is_marked_review) 
            VALUES (?, ?, ?, ?)
        ");
        $insStmt->execute([$attemptId, $questionId, $selectedOption, $isMarkedReview]);
    }

    echo json_encode(['success' => true]);
    exit();
}

if ($action === 'final_submit') {
    $attemptId = (int)($_POST['attempt_id'] ?? 0);

    // Verify attempt ownership
    $attStmt = $pdo->prepare("SELECT attempt_id FROM " . tbl('attempts') . " WHERE attempt_id = ? AND user_id = ?");
    $attStmt->execute([$attemptId, $userId]);
    if (!$attStmt->fetch()) {
        header('Location: dashboard.php?error=' . urlencode('Attempt not found.'));
        exit();
    }

    // Run scoring calculation
    calculate_and_save_score($attemptId);
    header('Location: quiz_result.php?attempt_id=' . $attemptId);
    exit();
}

header('Location: dashboard.php');
exit();
