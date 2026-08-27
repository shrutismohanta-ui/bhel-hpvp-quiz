<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Quiz Results & Scorecard Detailed View
 */

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = getDBConnection();
$attemptId = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;
$userId = $_SESSION['user_id'];

if ($attemptId <= 0) {
    header('Location: dashboard.php?error=' . urlencode('Invalid attempt ID specified.'));
    exit();
}

// Fetch Attempt Details
$stmt = $pdo->prepare("
    SELECT a.*, q.title_en, q.title_hi, q.title_te, q.languages, q.marks_per_question, q.negative_marks, q.pass_percentage, u.full_name, u.staff_no 
    FROM " . tbl('attempts') . " a
    JOIN " . tbl('quizzes') . " q ON a.quiz_id = q.quiz_id
    JOIN " . tbl('users') . " u ON a.user_id = u.user_id
    WHERE a.attempt_id = ?
");
$stmt->execute([$attemptId]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header('Location: dashboard.php?error=' . urlencode('Quiz attempt record not found.'));
    exit();
}

// Ensure normal employee can only view their own attempt unless admin
if (!is_admin() && (int)$attempt['user_id'] !== (int)$userId) {
    header('Location: dashboard.php?error=' . urlencode('Access denied to view this result scorecard.'));
    exit();
}

// Ensure attempt is calculated
if ($attempt['status'] === 'in_progress') {
    calculate_and_save_score($attemptId);
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch();
}

// Fetch Question Breakdown & Responses
$qStmt = $pdo->prepare("
    SELECT q.*, r.selected_option, r.is_correct, r.marks_awarded 
    FROM " . tbl('questions') . " q
    LEFT JOIN " . tbl('attempt_responses') . " r ON q.question_id = r.question_id AND r.attempt_id = ?
    WHERE q.quiz_id = ?
    ORDER BY q.question_num ASC
");
$qStmt->execute([$attemptId, $attempt['quiz_id']]);
$questions = $qStmt->fetchAll();

$pct = $attempt['total_marks'] > 0 ? round(($attempt['score_achieved'] / $attempt['total_marks']) * 100, 1) : 0;
$isPassed = $pct >= (float)$attempt['pass_percentage'];

$enabledLangs = explode(',', $attempt['languages'] ?? 'en');
$enabledLangs = array_values(array_filter(array_map('trim', $enabledLangs)));
if (empty($enabledLangs)) $enabledLangs = ['en'];
$primaryLang = $enabledLangs[0];

// Select title according to primary language
$primaryTitle = '';
if ($primaryLang === 'hi' && !empty($attempt['title_hi'])) {
    $primaryTitle = $attempt['title_hi'];
} elseif ($primaryLang === 'te' && !empty($attempt['title_te'])) {
    $primaryTitle = $attempt['title_te'];
} elseif (!empty($attempt['title_en'])) {
    $primaryTitle = $attempt['title_en'];
} else {
    $primaryTitle = !empty($attempt['title_hi']) ? $attempt['title_hi'] : (!empty($attempt['title_te']) ? $attempt['title_te'] : $attempt['title_en']);
}

$pageTitle = 'Scorecard - ' . $primaryTitle;
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fa-solid fa-square-poll-vertical" style="color: var(--bhel-gold);"></i> Quiz Performance Scorecard</h2>
        <p>Participant: <strong><?= sanitize($attempt['full_name'] ?? $_SESSION['full_name']) ?></strong> (Staff No: <?= sanitize($attempt['staff_no'] ?? $_SESSION['staff_no']) ?>)</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Return to Dashboard</a>
    </div>
</div>

<!-- Score Header Card -->
<div class="scorecard-header">
    <span class="badge <?= $isPassed ? 'badge-success' : 'badge-danger' ?>" style="font-size: 14px; padding: 6px 16px; margin-bottom: 15px;">
        <i class="fa-solid <?= $isPassed ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
        <?= $isPassed ? 'QUIZ PASSED' : 'NEEDS IMPROVEMENT' ?> (Min Pass: <?= (float)$attempt['pass_percentage'] ?>%)
    </span>

    <h2 style="font-size: 24px; color: #FFF; margin-bottom: 5px;"><?= sanitize($primaryTitle) ?></h2>
    
    <div class="score-circle">
        <div class="score-num"><?= number_format($attempt['score_achieved'], 2) ?></div>
        <div class="score-total">out of <?= number_format($attempt['total_marks'], 2) ?></div>
    </div>

    <div style="font-size: 18px; font-weight: 700; color: var(--bhel-blue-accent);">
        Overall Accuracy: <?= $pct ?>%
    </div>
</div>

<!-- Stats Breakdown Grid -->
<div class="grid-4" style="margin-bottom: 35px;">
    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--status-active);">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-content">
            <h3><?= (int)$attempt['correct_answers'] ?></h3>
            <p>Correct Answers (+<?= number_format($attempt['marks_per_question'], 1) ?> ea)</p>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--status-danger);">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <div class="stat-content">
            <h3><?= (int)$attempt['wrong_answers'] ?></h3>
            <p>Wrong Answers (-<?= number_format($attempt['negative_marks'], 2) ?> ea)</p>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--status-warning);">
            <i class="fa-solid fa-circle-minus"></i>
        </div>
        <div class="stat-content">
            <h3><?= (int)$attempt['unattempted'] ?></h3>
            <p>Unattempted (0 Marks)</p>
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #FCA5A5;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-content">
            <h3>-<?= number_format($attempt['wrong_answers'] * $attempt['negative_marks'], 2) ?></h3>
            <p>Total Negative Penalty</p>
        </div>
    </div>
