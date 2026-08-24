<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Create / Edit Quiz Settings & Scheduling
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = getDBConnection();
$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$error = '';
$message = '';

$quiz = [
    'title_en' => '',
    'title_hi' => '',
    'title_te' => '',
    'description_en' => '',
    'description_hi' => '',
    'description_te' => '',
    'start_time' => date('Y-m-d\TH:i'),
    'end_time' => date('Y-m-d\TH:i', strtotime('+7 days')),
    'duration_minutes' => 15,
    'marks_per_question' => 2.00,
    'negative_marks' => 0.50,
    'pass_percentage' => 40.00,
    'is_published' => 1
];

if ($quizId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM " . tbl('quizzes') . " WHERE quiz_id = ?");
    $stmt->execute([$quizId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $quiz = $existing;
        // Format datetimes for datetime-local picker
        $quiz['start_time'] = date('Y-m-d\TH:i', strtotime($existing['start_time']));
        $quiz['end_time'] = date('Y-m-d\TH:i', strtotime($existing['end_time']));
    } else {
        header('Location: index.php?error=' . urlencode('Quiz not found.'));
        exit();
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titleEn = trim($_POST['title_en'] ?? '');
    $titleHi = trim($_POST['title_hi'] ?? '');
    $titleTe = trim($_POST['title_te'] ?? '');
    $descEn  = trim($_POST['description_en'] ?? '');
    $descHi  = trim($_POST['description_hi'] ?? '');
    $descTe  = trim($_POST['description_te'] ?? '');
    $start   = $_POST['start_time'] ?? '';
    $end     = $_POST['end_time'] ?? '';
    $duration = (int)($_POST['duration_minutes'] ?? 15);
    $marks    = (float)($_POST['marks_per_question'] ?? 1.00);
    $negMarks = (float)($_POST['negative_marks'] ?? 0.00);
    $passPct  = (float)($_POST['pass_percentage'] ?? 40.00);
    $isPub    = isset($_POST['is_published']) ? 1 : 0;

    if (empty($titleEn) || empty($start) || empty($end)) {
        $error = 'Title (English), Start Time, and End Time are required fields.';
    } else {
        // Format for MySQL DATETIME
        $startFormatted = date('Y-m-d H:i:s', strtotime($start));
        $endFormatted   = date('Y-m-d H:i:s', strtotime($end));

        if ($quizId > 0) {
            // Update
            $upStmt = $pdo->prepare("
                UPDATE " . tbl('quizzes') . " SET
                    title_en = ?, title_hi = ?, title_te = ?,
                    description_en = ?, description_hi = ?, description_te = ?,
                    start_time = ?, end_time = ?, duration_minutes = ?,
                    marks_per_question = ?, negative_marks = ?, pass_percentage = ?,
                    is_published = ?
                WHERE quiz_id = ?
            ");
            $upStmt->execute([
                $titleEn, $titleHi, $titleTe,
                $descEn, $descHi, $descTe,
                $startFormatted, $endFormatted, $duration,
                $marks, $negMarks, $passPct,
                $isPub, $quizId
            ]);
            header('Location: index.php?message=' . urlencode('Quiz settings updated successfully.'));
            exit();
        } else {
            // Insert
            $insStmt = $pdo->prepare("
                INSERT INTO " . tbl('quizzes') . " (
                    title_en, title_hi, title_te,
                    description_en, description_hi, description_te,
                    start_time, end_time, duration_minutes,
                    marks_per_question, negative_marks, pass_percentage,
                    is_published, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insStmt->execute([
                $titleEn, $titleHi, $titleTe,
                $descEn, $descHi, $descTe,
                $startFormatted, $endFormatted, $duration,
                $marks, $negMarks, $passPct,
                $isPub, $_SESSION['user_id']
            ]);
            $newId = $pdo->lastInsertId();
            header('Location: questions.php?quiz_id=' . $newId . '&message=' . urlencode('Quiz created! Now add questions in English, Hindi, and Telugu.'));
            exit();
        }
    }
}

$pageTitle = $quizId > 0 ? 'Edit Quiz Settings' : 'Create New Quiz';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fa-solid fa-pen-to-square" style="color: var(--bhel-gold);"></i> <?= $quizId > 0 ? 'Edit Quiz Settings' : 'Create New Quiz' ?></h2>
        <p>Set up quiz details, time access window, and negative marking rules</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($error) ?></div>
<?php endif; ?>

<form method="POST" action="quiz_edit.php?quiz_id=<?= $quizId ?>">
    <div class="card">
        <h3 style="font-size: 16px; color: var(--bhel-blue-accent); margin-bottom: 20px;">
            <i class="fa-solid fa-language"></i> Trilingual Quiz Title & Description
        </h3>

        <div class="form-group">
            <label>Quiz Title (English) *</label>
            <input type="text" name="title_en" class="form-control" value="<?= sanitize($quiz['title_en']) ?>" placeholder="e.g. Industrial Safety & Emergency Procedures Quiz 2026" required>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Quiz Title (हिन्दी - Hindi)</label>
                <input type="text" name="title_hi" class="form-control" value="<?= sanitize($quiz['title_hi']) ?>" placeholder="उदा. औद्योगिक सुरक्षा और आपातकालीन प्रक्रिया क्विज़">
            </div>

            <div class="form-group">
                <label>Quiz Title (తెలుగు - Telugu)</label>
                <input type="text" name="title_te" class="form-control" value="<?= sanitize($quiz['title_te']) ?>" placeholder="ఉదా. పారిశ్రామిక భద్రత మరియు ఎమర్జెన్సీ విధానాలు క్విజ్">
            </div>
        </div>

        <div class="form-group">
            <label>Description (English)</label>
            <textarea name="description_en" class="form-control" rows="2" placeholder="Brief overview of quiz content and instructions for employees"><?= sanitize($quiz['description_en']) ?></textarea>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Description (हिन्दी - Hindi)</label>
                <textarea name="description_hi" class="form-control" rows="2" placeholder="कर्मचारियों के लिए संक्षिप्त विवरण"><?= sanitize($quiz['description_hi']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Description (తెలుగు - Telugu)</label>
                <textarea name="description_te" class="form-control" rows="2" placeholder="ఉద్యోగుల కోసం క్విజ్ వివరాలు"><?= sanitize($quiz['description_te']) ?></textarea>
            </div>
        </div>
    </div>

    <!-- Schedule & Scoring Settings Card -->
    <div class="card">
        <h3 style="font-size: 16px; color: var(--bhel-gold); margin-bottom: 20px;">
            <i class="fa-solid fa-clock-rotate-left"></i> Time Scheduling & Marking Scheme Rules
        </h3>

        <div class="grid-2">
            <div class="form-group">
                <label><i class="fa-solid fa-calendar-days"></i> Quiz Start Date & Time (Access Window Opens) *</label>
                <input type="datetime-local" name="start_time" class="form-control" value="<?= $quiz['start_time'] ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-calendar-xmark"></i> Quiz End Date & Time (Access Window Closes) *</label>
                <input type="datetime-local" name="end_time" class="form-control" value="<?= $quiz['end_time'] ?>" required>
            </div>
        </div>

        <div class="grid-4">
            <div class="form-group">
                <label><i class="fa-solid fa-stopwatch"></i> Duration (Minutes)</label>
                <input type="number" name="duration_minutes" class="form-control" value="<?= (int)$quiz['duration_minutes'] ?>" min="1" max="180" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-plus" style="color: var(--status-active);"></i> Positive Marks / Question</label>
                <input type="number" step="0.25" name="marks_per_question" class="form-control" value="<?= (float)$quiz['marks_per_question'] ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-minus" style="color: var(--status-danger);"></i> Negative Marks / Wrong Ans</label>
                <input type="number" step="0.05" name="negative_marks" class="form-control" value="<?= (float)$quiz['negative_marks'] ?>" required>
                <small style="color: var(--text-muted); font-size: 11px;">e.g. 0.50 for -0.5 deduction</small>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-percent" style="color: var(--bhel-gold);"></i> Pass Percentage (%)</label>
                <input type="number" step="1" name="pass_percentage" class="form-control" value="<?= (float)$quiz['pass_percentage'] ?>" min="0" max="100" required>
            </div>
        </div>

        <div class="form-group" style="margin-top: 10px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 15px;">
                <input type="checkbox" name="is_published" value="1" <?= $quiz['is_published'] ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--bhel-gold);">
                <strong>Publish Quiz immediately (Employees can see it on dashboard)</strong>
            </label>
        </div>
    </div>

    <div style="display: flex; gap: 15px; justify-content: flex-end;">
        <a href="index.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 15px;">
            <i class="fa-solid fa-floppy-disk"></i> Save Quiz Settings
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
