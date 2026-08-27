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
$allQuizzesRaw = $quizzesStmt->fetchAll();

$userCategory = $_SESSION['employee_category'] ?? 'workman';

// Filter quizzes by employee category (Admins see all)
$allQuizzes = [];
foreach ($allQuizzesRaw as $qItem) {
    if (is_admin()) {
        $allQuizzes[] = $qItem;
    } else {
        $targetCats = explode(',', $qItem['target_categories'] ?? 'executive,supervisor,workman');
        if (in_array($userCategory, $targetCats)) {
            $allQuizzes[] = $qItem;
        }
    }
}

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
        <p>Welcome back, <strong><?= sanitize($_SESSION['full_name']) ?></strong> (Dept: <strong><?= sanitize($_SESSION['department']) ?></strong> | Category: <span class="badge badge-info"><?= ucfirst(sanitize($userCategory)) ?></span>)</p>
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
            <p>Assigned Quizzes</p>
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
            <i class="fa-solid fa-bolt" style="color: var(--bhel-blue-accent);"></i> Available & Live Quizzes for <?= ucfirst(sanitize($userCategory)) ?> Category
        </h3>
    </div>

    <?php if (empty($allQuizzes)): ?>
        <div class="card" style="text-align: center; padding: 40px; color: var(--text-muted);">
            <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 15px; color: var(--bhel-gold);"></i>
            <p style="font-size: 16px;">No quizzes currently assigned for your employee category (<strong><?= ucfirst(sanitize($userCategory)) ?></strong>).</p>
        </div>
    <?php else: ?>
        <div class="grid-2">
            <?php 
            $userStaffNo = $_SESSION['staff_no'] ?? '';
            $userDept = $_SESSION['department'] ?? '';

            foreach ($allQuizzes as $q):
                $qStatus = get_quiz_status($q['start_time'], $q['end_time'], $q['is_published']);
                $existingAttempt = $userAttemptsByQuiz[$q['quiz_id']] ?? null;
                $qLangs = explode(',', $q['languages'] ?? 'en');
                $qCats = explode(',', $q['target_categories'] ?? 'executive,supervisor,workman');
                $primaryTitle = !empty($q['title_en']) ? $q['title_en'] : (!empty($q['title_hi']) ? $q['title_hi'] : $q['title_te']);
                $primaryDesc = !empty($q['description_en']) ? $q['description_en'] : (!empty($q['description_hi']) ? $q['description_hi'] : $q['description_te']);

                // Check exclusion rules (Admins are exempt)
                $exCheck = is_admin() ? ['is_excluded' => false, 'reason' => ''] : is_user_excluded_from_quiz($userStaffNo, $userDept, $q['excluded_staff_nos'] ?? '', $q['excluded_departments'] ?? '');
            ?>
                <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid <?= $exCheck['is_excluded'] ? 'var(--status-danger)' : 'var(--bhel-blue-accent)' ?>;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <span class="badge <?= $qStatus['badge'] ?>"><?= $qStatus['label'] ?></span>
                                <?php if ($exCheck['is_excluded']): ?>
                                    <span class="badge badge-danger" title="<?= sanitize($exCheck['reason']) ?>"><i class="fa-solid fa-user-slash"></i> Excluded</span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: rgba(0, 210, 255, 0.15); color: var(--bhel-blue-accent); border: 1px solid rgba(0, 210, 255, 0.3);">
                                    <i class="fa-solid fa-language"></i> <?= strtoupper(implode(', ', $qLangs)) ?>
                                </span>
                                <span style="font-size: 12px; color: var(--text-muted);">
                                    <i class="fa-solid fa-clock"></i> <?= (int)$q['duration_minutes'] ?> Mins
                                </span>
                            </div>
                        </div>

                        <h4 style="font-size: 18px; font-weight: 700; color: #FFF; margin-bottom: 6px;">
                            <?= sanitize($primaryTitle) ?>
                        </h4>
                        <?php 
                        $subTitles = [];
                        if (in_array('hi', $qLangs) && !empty($q['title_hi']) && $primaryTitle !== $q['title_hi']) $subTitles[] = $q['title_hi'];
                        if (in_array('te', $qLangs) && !empty($q['title_te']) && $primaryTitle !== $q['title_te']) $subTitles[] = $q['title_te'];
                        if (!empty($subTitles)): 
                        ?>
                            <div style="font-size: 13px; color: var(--bhel-gold); margin-bottom: 10px;">
                                <?= sanitize(implode(' | ', $subTitles)) ?>
                            </div>
                        <?php endif; ?>

                        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 15px;">
                            <?= sanitize($primaryDesc) ?>
                        </p>

                        <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px; font-size: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px;">
                            <div><i class="fa-solid fa-circle-question" style="color: var(--bhel-blue-accent);"></i> Questions: <strong><?= $q['question_count'] ?></strong></div>
                            <div><i class="fa-solid fa-award" style="color: var(--bhel-gold);"></i> Marks/Q: <strong>+<?= number_format($q['marks_per_question'], 1) ?></strong></div>
                            <div><i class="fa-solid fa-triangle-exclamation" style="color: var(--status-danger);"></i> Negative Marks: <strong style="color: #FCA5A5;">-<?= number_format($q['negative_marks'], 2) ?></strong></div>
                            <div><i class="fa-solid fa-calendar-check" style="color: var(--status-active);"></i> Window: <strong><?= date('d M H:i', strtotime($q['start_time'])) ?> to <?= date('d M H:i', strtotime($q['end_time'])) ?></strong></div>
                        </div>
                    </div>

                <div>
                    <?php if ($exCheck['is_excluded']): ?>
                        <button disabled class="btn btn-outline" style="width: 100%; opacity: 0.6; cursor: not-allowed; border-color: rgba(239, 68, 68, 0.4); color: #FCA5A5;" title="<?= sanitize($exCheck['reason']) ?>">
                            <i class="fa-solid fa-user-slash"></i> Access Restricted (Excluded)
                        </button>
                    <?php elseif ($existingAttempt && $existingAttempt['status'] === 'completed'): ?>
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
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