</div>

<!-- Detailed Question Answer Review Section -->
<div style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-size: 18px; color: #FFF; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-list-ol" style="color: var(--bhel-blue-accent);"></i> Detailed Answer Sheet Review
        </h3>

        <!-- Language switcher for review (only if multiple languages enabled for quiz) -->
        <?php if (count($enabledLangs) > 1): ?>
            <div class="lang-switcher-bar" style="margin-bottom: 0;">
                <?php 
                $langLabels = ['en' => 'English', 'hi' => 'हिन्दी', 'te' => 'తెలుగు'];
                foreach ($enabledLangs as $idx => $lCode): 
                ?>
                    <button type="button" class="lang-btn <?= $lCode === $primaryLang ? 'active' : '' ?>" onclick="switchReviewLang('<?= $lCode ?>', this)">
                        <i class="fa-solid fa-language"></i> <?= $langLabels[$lCode] ?? strtoupper($lCode) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($questions as $idx => $q): 
            $userOpt = $q['selected_option'] !== null ? (int)$q['selected_option'] : null;
            $correctOpt = (int)$q['correct_option'];
            $isCorrect = $q['is_correct'] === 1;
            $isWrong = $q['is_correct'] === 0;
            $isUnans = $userOpt === null;

            $cardClass = $isCorrect ? 'correct' : ($isWrong ? 'wrong' : 'unattempted');
        ?>
            <div class="card review-q-card <?= $cardClass ?>">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <span style="font-weight: 700; font-size: 15px; color: var(--bhel-blue-accent);">
                        Question <?= $idx + 1 ?> of <?= count($questions) ?>
                    </span>
                    <div>
                        <?php if ($isCorrect): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Correct (+<?= number_format($attempt['marks_per_question'], 1) ?>)</span>
                        <?php elseif ($isWrong): ?>
                            <span class="badge badge-danger"><i class="fa-solid fa-xmark"></i> Incorrect (-<?= number_format($attempt['negative_marks'], 2) ?>)</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><i class="fa-solid fa-minus"></i> Unattempted (0.0)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Multilingual Question Text -->
                <?php foreach ($enabledLangs as $lCode): 
                    $qText = !empty($q["question_{$lCode}"]) ? $q["question_{$lCode}"] : (!empty($q['question_en']) ? $q['question_en'] : (!empty($q['question_hi']) ? $q['question_hi'] : $q['question_te']));
                    $displayStyle = ($lCode === $primaryLang) ? 'display: block;' : 'display: none;';
                ?>
                    <h4 class="rev-lang rev-<?= $lCode ?>" style="font-size: 16px; font-weight: 600; color: #FFF; margin-bottom: 15px; <?= $displayStyle ?>">
                        <?= sanitize($qText) ?>
                    </h4>
                <?php endforeach; ?>

                <!-- Options Review -->
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php for ($i = 1; $i <= 4; $i++): 
                        $optLetter = chr(64 + $i);
                        $isUserChoice = ($userOpt === $i);
                        $isRightChoice = ($correctOpt === $i);

                        $optClass = '';
                        if ($isUserChoice && $isRightChoice) {
                            $optClass = 'user-picked-correct';
                        } elseif ($isUserChoice && !$isRightChoice) {
                            $optClass = 'user-picked-wrong';
                        } elseif ($isRightChoice) {
                            $optClass = 'correct-answer-highlight';
                        }
                    ?>
                        <div class="review-opt <?= $optClass ?>" style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?= chr(64 + $i) ?>.</strong>
                                <?php foreach ($enabledLangs as $lCode): 
                                    $optText = !empty($q["option_{$i}_{$lCode}"]) ? $q["option_{$i}_{$lCode}"] : (!empty($q["option_{$i}_en"]) ? $q["option_{$i}_en"] : (!empty($q["option_{$i}_hi"]) ? $q["option_{$i}_hi"] : $q["option_{$i}_te"]));
                                    $displayStyle = ($lCode === $primaryLang) ? 'display: inline;' : 'display: none;';
                                ?>
                                    <span class="rev-lang rev-<?= $lCode ?>" style="<?= $displayStyle ?>"><?= sanitize($optText) ?></span>
                                <?php endforeach; ?>
                            </div>

                            <div>
                                <?php if ($isUserChoice && $isRightChoice): ?>
                                    <span style="font-size: 11px; font-weight: 700; color: #6EE7B7;"><i class="fa-solid fa-check"></i> Your Choice (Correct)</span>
                                <?php elseif ($isUserChoice): ?>
                                    <span style="font-size: 11px; font-weight: 700; color: #FCA5A5;"><i class="fa-solid fa-xmark"></i> Your Choice (Incorrect)</span>
                                <?php elseif ($isRightChoice): ?>
                                    <span style="font-size: 11px; font-weight: 700; color: #7DD3FC;"><i class="fa-solid fa-circle-check"></i> Correct Answer</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Explanation Box -->
                <?php 
                $hasExp = false;
                foreach ($enabledLangs as $lCode) {
                    if (!empty($q["explanation_{$lCode}"])) { $hasExp = true; break; }
                }
                if (!$hasExp && !empty($q['explanation_en'])) { $hasExp = true; }
                ?>
                <?php if ($hasExp): ?>
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px dashed var(--border-light); padding: 12px 15px; border-radius: 8px; margin-top: 15px; font-size: 13px; color: var(--text-secondary);">
                        <strong style="color: var(--bhel-gold);"><i class="fa-solid fa-lightbulb"></i> Explanation:</strong>
                        <?php foreach ($enabledLangs as $lCode): 
                            $expText = !empty($q["explanation_{$lCode}"]) ? $q["explanation_{$lCode}"] : (!empty($q['explanation_en']) ? $q['explanation_en'] : (!empty($q['explanation_hi']) ? $q['explanation_hi'] : $q['explanation_te']));
                            $displayStyle = ($lCode === $primaryLang) ? 'display: inline;' : 'display: none;';
                        ?>
                            <span class="rev-lang rev-<?= $lCode ?>" style="<?= $displayStyle ?>"><?= sanitize($expText) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function switchReviewLang(lang, btn) {
    document.querySelectorAll('.rev-lang').forEach(el => {
        if (el.classList.contains('rev-' + lang)) {
            el.style.display = el.tagName === 'H4' ? 'block' : 'inline';
        } else {
            el.style.display = 'none';
        }
    });
    if (btn) {
        document.querySelectorAll('.lang-switcher-bar .lang-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
