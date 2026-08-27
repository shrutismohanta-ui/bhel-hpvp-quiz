<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Core Utility Functions & Scoring Engine
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/**
 * Sanitize string output for safety against XSS
 */
function sanitize($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is currently logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if logged in user is an Administrator
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require user login or redirect to login page
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . get_base_url() . 'index.php?error=' . urlencode('Please log in to access this page.'));
        exit();
    }
}

/**
 * Require admin privileges or redirect
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: ' . get_base_url() . 'dashboard.php?error=' . urlencode('Access restricted to Administrators only.'));
        exit();
    }
}

/**
 * Get base URL of application
 */
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // Normalize path to root of bhel-quiz
    if (strpos($scriptDir, '/admin') !== false) {
        $scriptDir = str_replace('/admin', '', $scriptDir);
    }
    $base = rtrim($scriptDir, '/\\') . '/';
    return $base;
}

/**
 * Calculate quiz status based on start/end dates and publish status
 */
function get_quiz_status($startTime, $endTime, $isPublished = 1) {
    if (!$isPublished) {
        return [
            'status' => 'draft',
            'label' => 'Draft',
            'badge' => 'badge-warning',
            'can_start' => false
        ];
    }

    $now = new DateTime();
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);

    if ($now < $start) {
        return [
            'status' => 'upcoming',
            'label' => 'Upcoming',
            'badge' => 'badge-info',
            'can_start' => false
        ];
    } elseif ($now >= $start && $now <= $end) {
        return [
            'status' => 'active',
            'label' => 'Live Now',
            'badge' => 'badge-success',
            'can_start' => true
        ];
    } else {
        return [
            'status' => 'expired',
            'label' => 'Closed',
            'badge' => 'badge-danger',
            'can_start' => false
        ];
    }
}

/**
 * Check if a user is excluded from taking a quiz based on Staff No or Department
 * @param string $staffNo User's Staff No
 * @param string $dept User's Department
 * @param string $excludedStaffStr Comma/newline separated excluded staff numbers
 * @param string $excludedDeptsStr Comma/newline separated excluded department names
 * @return array ['is_excluded' => bool, 'reason' => string]
 */
function is_user_excluded_from_quiz($staffNo, $dept, $excludedStaffStr, $excludedDeptsStr) {
    $staffNoClean = strtoupper(trim($staffNo ?? ''));
    $deptClean = strtolower(trim($dept ?? ''));

    // Check Staff No exclusion
    if (!empty($excludedStaffStr)) {
        $exStaffList = array_map(function($item) {
            return strtoupper(trim($item));
        }, preg_split('/[\r\n,]+/', $excludedStaffStr));
        $exStaffList = array_values(array_filter($exStaffList));

        if (in_array($staffNoClean, $exStaffList)) {
            return [
                'is_excluded' => true,
                'reason' => "Staff No ({$staffNoClean}) is in the exclusion list for this quiz."
            ];
        }
    }

    // Check Department exclusion
    if (!empty($excludedDeptsStr)) {
        $exDeptList = array_map(function($item) {
            return strtolower(trim($item));
        }, preg_split('/[\r\n,]+/', $excludedDeptsStr));
        $exDeptList = array_values(array_filter($exDeptList));

        if (!empty($deptClean) && in_array($deptClean, $exDeptList)) {
            return [
                'is_excluded' => true,
                'reason' => "Your Department ({$dept}) is excluded from participating in this quiz."
            ];
        }
    }

    return [
        'is_excluded' => false,
        'reason' => ''
    ];
}

/**
 * Format datetime string into friendly display string
 */
function format_datetime($datetimeStr) {
    if (!$datetimeStr) return 'N/A';
    $dt = new DateTime($datetimeStr);
    return $dt->format('d M Y, h:i A');
}

/**
 * Calculate Score & Update Attempt in Database (Scoring Engine)
 * Applies positive marks per correct answer and negative marks per wrong answer.
 */
