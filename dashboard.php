<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Employee Quiz Hub & Dashboard
 */

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all published quizzes
$quizzesStmt = $pdo->prepare("
    SELECT q.*, 
           (SELECT COUNT(*) FROM " . tbl('questions') . " WHERE quiz_id = q.quiz_id) as question_count
    FROM " . tbl('quizzes') . " q
    WHERE q.is_published = 1
    ORDER BY q.start_time DESC
");
$quizzesStmt->execute();
$allQuizzes = $quizzesStmt->fetchAll();

// Fetch user attempts
$attemptsStmt = $pdo->prepare("
    SELECT * FROM " . tbl('attempts') . " 
    WHERE user_id = ?
    ORDER BY start_time DESC
");
$attemptsStmt->execute([$userId]);
$userAttemptsRaw = $attemptsStmt->fetchAll();

$userAttemptsByQuiz = [];
$totalScoreAccumulated = 0;
$completedCount = 0;
$passedCount = 0;

foreach ($userAttemptsRaw as $att) {
    // Keep most recent attempt per quiz
    if (!isset($userAttemptsByQuiz[$att['quiz_id']])) {
        $userAttemptsByQuiz[$att['quiz_id']] = $att;
    }
    if ($att['status'] === 'completed') {
        $completedCount++;
        $totalScoreAccumulated += $att['score_achieved'];
        
        // Pass calculation
        $pct = $att['total_marks'] > 0 ? ($att['score_achieved'] / $att['total_marks']) * 100 : 0;
        if ($pct >= 40) {
            $passedCount++;
        }
    }
}

$pageTitle = 'Employee Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fa-solid fa-graduation-cap" style="color: var(--bhel-gold);"></i> BHEL Vizag Quiz Dashboard</h2>
        <p>Welcome back, <strong><?= sanitize($_SESSION['full_name']) ?></strong> (Department: <?= sanitize($_SESSION['department']) ?>)</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-outline"><i class="fa-solid fa-rotate"></i> Refresh Page</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= sanitize($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($error) ?></div>
<?php endif; ?>

<!-- Employee Performance Summary Widgets -->
<div class="grid-4" style="margin-bottom: 30px;">
    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: var(--bhel-gold); border-color: rgba(255, 193, 7, 0.3);">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="stat-content">
            <h3><?= count($allQuizzes) ?></h3>
            <p>Total Quizzes Available</p>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--status-active); border-color: rgba(16, 185, 129, 0.3);">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-content">
            <h3><?= $completedCount ?></h3>
            <p>Completed Quizzes</p>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(0, 210, 255, 0.1); color: var(--bhel-blue-accent); border-color: rgba(0, 210, 255, 0.3);">
            <i class="fa-solid fa-trophy"></i>
        </div>
        <div class="stat-content">
            <h3><?= $passedCount ?></h3>
            <p>Quizzes Passed</p>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: var(--status-review); border-color: rgba(139, 92, 246, 0.3);">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($totalScoreAccumulated, 1) ?></h3>
            <p>Total Marks Scored</p>
        </div>
    </div>
</div>

<!-- Active Quizzes Section -->
<div style="margin-bottom: 40px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
        <h3 style="font-size: 18px; color: #FFF; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-bolt" style="color: var(--bhel-blue-accent);"></i> Available & Live Quizzes
        </h3>
    </div>

    <div class="grid-2">
        <?php 
        $activeCount = 0;
        foreach ($allQuizzes as $q):
            $qStatus = get_quiz_status($q['start_time'], $q['end_time'], $q['is_published']);
            $existingAttempt = $userAttemptsByQuiz[$q['quiz_id']] ?? null;
        ?>
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid var(--bhel-blue-accent);">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                        <span class="badge <?= $qStatus['badge'] ?>"><?= $qStatus['label'] ?></span>
                        <span style="font-size: 12px; color: var(--text-muted);">
                            <i class="fa-solid fa-clock"></i> <?= (int)$q['duration_minutes'] ?> Mins
                        </span>
                    </div>

                    <h4 style="font-size: 18px; font-weight: 700; color: #FFF; margin-bottom: 6px;">
                        <?= sanitize($q['title_en']) ?>
                    </h4>
                    <?php if (!empty($q['title_hi']) || !empty($q['title_te'])): ?>
                        <div style="font-size: 13px; color: var(--bhel-gold); margin-bottom: 10px;">
                            <?= sanitize($q['title_hi']) ?> | <?= sanitize($q['title_te']) ?>
                        </div>
                    <?php endif; ?>

                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 15px;">
                        <?= sanitize($q['description_en']) ?>
                    </p>

                    <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px; font-size: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px;">
                        <div><i class="fa-solid fa-circle-question" style="color: var(--bhel-blue-accent);"></i> Questions: <strong><?= $q['question_count'] ?></strong></div>
                        <div><i class="fa-solid fa-award" style="color: var(--bhel-gold);"></i> Marks/Q: <strong>+<?= number_format($q['marks_per_question'], 1) ?></strong></div>
                        <div><i class="fa-solid fa-triangle-exclamation" style="color: var(--status-danger);"></i> Negative Marks: <strong style="color: #FCA5A5;">-<?= number_format($q['negative_marks'], 2) ?></strong></div>
                        <div><i class="fa-solid fa-calendar-check" style="color: var(--status-active);"></i> Window: <strong><?= date('d M H:i', strtotime($q['start_time'])) ?> to <?= date('d M H:i', strtotime($q['end_time'])) ?></strong></div>
                    </div>
                </div>

                <div>
                    <?php if ($existingAttempt && $existingAttempt['status'] === 'completed'): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(16, 185, 129, 0.1); border: 1px solid var(--status-active); padding: 10px 14px; border-radius: 8px; margin-bottom: 10px;">
                            <span style="font-size: 13px; color: #6EE7B7;"><i class="fa-solid fa-check-circle"></i> Quiz Completed</span>
                            <span style="font-weight: 700; color: var(--bhel-gold);"><?= $existingAttempt['score_achieved'] ?> / <?= $existingAttempt['total_marks'] ?> Marks</span>
                        </div>
                        <a href="quiz_result.php?attempt_id=<?= $existingAttempt['attempt_id'] ?>" class="btn btn-outline" style="width: 100%;">
                            <i class="fa-solid fa-file-invoice"></i> View Detailed Scorecard & Answers
                        </a>
                    <?php elseif ($existingAttempt && $existingAttempt['status'] === 'in_progress'): ?>
                        <a href="quiz.php?quiz_id=<?= $q['quiz_id'] ?>" class="btn btn-primary" style="width: 100%;">
                            <i class="fa-solid fa-play"></i> Resume Quiz In Progress &rarr;
                        </a>
                    <?php elseif ($qStatus['can_start']): ?>
                        <a href="quiz.php?quiz_id=<?= $q['quiz_id'] ?>" class="btn btn-accent" style="width: 100%;">
                            <i class="fa-solid fa-pen-to-square"></i> Start Quiz Now &rarr;
                        </a>
                    <?php else: ?>
                        <button disabled class="btn btn-outline" style="width: 100%; opacity: 0.5; cursor: not-allowed;">
                            <i class="fa-solid fa-lock"></i> Quiz Not Accessible (<?= $qStatus['label'] ?>)
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
