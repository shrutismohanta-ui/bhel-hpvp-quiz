<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Administrator Question & Answer Builder (Trilingual EN/HI/TE)
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = getDBConnection();
$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$editQuestionId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

if ($quizId <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid Quiz ID specified.'));
    exit();
}

// Fetch Quiz header
$quizStmt = $pdo->prepare("SELECT * FROM " . tbl('quizzes') . " WHERE quiz_id = ?");
$quizStmt->execute([$quizId]);
$quiz = $quizStmt->fetch();

if (!$quiz) {
    header('Location: index.php?error=' . urlencode('Quiz not found.'));
    exit();
}

// Handle Question Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['q_id'])) {
    $delQId = (int)$_GET['q_id'];
    $delStmt = $pdo->prepare("DELETE FROM " . tbl('questions') . " WHERE question_id = ? AND quiz_id = ?");
    $delStmt->execute([$delQId, $quizId]);
    header('Location: questions.php?quiz_id=' . $quizId . '&message=' . urlencode('Question deleted successfully.'));
    exit();
}

// Default empty question form values
$qData = [
    'question_en' => '', 'question_hi' => '', 'question_te' => '',
    'option_1_en' => '', 'option_1_hi' => '', 'option_1_te' => '',
    'option_2_en' => '', 'option_2_hi' => '', 'option_2_te' => '',
    'option_3_en' => '', 'option_3_hi' => '', 'option_3_te' => '',
    'option_4_en' => '', 'option_4_hi' => '', 'option_4_te' => '',
    'correct_option' => 1,
    'explanation_en' => '', 'explanation_hi' => '', 'explanation_te' => ''
];

if ($editQuestionId > 0) {
    $eqStmt = $pdo->prepare("SELECT * FROM " . tbl('questions') . " WHERE question_id = ? AND quiz_id = ?");
    $eqStmt->execute([$editQuestionId, $quizId]);
    $existingQ = $eqStmt->fetch();
    if ($existingQ) {
        $qData = $existingQ;
    }
}

