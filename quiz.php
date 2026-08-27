<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Interactive Examination Screen (1 Question / Screen)
 */

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

if ($quizId <= 0) {
    header('Location: dashboard.php?error=' . urlencode('Invalid Quiz ID specified.'));
    exit();
}

// Fetch Quiz details
$stmt = $pdo->prepare("SELECT * FROM " . tbl('quizzes') . " WHERE quiz_id = ? AND is_published = 1");
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: dashboard.php?error=' . urlencode('Requested Quiz does not exist or is unavailable.'));
    exit();
}

// Exclusion Rules Verification (Admins exempt)
$userStaffNo = $_SESSION['staff_no'] ?? '';
$userDept = $_SESSION['department'] ?? '';
$exCheck = is_admin() ? ['is_excluded' => false, 'reason' => ''] : is_user_excluded_from_quiz($userStaffNo, $userDept, $quiz['excluded_staff_nos'] ?? '', $quiz['excluded_departments'] ?? '');

if ($exCheck['is_excluded']) {
    header('Location: dashboard.php?error=' . urlencode('Access Restricted: ' . $exCheck['reason']));
    exit();
}

// Time Window Verification
$now = new DateTime();
$startTime = new DateTime($quiz['start_time']);
$endTime = new DateTime($quiz['end_time']);

if ($now < $startTime) {
    header('Location: dashboard.php?error=' . urlencode('This quiz is scheduled to open on ' . format_datetime($quiz['start_time']) . '. You cannot start early.'));
    exit();
}

if ($now > $endTime) {
    header('Location: dashboard.php?error=' . urlencode('This quiz access window closed on ' . format_datetime($quiz['end_time']) . '.'));
    exit();
}

