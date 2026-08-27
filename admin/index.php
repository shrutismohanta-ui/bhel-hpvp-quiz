<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Administrator Dashboard & Quiz Manager
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = getDBConnection();
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Delete Quiz
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['quiz_id'])) {
    $delId = (int)$_GET['quiz_id'];
    $delStmt = $pdo->prepare("DELETE FROM " . tbl('quizzes') . " WHERE quiz_id = ?");
    $delStmt->execute([$delId]);
    header('Location: index.php?message=' . urlencode('Quiz deleted successfully.'));
    exit();
}

// Handle Toggle Publish
if (isset($_GET['action']) && $_GET['action'] === 'toggle_publish' && isset($_GET['quiz_id'])) {
    $qId = (int)$_GET['quiz_id'];
    $togStmt = $pdo->prepare("UPDATE " . tbl('quizzes') . " SET is_published = NOT is_published WHERE quiz_id = ?");
    $togStmt->execute([$qId]);
    header('Location: index.php?message=' . urlencode('Quiz publication status updated.'));
    exit();
}

// Fetch Admin Stats
$totalQuizzes = $pdo->query("SELECT COUNT(*) FROM " . tbl('quizzes'))->fetchColumn();
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM " . tbl('users') . " WHERE role = 'employee'")->fetchColumn();
$totalAttempts = $pdo->query("SELECT COUNT(*) FROM " . tbl('attempts') . " WHERE status = 'completed'")->fetchColumn();
$avgScore = $pdo->query("SELECT AVG(score_achieved) FROM " . tbl('attempts') . " WHERE status = 'completed'")->fetchColumn();

// Fetch All Quizzes with counts
$quizzesStmt = $pdo->query("
    SELECT q.*, 
           (SELECT COUNT(*) FROM " . tbl('questions') . " WHERE quiz_id = q.quiz_id) as question_count,
           (SELECT COUNT(*) FROM " . tbl('attempts') . " WHERE quiz_id = q.quiz_id AND status = 'completed') as participant_count
    FROM " . tbl('quizzes') . " q
    ORDER BY q.created_at DESC
");
$quizzes = $quizzesStmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fa-solid fa-gauge-high" style="color: var(--bhel-gold);"></i> Administrator Portal</h2>
        <p>Quiz Setup, Time Scheduling, Trilingual Content & Analytics</p>
    </div>
    <div>
        <a href="quiz_edit.php" class="btn btn-primary">
            <i class="fa-solid fa-plus-circle"></i> Create New Quiz
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= sanitize($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($error) ?></div>
<?php endif; ?>

<!-- Summary Stat Cards -->
<div class="grid-3" style="margin-bottom: 30px;">
    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(0, 210, 255, 0.1); color: var(--bhel-blue-accent);">
            <i class="fa-solid fa-layer-group"></i>
        </div>
        <div class="stat-content">
            <h3><?= $totalQuizzes ?></h3>
            <p>Total Quizzes Created</p>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: var(--bhel-gold);">
            <i class="fa-solid fa-clipboard-check"></i>
        </div>
        <div class="stat-content">
            <h3><?= $totalAttempts ?></h3>
            <p>Completed Quiz Attempts</p>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: var(--status-review);">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($avgScore ?? 0, 1) ?></h3>
            <p>Average Score Across Quizzes</p>
        </div>
    </div>
</div>

<!-- Manage Quizzes Table Card -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 18px; color: #FFF; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-sliders" style="color: var(--bhel-blue-accent);"></i> Quiz Management Center
        </h3>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Quiz Title (EN / HI / TE)</th>
                    <th>Schedule Window</th>
                    <th>Duration & Marks</th>
                    <th>Negative Marking</th>
                    <th>Status</th>
                    <th>Questions</th>
                    <th>Attempts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($quizzes)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            No quizzes configured yet. Click "Create New Quiz" to start.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($quizzes as $q): 
                        $status = get_quiz_status($q['start_time'], $q['end_time'], $q['is_published']);
                        $qLangs = explode(',', $q['languages'] ?? 'en');
                        $qCats = explode(',', $q['target_categories'] ?? 'executive,supervisor,workman');
                    ?>
                        <tr>
                            <td>
                                <strong style="color: #FFF; font-size: 15px;"><?= sanitize(!empty($q['title_en']) ? $q['title_en'] : ($q['title_hi'] ?: $q['title_te'])) ?></strong>
                                <?php if (!empty($q['title_hi']) || !empty($q['title_te'])): ?>
                                    <div style="font-size: 12px; color: var(--bhel-gold); margin-top: 2px;">
                                        <?= sanitize($q['title_hi']) ?> <?= (!empty($q['title_hi']) && !empty($q['title_te'])) ? '|' : '' ?> <?= sanitize($q['title_te']) ?>
                                    </div>
                                <?php endif; ?>
                                <div style="display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap;">
                                    <span style="font-size: 10px; padding: 2px 6px; border-radius: 4px; background: rgba(0, 210, 255, 0.15); color: var(--bhel-blue-accent); border: 1px solid rgba(0, 210, 255, 0.3);">
                                        <i class="fa-solid fa-language"></i> <?= strtoupper(implode(', ', $qLangs)) ?>
                                    </span>
                                    <span style="font-size: 10px; padding: 2px 6px; border-radius: 4px; background: rgba(255, 193, 7, 0.15); color: var(--bhel-gold); border: 1px solid rgba(255, 193, 7, 0.3);">
                                        <i class="fa-solid fa-users"></i> <?= implode(', ', array_map('ucfirst', $qCats)) ?>
                                    </span>
                                </div>
                            </td>
                            <td style="font-size: 12px;">
                                <div><strong>From:</strong> <?= format_datetime($q['start_time']) ?></div>
                                <div><strong>To:</strong> <?= format_datetime($q['end_time']) ?></div>
                            </td>
                            <td>
                                <div><strong><?= $q['duration_minutes'] ?></strong> Mins</div>
                                <div style="font-size: 12px; color: var(--text-secondary);">+<?= number_format($q['marks_per_question'], 1) ?> per Q</div>
                            </td>
                            <td>
                                <span style="color: #FCA5A5; font-weight: 600;">-<?= number_format($q['negative_marks'], 2) ?></span>
                            </td>
                            <td>
                                <a href="index.php?action=toggle_publish&quiz_id=<?= $q['quiz_id'] ?>" class="badge <?= $status['badge'] ?>" title="Click to toggle publish status">
                                    <?= $status['label'] ?>
                                </a>
                            </td>
                            <td>
                                <a href="questions.php?quiz_id=<?= $q['quiz_id'] ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 12px;">
                                    <i class="fa-solid fa-list"></i> <?= $q['question_count'] ?> Questions
                                </a>
                            </td>
                            <td>
                                <a href="results.php?quiz_id=<?= $q['quiz_id'] ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 12px; color: var(--bhel-gold); border-color: rgba(255, 193, 7, 0.4);">
                                    <i class="fa-solid fa-chart-column"></i> <?= $q['participant_count'] ?> Stats
                                </a>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <a href="quiz_edit.php?quiz_id=<?= $q['quiz_id'] ?>" class="btn btn-outline" style="padding: 6px 10px; font-size: 12px;" title="Edit Quiz Settings">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="index.php?action=delete&quiz_id=<?= $q['quiz_id'] ?>" onclick="return confirm('Are you sure you want to delete this quiz and all its questions and employee attempt records?');" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px;" title="Delete Quiz">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