// Handle Form Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_question') {
    $qEn = trim($_POST['question_en'] ?? '');
    $qHi = trim($_POST['question_hi'] ?? '');
    $qTe = trim($_POST['question_te'] ?? '');

    $o1En = trim($_POST['option_1_en'] ?? '');
    $o1Hi = trim($_POST['option_1_hi'] ?? '');
    $o1Te = trim($_POST['option_1_te'] ?? '');

    $o2En = trim($_POST['option_2_en'] ?? '');
    $o2Hi = trim($_POST['option_2_hi'] ?? '');
    $o2Te = trim($_POST['option_2_te'] ?? '');

    $o3En = trim($_POST['option_3_en'] ?? '');
    $o3Hi = trim($_POST['option_3_hi'] ?? '');
    $o3Te = trim($_POST['option_3_te'] ?? '');

    $o4En = trim($_POST['option_4_en'] ?? '');
    $o4Hi = trim($_POST['option_4_hi'] ?? '');
    $o4Te = trim($_POST['option_4_te'] ?? '');

    $correctOpt = (int)($_POST['correct_option'] ?? 1);
    $expEn = trim($_POST['explanation_en'] ?? '');
    $expHi = trim($_POST['explanation_hi'] ?? '');
    $expTe = trim($_POST['explanation_te'] ?? '');

    if (empty($qEn) || empty($o1En) || empty($o2En) || empty($o3En) || empty($o4En)) {
        $error = 'Question Text and all 4 Option fields in English are required.';
    } else {
        if ($editQuestionId > 0) {
            // Update
            $upStmt = $pdo->prepare("
                UPDATE " . tbl('questions') . " SET
                    question_en = ?, question_hi = ?, question_te = ?,
                    option_1_en = ?, option_1_hi = ?, option_1_te = ?,
                    option_2_en = ?, option_2_hi = ?, option_2_te = ?,
                    option_3_en = ?, option_3_hi = ?, option_3_te = ?,
                    option_4_en = ?, option_4_hi = ?, option_4_te = ?,
                    correct_option = ?,
                    explanation_en = ?, explanation_hi = ?, explanation_te = ?
                WHERE question_id = ? AND quiz_id = ?
            ");
            $upStmt->execute([
                $qEn, $qHi, $qTe,
                $o1En, $o1Hi, $o1Te,
                $o2En, $o2Hi, $o2Te,
                $o3En, $o3Hi, $o3Te,
                $o4En, $o4Hi, $o4Te,
                $correctOpt,
                $expEn, $expHi, $expTe,
                $editQuestionId, $quizId
            ]);
            header('Location: questions.php?quiz_id=' . $quizId . '&message=' . urlencode('Question updated successfully.'));
            exit();
        } else {
            // Get next question number
            $numStmt = $pdo->prepare("SELECT COALESCE(MAX(question_num), 0) + 1 FROM " . tbl('questions') . " WHERE quiz_id = ?");
            $numStmt->execute([$quizId]);
            $nextNum = $numStmt->fetchColumn();

            $insStmt = $pdo->prepare("
                INSERT INTO " . tbl('questions') . " (
                    quiz_id, question_num,
                    question_en, question_hi, question_te,
                    option_1_en, option_1_hi, option_1_te,
                    option_2_en, option_2_hi, option_2_te,
                    option_3_en, option_3_hi, option_3_te,
                    option_4_en, option_4_hi, option_4_te,
                    correct_option,
                    explanation_en, explanation_hi, explanation_te
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insStmt->execute([
                $quizId, $nextNum,
                $qEn, $qHi, $qTe,
                $o1En, $o1Hi, $o1Te,
                $o2En, $o2Hi, $o2Te,
                $o3En, $o3Hi, $o3Te,
                $o4En, $o4Hi, $o4Te,
                $correctOpt,
                $expEn, $expHi, $expTe
            ]);
            header('Location: questions.php?quiz_id=' . $quizId . '&message=' . urlencode('New trilingual question added!'));
            exit();
        }
    }
}

// Fetch Existing Questions List
$qListStmt = $pdo->prepare("SELECT * FROM " . tbl('questions') . " WHERE quiz_id = ? ORDER BY question_num ASC");
$qListStmt->execute([$quizId]);
$questionsList = $qListStmt->fetchAll();

$pageTitle = 'Manage Questions - ' . $quiz['title_en'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fa-solid fa-list-check" style="color: var(--bhel-gold);"></i> Trilingual Question Builder</h2>
        <p>Quiz: <strong><?= sanitize($quiz['title_en']) ?></strong> (Total Questions: <?= count($questionsList) ?>)</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Quizzes</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= sanitize($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($error) ?></div>
<?php endif; ?>

<!-- Question Form Card -->
<div class="card">
    <h3 style="font-size: 18px; color: #FFF; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
        <span><i class="fa-solid fa-plus-circle" style="color: var(--bhel-blue-accent);"></i> <?= $editQuestionId > 0 ? 'Edit Question #' . $qData['question_num'] : 'Add New Trilingual Question' ?></span>
        <?php if ($editQuestionId > 0): ?>
            <a href="questions.php?quiz_id=<?= $quizId ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 12px;">
                <i class="fa-solid fa-plus"></i> Add New Question
            </a>
        <?php endif; ?>
    </h3>

    <form method="POST" action="questions.php?quiz_id=<?= $quizId ?><?= $editQuestionId > 0 ? '&edit_id=' . $editQuestionId : '' ?>">
        <input type="hidden" name="action" value="save_question">

        <!-- Language Tabs Header -->
        <div class="tab-header">
            <button type="button" class="tab-link active" onclick="openLangTab(event, 'tab-en')"><i class="fa-solid fa-flag"></i> English Content</button>
            <button type="button" class="tab-link" onclick="openLangTab(event, 'tab-hi')"><i class="fa-solid fa-language"></i> हिन्दी (Hindi)</button>
            <button type="button" class="tab-link" onclick="openLangTab(event, 'tab-te')"><i class="fa-solid fa-language"></i> తెలుగు (Telugu)</button>
        </div>

        <!-- ENGLISH TAB -->
        <div id="tab-en" class="tab-content active">
            <div class="form-group">
                <label>Question Text (English) *</label>
                <textarea name="question_en" class="form-control" rows="2" required placeholder="Enter question in English"><?= sanitize($qData['question_en']) ?></textarea>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Option 1 (A - English) *</label>
                    <input type="text" name="option_1_en" class="form-control" value="<?= sanitize($qData['option_1_en']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Option 2 (B - English) *</label>
                    <input type="text" name="option_2_en" class="form-control" value="<?= sanitize($qData['option_2_en']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Option 3 (C - English) *</label>
                    <input type="text" name="option_3_en" class="form-control" value="<?= sanitize($qData['option_3_en']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Option 4 (D - English) *</label>
                    <input type="text" name="option_4_en" class="form-control" value="<?= sanitize($qData['option_4_en']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Answer Explanation (English Optional)</label>
                <textarea name="explanation_en" class="form-control" rows="2" placeholder="Explanation for correct answer displayed on scorecard"><?= sanitize($qData['explanation_en']) ?></textarea>
            </div>
        </div>

        <!-- HINDI TAB -->
        <div id="tab-hi" class="tab-content">
            <div class="form-group">
                <label>Question Text (हिन्दी - Hindi)</label>
                <textarea name="question_hi" class="form-control" rows="2" placeholder="हिन्दी में प्रश्न दर्ज करें"><?= sanitize($qData['question_hi']) ?></textarea>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Option 1 (A - हिन्दी)</label>
                    <input type="text" name="option_1_hi" class="form-control" value="<?= sanitize($qData['option_1_hi']) ?>">
                </div>

                <div class="form-group">
                    <label>Option 2 (B - हिन्दी)</label>
                    <input type="text" name="option_2_hi" class="form-control" value="<?= sanitize($qData['option_2_hi']) ?>">
                </div>

                <div class="form-group">
                    <label>Option 3 (C - हिन्दी)</label>
                    <input type="text" name="option_3_hi" class="form-control" value="<?= sanitize($qData['option_3_hi']) ?>">
                </div>

                <div class="form-group">
                    <label>Option 4 (D - हिन्दी)</label>
                    <input type="text" name="option_4_hi" class="form-control" value="<?= sanitize($qData['option_4_hi']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Answer Explanation (हिन्दी)</label>
                <textarea name="explanation_hi" class="form-control" rows="2" placeholder="उत्तर का विवरण"><?= sanitize($qData['explanation_hi']) ?></textarea>
            </div>
        </div>

        <!-- TELUGU TAB -->
        <div id="tab-te" class="tab-content">
            <div class="form-group">
                <label>Question Text (తెలుగు - Telugu)</label>
                <textarea name="question_te" class="form-control" rows="2" placeholder="తెలుగులో ప్రశ్నను ఎంటర్ చేయండి"><?= sanitize($qData['question_te']) ?></textarea>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Option 1 (A - తెలుగు)</label>
                    <input type="text" name="option_1_te" class="form-control" value="<?= sanitize($qData['option_1_te']) ?>">
                </div>

                <div class="form-group">
                    <label>Option 2 (B - తెలుగు)</label>
                    <input type="text" name="option_2_te" class="form-control" value="<?= sanitize($qData['option_2_te']) ?>">
                </div>

                <div class="form-group">
                    <label>Option 3 (C - తెలుగు)</label>
                    <input type="text" name="option_3_te" class="form-control" value="<?= sanitize($qData['option_3_te']) ?>">
                </div>

                <div class="form-group">
                    <label>Option 4 (D - తెలుగు)</label>
                    <input type="text" name="option_4_te" class="form-control" value="<?= sanitize($qData['option_4_te']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Answer Explanation (తెలుగు)</label>
                <textarea name="explanation_te" class="form-control" rows="2" placeholder="సరైన సమాధానం వివరణ"><?= sanitize($qData['explanation_te']) ?></textarea>
            </div>
        </div>

        <!-- CORRECT OPTION SELECTOR -->
        <div style="background: rgba(255, 193, 7, 0.08); border: 1px solid var(--bhel-gold); padding: 18px; border-radius: 10px; margin-top: 20px; margin-bottom: 20px;">
            <label style="font-weight: 700; color: var(--bhel-gold); font-size: 15px; margin-bottom: 10px; display: block;">
                <i class="fa-solid fa-circle-check"></i> Select Correct Option (1 out of 4) *
            </label>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 15px; font-weight: 600;">
                        <input type="radio" name="correct_option" value="<?= $i ?>" <?= (int)$qData['correct_option'] === $i ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--bhel-blue-accent);">
                        Option <?= $i ?> (<?= chr(64 + $i) ?>)
                    </label>
                <?php endfor; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 12px 25px; font-size: 15px;">
            <i class="fa-solid fa-floppy-disk"></i> <?= $editQuestionId > 0 ? 'Update Question' : 'Save & Add Question' ?>
        </button>
    </form>
</div>

<!-- Configured Questions List -->
<div class="card" style="margin-top: 30px;">
    <h3 style="font-size: 18px; color: #FFF; margin-bottom: 20px;">
        <i class="fa-solid fa-list-ol" style="color: var(--bhel-blue-accent);"></i> Configured Questions (<?= count($questionsList) ?>)
    </h3>

    <?php if (empty($questionsList)): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 20px;">No questions added to this quiz yet.</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($questionsList as $idx => $q): ?>
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); border-radius: 10px; padding: 18px; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-weight: 700; color: var(--bhel-blue-accent); font-size: 15px; margin-bottom: 6px;">
                            Q<?= $idx + 1 ?>. <?= sanitize($q['question_en']) ?>
                        </div>
                        <?php if (!empty($q['question_hi']) || !empty($q['question_te'])): ?>
                            <div style="font-size: 13px; color: var(--bhel-gold); margin-bottom: 10px;">
                                <?= sanitize($q['question_hi']) ?> | <?= sanitize($q['question_te']) ?>
                            </div>
                        <?php endif; ?>

                        <div style="font-size: 13px; color: var(--text-secondary); display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px;">
                            <div <?= $q['correct_option'] == 1 ? 'style="color: var(--status-active); font-weight:700;"' : '' ?>>A: <?= sanitize($q['option_1_en']) ?></div>
                            <div <?= $q['correct_option'] == 2 ? 'style="color: var(--status-active); font-weight:700;"' : '' ?>>B: <?= sanitize($q['option_2_en']) ?></div>
                            <div <?= $q['correct_option'] == 3 ? 'style="color: var(--status-active); font-weight:700;"' : '' ?>>C: <?= sanitize($q['option_3_en']) ?></div>
                            <div <?= $q['correct_option'] == 4 ? 'style="color: var(--status-active); font-weight:700;"' : '' ?>>D: <?= sanitize($q['option_4_en']) ?></div>
                        </div>

                        <span class="badge badge-success">Correct Option: <?= $q['correct_option'] ?> (<?= chr(64 + $q['correct_option']) ?>)</span>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <a href="questions.php?quiz_id=<?= $quizId ?>&edit_id=<?= $q['question_id'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                        <a href="questions.php?quiz_id=<?= $quizId ?>&action=delete&q_id=<?= $q['question_id'] ?>" onclick="return confirm('Delete this question?');" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function openLangTab(evt, tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-link').forEach(el => el.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