// Check or Create Attempt
$attStmt = $pdo->prepare("
    SELECT * FROM " . tbl('attempts') . " 
    WHERE quiz_id = ? AND user_id = ? AND status = 'in_progress'
    ORDER BY start_time DESC LIMIT 1
");
$attStmt->execute([$quizId, $userId]);
$attempt = $attStmt->fetch();

if (!$attempt) {
    // Check if already completed
    $compStmt = $pdo->prepare("
        SELECT * FROM " . tbl('attempts') . " 
        WHERE quiz_id = ? AND user_id = ? AND status = 'completed'
        ORDER BY start_time DESC LIMIT 1
    ");
    $compStmt->execute([$quizId, $userId]);
    $compAttempt = $compStmt->fetch();

    if ($compAttempt) {
        header('Location: quiz_result.php?attempt_id=' . $compAttempt['attempt_id']);
        exit();
    }

    // Start New Attempt
    $startStr = date('Y-m-d H:i:s');
    $insAtt = $pdo->prepare("
        INSERT INTO " . tbl('attempts') . " (quiz_id, user_id, start_time, status) 
        VALUES (?, ?, ?, 'in_progress')
    ");
    $insAtt->execute([$quizId, $userId, $startStr]);
    $attemptId = $pdo->lastInsertId();

    $attStmt->execute([$quizId, $userId]);
    $attempt = $attStmt->fetch();
} else {
    $attemptId = $attempt['attempt_id'];
}

// Calculate Remaining Time in Seconds
$attemptStartTS = strtotime($attempt['start_time']);
$durationSecs = (int)$quiz['duration_minutes'] * 60;
$elapsedSecs = time() - $attemptStartTS;
$remainingSecs = max(0, $durationSecs - $elapsedSecs);

if ($remainingSecs <= 0) {
    // Timed out, calculate score & redirect to scorecard
    calculate_and_save_score($attemptId);
    header('Location: quiz_result.php?attempt_id=' . $attemptId . '&timeout=1');
    exit();
}

// Fetch Questions for this Quiz
$qStmt = $pdo->prepare("
    SELECT * FROM " . tbl('questions') . " 
    WHERE quiz_id = ? 
    ORDER BY question_num ASC
");
$qStmt->execute([$quizId]);
$questions = $qStmt->fetchAll();

if (empty($questions)) {
    header('Location: dashboard.php?error=' . urlencode('No questions have been configured for this quiz yet.'));
    exit();
}

// Fetch Existing Responses & Review Flags
$rStmt = $pdo->prepare("
    SELECT question_id, selected_option, is_marked_review 
    FROM " . tbl('attempt_responses') . " 
    WHERE attempt_id = ?
");
$rStmt->execute([$attemptId]);
$existingResponsesRaw = $rStmt->fetchAll();

$initialResponses = [];
$initialReviews = [];
foreach ($existingResponsesRaw as $r) {
    if ($r['selected_option'] !== null) {
        $initialResponses[$r['question_id']] = (int)$r['selected_option'];
    }
    $initialReviews[$r['question_id']] = (bool)$r['is_marked_review'];
}

$enabledLangs = explode(',', $quiz['languages'] ?? 'en');
$enabledLangs = array_values(array_filter(array_map('trim', $enabledLangs)));
if (empty($enabledLangs)) $enabledLangs = ['en'];
$primaryLang = $enabledLangs[0];

$primaryTitle = '';
if ($primaryLang === 'hi' && !empty($quiz['title_hi'])) {
    $primaryTitle = $quiz['title_hi'];
} elseif ($primaryLang === 'te' && !empty($quiz['title_te'])) {
    $primaryTitle = $quiz['title_te'];
} elseif (!empty($quiz['title_en'])) {
    $primaryTitle = $quiz['title_en'];
} else {
    $primaryTitle = !empty($quiz['title_hi']) ? $quiz['title_hi'] : (!empty($quiz['title_te']) ? $quiz['title_te'] : $quiz['title_en']);
}

$pageTitle = 'Quiz: ' . $primaryTitle;
require_once __DIR__ . '/includes/header.php';
?>

<!-- Timer & Header Bar -->
<div class="timer-card">
    <div>
        <h3 style="font-size: 18px; font-weight: 700; color: #FFF; margin-bottom: 4px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span><?= sanitize($primaryTitle) ?></span>
            <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(0, 210, 255, 0.2); color: var(--bhel-blue-accent); border: 1px solid rgba(0, 210, 255, 0.4); font-weight: 600;">
                <i class="fa-solid fa-language"></i> Language: <?= strtoupper(implode(', ', $enabledLangs)) ?>
            </span>
        </h3>
        <p style="font-size: 12px; color: var(--bhel-gold);">
            Marks/Q: +<?= number_format($quiz['marks_per_question'], 1) ?> | Negative Marks: -<?= number_format($quiz['negative_marks'], 2) ?>
        </p>
    </div>
    <div style="text-align: right;">
        <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Time Remaining</div>
        <div id="timer-display" class="timer-display">00:00</div>
    </div>
</div>

<!-- Multilingual Language Switcher Bar (Only if multiple languages enabled) -->
<?php if (count($enabledLangs) > 1): ?>
    <div class="lang-switcher-bar">
        <?php 
        $langLabels = ['en' => 'English', 'hi' => 'हिन्दी (Hindi)', 'te' => 'తెలుగు (Telugu)'];
        foreach ($enabledLangs as $idx => $lCode): 
        ?>
            <button type="button" class="lang-btn <?= $idx === 0 ? 'active' : '' ?>" data-lang="<?= $lCode ?>">
                <i class="fa-solid fa-language"></i> <?= $langLabels[$lCode] ?? strtoupper($lCode) ?>
            </button>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Main Quiz Layout (Question + Sidebar Palette) -->
<div class="quiz-layout">
    <!-- Left Column: Single Question Engine -->
    <div class="question-card">
        <div>
            <div class="question-header">
                <span id="current-q-badge" class="question-number-badge">Question 1 of <?= count($questions) ?></span>
                <button type="button" id="btn-mark-review" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                    <i class="fa-solid fa-bookmark" style="color: var(--status-review);"></i> Mark for Review
                </button>
            </div>

            <!-- Question Text -->
            <div id="q-text" class="question-text">
                Loading Question...
            </div>

            <!-- Options List A-D -->
            <div id="options-container" class="options-list">
                <!-- Dynamically populated via JS -->
            </div>
        </div>

        <!-- Question Footer Navigation -->
        <div class="question-footer-nav">
            <div>
                <button type="button" id="btn-prev" class="btn btn-outline">
                    <i class="fa-solid fa-chevron-left"></i> Previous
                </button>
                <button type="button" id="btn-clear-choice" class="btn btn-outline" style="color: var(--text-muted);">
                    <i class="fa-solid fa-eraser"></i> Clear Choice
                </button>
            </div>

            <div>
                <button type="button" id="btn-next" class="btn btn-primary">
                    Next Question <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button type="button" id="btn-submit-quiz" class="btn btn-success">
                    <i class="fa-solid fa-paper-plane"></i> Final Submit Quiz
                </button>
            </div>
        </div>
    </div>

    <!-- Right Column: Sidebar Question Palette -->
    <div class="palette-card">
        <div class="palette-header">
            <i class="fa-solid fa-grip" style="color: var(--bhel-blue-accent);"></i> Question Palette
        </div>

        <div id="palette-grid" class="palette-grid">
            <!-- Dynamically populated by quiz.js -->
        </div>

        <div class="palette-legend">
            <div class="legend-item">
                <div class="legend-dot" style="background: var(--status-active);"></div>
                <span>Attempted (<strong id="count-answered">0</strong>)</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background: var(--status-pending);"></div>
                <span>Unanswered / Pending (<strong id="count-pending">0</strong>)</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background: var(--status-review);"></div>
                <span>Marked for Review (<strong id="count-review">0</strong>)</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="border: 2px solid var(--bhel-blue-accent); background: transparent;"></div>
                <span>Current Active Question</span>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Final Submission -->
<form id="quiz-submit-form" method="POST" action="quiz_submit.php">
    <input type="hidden" name="action" value="final_submit">
    <input type="hidden" id="attempt-id-input" name="attempt_id" value="<?= $attemptId ?>">
</form>

<!-- Pass PHP Data to Client JS -->
<script>
const quizData = {
    quiz_id: <?= (int)$quiz['quiz_id'] ?>,
    attempt_id: <?= (int)$attemptId ?>,
    remaining_seconds: <?= (int)$remainingSecs ?>,
    enabled_languages: <?= json_encode($enabledLangs) ?>,
    questions: <?= json_encode($questions, JSON_UNESCAPED_UNICODE) ?>,
    initial_responses: <?= json_encode($initialResponses) ?>,
    initial_reviews: <?= json_encode($initialReviews) ?>
};
</script>
<script src="assets/js/quiz.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