function calculate_and_save_score($attemptId) {
    $pdo = getDBConnection();
    if (!$pdo) return false;

    // Fetch attempt details along with quiz settings
    $stmt = $pdo->prepare("
        SELECT a.*, q.marks_per_question, q.negative_marks, q.pass_percentage 
        FROM " . tbl('attempts') . " a
        JOIN " . tbl('quizzes') . " q ON a.quiz_id = q.quiz_id
        WHERE a.attempt_id = ?
    ");
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch();

    if (!$attempt) return false;

    $marksPerQ = (float)$attempt['marks_per_question'];
    $negMarksPerQ = (float)$attempt['negative_marks'];

    // Fetch all questions for this quiz
    $qStmt = $pdo->prepare("
        SELECT question_id, correct_option 
        FROM " . tbl('questions') . " 
        WHERE quiz_id = ?
        ORDER BY question_num ASC
    ");
    $qStmt->execute([$attempt['quiz_id']]);
    $questions = $qStmt->fetchAll();

    // Fetch user responses for this attempt
    $rStmt = $pdo->prepare("
        SELECT question_id, selected_option 
        FROM " . tbl('attempt_responses') . " 
        WHERE attempt_id = ?
    ");
    $rStmt->execute([$attemptId]);
    $responsesRaw = $rStmt->fetchAll();

    $userResponses = [];
    foreach ($responsesRaw as $r) {
        $userResponses[$r['question_id']] = $r['selected_option'];
    }

    $totalQuestions = count($questions);
    $totalPossibleMarks = $totalQuestions * $marksPerQ;
    $correctCount = 0;
    $wrongCount = 0;
    $unattemptedCount = 0;
    $totalScoreAchieved = 0.00;

    // Process each question
    foreach ($questions as $q) {
        $qId = $q['question_id'];
        $correctOpt = (int)$q['correct_option'];
        $userOpt = isset($userResponses[$qId]) && $userResponses[$qId] !== null ? (int)$userResponses[$qId] : null;

        $isCorrect = null;
        $marksAwarded = 0.00;

        if ($userOpt === null || $userOpt === 0) {
            // Unattempted
            $unattemptedCount++;
            $isCorrect = null;
            $marksAwarded = 0.00;
        } elseif ($userOpt === $correctOpt) {
            // Correct Answer
            $correctCount++;
            $isCorrect = 1;
            $marksAwarded = $marksPerQ;
            $totalScoreAchieved += $marksPerQ;
        } else {
            // Wrong Answer (Apply negative marking penalty)
            $wrongCount++;
            $isCorrect = 0;
            $marksAwarded = -$negMarksPerQ;
            $totalScoreAchieved -= $negMarksPerQ;
        }

        // Update response detail in DB
        $upResp = $pdo->prepare("
            UPDATE " . tbl('attempt_responses') . " 
            SET is_correct = ?, marks_awarded = ?
            WHERE attempt_id = ? AND question_id = ?
        ");
        $upResp->execute([$isCorrect, $marksAwarded, $attemptId, $qId]);
    }

    // Ensure score does not go below 0 if required (or keep net score)
    $finalScore = max(0.00, round($totalScoreAchieved, 2));
    $submitTime = date('Y-m-d H:i:s');

    // Update attempt header
    $upAttempt = $pdo->prepare("
        UPDATE " . tbl('attempts') . " 
        SET submit_time = ?,
            score_achieved = ?,
            total_marks = ?,
            total_questions = ?,
            correct_answers = ?,
            wrong_answers = ?,
            unattempted = ?,
            status = 'completed'
        WHERE attempt_id = ?
    ");
    $upAttempt->execute([
        $submitTime,
        $finalScore,
        $totalPossibleMarks,
        $totalQuestions,
        $correctCount,
        $wrongCount,
        $unattemptedCount,
        $attemptId
    ]);

    return [
        'attempt_id' => $attemptId,
        'final_score' => $finalScore,
        'total_possible' => $totalPossibleMarks,
        'correct_count' => $correctCount,
        'wrong_count' => $wrongCount,
        'unattempted_count' => $unattemptedCount,
        'negative_penalty' => round($wrongCount * $negMarksPerQ, 2),
        'pass_percentage' => (float)$attempt['pass_percentage'],
        'percentage' => $totalPossibleMarks > 0 ? round(($finalScore / $totalPossibleMarks) * 100, 2) : 0
    ];
}
