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
    'languages' => 'en',
    'target_categories' => 'executive,supervisor,workman',
    'excluded_staff_nos' => '',
    'excluded_departments' => '',
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
        $quiz['languages'] = !empty($existing['languages']) ? $existing['languages'] : 'en';
        $quiz['target_categories'] = !empty($existing['target_categories']) ? $existing['target_categories'] : 'executive,supervisor,workman';
        $quiz['excluded_staff_nos'] = $existing['excluded_staff_nos'] ?? '';
        $quiz['excluded_departments'] = $existing['excluded_departments'] ?? '';
        // Format datetimes for datetime-local picker
        $quiz['start_time'] = date('Y-m-d\TH:i', strtotime($existing['start_time']));
        $quiz['end_time'] = date('Y-m-d\TH:i', strtotime($existing['end_time']));
    } else {
        header('Location: index.php?error=' . urlencode('Quiz not found.'));
        exit();
    }
}

// Check if any user response has been submitted for this quiz
$hasResponses = false;
$responseCount = 0;
if ($quizId > 0) {
    $respStmt = $pdo->prepare("SELECT COUNT(*) FROM " . tbl('attempts') . " WHERE quiz_id = ?");
    $respStmt->execute([$quizId]);
    $responseCount = (int)$respStmt->fetchColumn();
    $hasResponses = ($responseCount > 0);
}
$disabledAttr = $hasResponses ? 'disabled' : '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($quizId > 0 && $hasResponses) {
        $error = "Quiz settings cannot be modified because {$responseCount} user response(s) have already been submitted.";
    } else {
        $titleEn = trim($_POST['title_en'] ?? '');
        $titleHi = trim($_POST['title_hi'] ?? '');
        $titleTe = trim($_POST['title_te'] ?? '');
        $descEn  = trim($_POST['description_en'] ?? '');
        $descHi  = trim($_POST['description_hi'] ?? '');
        $descTe  = trim($_POST['description_te'] ?? '');
        
        $langsArr = $_POST['languages'] ?? ['en'];
        if (empty($langsArr)) $langsArr = ['en'];
        $languagesStr = implode(',', $langsArr);

        $catsArr = $_POST['target_categories'] ?? ['executive', 'supervisor', 'workman'];
        if (empty($catsArr)) $catsArr = ['executive', 'supervisor', 'workman'];
        $targetCategoriesStr = implode(',', $catsArr);

        $exStaffNos = trim($_POST['excluded_staff_nos'] ?? '');
        $exDepts    = trim($_POST['excluded_departments'] ?? '');

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
                        languages = ?, target_categories = ?,
                        excluded_staff_nos = ?, excluded_departments = ?,
                        start_time = ?, end_time = ?, duration_minutes = ?,
                        marks_per_question = ?, negative_marks = ?, pass_percentage = ?,
                        is_published = ?
                    WHERE quiz_id = ?
                ");
                $upStmt->execute([
                    $titleEn, $titleHi, $titleTe,
                    $descEn, $descHi, $descTe,
                    $languagesStr, $targetCategoriesStr,
                    $exStaffNos, $exDepts,
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
                        languages, target_categories,
                        excluded_staff_nos, excluded_departments,
                        start_time, end_time, duration_minutes,
                        marks_per_question, negative_marks, pass_percentage,
                        is_published, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insStmt->execute([
                    $titleEn, $titleHi, $titleTe,
                    $descEn, $descHi, $descTe,
                    $languagesStr, $targetCategoriesStr,
                    $exStaffNos, $exDepts,
                    $startFormatted, $endFormatted, $duration,
                    $marks, $negMarks, $passPct,
                    $isPub, $_SESSION['user_id']
                ]);
                $newId = $pdo->lastInsertId();
                header('Location: questions.php?quiz_id=' . $newId . '&message=' . urlencode('Quiz created! Now add questions.'));
                exit();
            }
        }
    }
}

$pageTitle = $quizId > 0 ? 'Edit Quiz Settings' : 'Create New Quiz';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fa-solid fa-pen-to-square" style="color: var(--bhel-gold);"></i> <?= $quizId > 0 ? 'Edit Quiz Settings' : 'Create New Quiz' ?></h2>
        <p>Set up quiz details, language options, target employee categories, time access window, and scoring rules</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<?php if ($hasResponses): ?>
    <div class="alert alert-error" style="border-left: 5px solid var(--status-danger); background: rgba(239, 68, 68, 0.15); color: #FFF; margin-bottom: 25px; padding: 15px 20px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fa-solid fa-lock" style="font-size: 24px; color: #FCA5A5;"></i>
            <div>
                <strong style="font-size: 16px; display: block; color: #FCA5A5; margin-bottom: 2px;">Quiz Settings Locked</strong>
                <span style="font-size: 13px; color: var(--text-secondary);">
                    This quiz settings form is disabled because <strong><?= $responseCount ?></strong> participant response(s) have already been submitted. To ensure evaluation integrity, settings cannot be modified once responses exist.
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($error) ?></div>
<?php endif; ?>

<form method="POST" action="quiz_edit.php?quiz_id=<?= $quizId ?>">

    <!-- Language Options & Target Employee Category Setup Card -->
    <div class="card">
        <h3 style="font-size: 16px; color: var(--bhel-gold); margin-bottom: 20px;">
            <i class="fa-solid fa-gear"></i> Quiz Language & Target Employee Group Setup
        </h3>

        <div class="grid-2">
            <div class="form-group">
                <label style="font-weight: 700; color: #FFF; margin-bottom: 10px; display: block;">
                    <i class="fa-solid fa-language" style="color: var(--bhel-blue-accent);"></i> Available Quiz Language Options *
                </label>
                <?php $selectedLangs = explode(',', $quiz['languages']); ?>
                <div style="display: flex; gap: 20px; align-items: center; background: rgba(255,255,255,0.03); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-light); flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="languages[]" value="en" <?= in_array('en', $selectedLangs) ? 'checked' : '' ?> <?= $disabledAttr ?> onchange="toggleLangInputs()" style="width: 18px; height: 18px; accent-color: var(--bhel-blue-accent);">
                        <strong>English (EN)</strong>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="languages[]" value="hi" id="chk-lang-hi" <?= in_array('hi', $selectedLangs) ? 'checked' : '' ?> <?= $disabledAttr ?> onchange="toggleLangInputs()" style="width: 18px; height: 18px; accent-color: var(--bhel-blue-accent);">
                        <strong>हिन्दी (Hindi)</strong>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="languages[]" value="te" id="chk-lang-te" <?= in_array('te', $selectedLangs) ? 'checked' : '' ?> <?= $disabledAttr ?> onchange="toggleLangInputs()" style="width: 18px; height: 18px; accent-color: var(--bhel-blue-accent);">
                        <strong>తెలుగు (Telugu)</strong>
                    </label>
                </div>
                <small style="color: var(--text-muted); font-size: 11px; margin-top: 5px; display: block;">Quizzes are single-language by default. Select extra languages if available for employees.</small>
            </div>

            <div class="form-group">
                <label style="font-weight: 700; color: #FFF; margin-bottom: 10px; display: block;">
                    <i class="fa-solid fa-users-gear" style="color: var(--bhel-gold);"></i> Target Employee Category (Select Group(s)) *
                </label>
                <?php $selectedCats = explode(',', $quiz['target_categories']); ?>
                <div style="display: flex; gap: 20px; align-items: center; background: rgba(255,255,255,0.03); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-light); flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="target_categories[]" value="executive" <?= in_array('executive', $selectedCats) ? 'checked' : '' ?> <?= $disabledAttr ?> style="width: 18px; height: 18px; accent-color: var(--bhel-gold);">
                        <strong>Executive</strong>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="target_categories[]" value="supervisor" <?= in_array('supervisor', $selectedCats) ? 'checked' : '' ?> <?= $disabledAttr ?> style="width: 18px; height: 18px; accent-color: var(--bhel-gold);">
                        <strong>Supervisor</strong>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="target_categories[]" value="workman" <?= in_array('workman', $selectedCats) ? 'checked' : '' ?> <?= $disabledAttr ?> style="width: 18px; height: 18px; accent-color: var(--bhel-gold);">
                        <strong>Workman</strong>
                    </label>
                </div>
                <small style="color: var(--text-muted); font-size: 11px; margin-top: 5px; display: block;">Employees belonging to selected group(s) can view and take this quiz.</small>
            </div>
        </div>
    </div>

    <!-- Quiz Access Exclusion List Card -->
    <div class="card">
        <h3 style="font-size: 16px; color: #FCA5A5; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-user-slash" style="color: var(--status-danger);"></i> Quiz Access Exclusion Rules (Optional)
        </h3>
        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 15px;">
            Specify staff numbers or department names that are excluded from participating in this quiz (e.g. paper setters, organizing committee members, or specific departments).
        </p>

        <div class="grid-2">
            <div class="form-group">
                <label style="font-weight: 600; color: #FFF;">
                    <i class="fa-solid fa-id-badge" style="color: var(--bhel-gold);"></i> Excluded Staff Numbers / Employee IDs
                </label>
                <textarea name="excluded_staff_nos" class="form-control" rows="2" <?= $disabledAttr ?> placeholder="e.g. EXEC2001, SUP3001, WRK4002 (comma or line separated staff numbers)"><?= sanitize($quiz['excluded_staff_nos']) ?></textarea>
                <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">Employees with matching Staff No will be blocked from taking this quiz.</small>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; color: #FFF;">
                    <i class="fa-solid fa-building-user" style="color: var(--bhel-blue-accent);"></i> Excluded Departments
                </label>
                <textarea name="excluded_departments" class="form-control" rows="2" <?= $disabledAttr ?> placeholder="e.g. IT & Safety Admin, Quality Assurance (comma or line separated department names)"><?= sanitize($quiz['excluded_departments']) ?></textarea>
                <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">All employees belonging to excluded department(s) will be blocked from this quiz.</small>
            </div>
        </div>
    </div>

    <!-- Title & Description Card -->
    <div class="card">
        <h3 style="font-size: 16px; color: var(--bhel-blue-accent); margin-bottom: 20px;">
            <i class="fa-solid fa-heading"></i> Quiz Title & Description
        </h3>

        <div class="form-group">
            <label>Quiz Title (English) *</label>
            <input type="text" name="title_en" class="form-control" value="<?= sanitize($quiz['title_en']) ?>" <?= $disabledAttr ?> placeholder="e.g. Industrial Safety & Emergency Procedures Quiz 2026" required>
        </div>

        <div class="form-group">
            <label>Description (English)</label>
            <textarea name="description_en" class="form-control" rows="2" <?= $disabledAttr ?> placeholder="Brief overview of quiz content and instructions for employees"><?= sanitize($quiz['description_en']) ?></textarea>
        </div>

        <div id="group-lang-hi" style="display: none; margin-top: 15px; border-top: 1px dashed var(--border-light); padding-top: 15px;">
            <div class="form-group">
                <label>Quiz Title (हिन्दी - Hindi)</label>
                <input type="text" name="title_hi" class="form-control" value="<?= sanitize($quiz['title_hi']) ?>" <?= $disabledAttr ?> placeholder="उदा. औद्योगिक सुरक्षा और आपातकालीन प्रक्रिया क्विज़">
            </div>

            <div class="form-group">
                <label>Description (हिन्दी - Hindi)</label>
                <textarea name="description_hi" class="form-control" rows="2" <?= $disabledAttr ?> placeholder="कर्मचारियों के लिए संक्षिप्त विवरण"><?= sanitize($quiz['description_hi']) ?></textarea>
            </div>
        </div>

        <div id="group-lang-te" style="display: none; margin-top: 15px; border-top: 1px dashed var(--border-light); padding-top: 15px;">
            <div class="form-group">
                <label>Quiz Title (తెలుగు - Telugu)</label>
                <input type="text" name="title_te" class="form-control" value="<?= sanitize($quiz['title_te']) ?>" <?= $disabledAttr ?> placeholder="ఉదా. పారిశ్రామిక భద్రత మరియు ఎమర్జెన్సీ విధానాలు క్విజ్">
            </div>

            <div class="form-group">
                <label>Description (తెలుగు - Telugu)</label>
                <textarea name="description_te" class="form-control" rows="2" <?= $disabledAttr ?> placeholder="ఉద్యోగుల కోసం క్విజ్ వివరాలు"><?= sanitize($quiz['description_te']) ?></textarea>
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
                <input type="datetime-local" name="start_time" class="form-control" value="<?= $quiz['start_time'] ?>" <?= $disabledAttr ?> required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-calendar-xmark"></i> Quiz End Date & Time (Access Window Closes) *</label>
                <input type="datetime-local" name="end_time" class="form-control" value="<?= $quiz['end_time'] ?>" <?= $disabledAttr ?> required>
            </div>
        </div>

        <div class="grid-4">
            <div class="form-group">
                <label><i class="fa-solid fa-stopwatch"></i> Duration (Minutes)</label>
                <input type="number" name="duration_minutes" class="form-control" value="<?= (int)$quiz['duration_minutes'] ?>" min="1" max="180" <?= $disabledAttr ?> required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-plus" style="color: var(--status-active);"></i> Positive Marks / Question</label>
                <input type="number" step="0.25" name="marks_per_question" class="form-control" value="<?= (float)$quiz['marks_per_question'] ?>" <?= $disabledAttr ?> required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-minus" style="color: var(--status-danger);"></i> Negative Marks / Wrong Ans</label>
                <input type="number" step="0.05" name="negative_marks" class="form-control" value="<?= (float)$quiz['negative_marks'] ?>" <?= $disabledAttr ?> required>
                <small style="color: var(--text-muted); font-size: 11px;">e.g. 0.50 for -0.5 deduction</small>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-percent" style="color: var(--bhel-gold);"></i> Pass Percentage (%)</label>
                <input type="number" step="1" name="pass_percentage" class="form-control" value="<?= (float)$quiz['pass_percentage'] ?>" min="0" max="100" <?= $disabledAttr ?> required>
            </div>
        </div>

        <div class="form-group" style="margin-top: 10px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 15px;">
                <input type="checkbox" name="is_published" value="1" <?= $quiz['is_published'] ? 'checked' : '' ?> <?= $disabledAttr ?> style="width: 20px; height: 20px; accent-color: var(--bhel-gold);">
                <strong>Publish Quiz immediately (Employees can see it on dashboard)</strong>
            </label>
        </div>
    </div>

    <div style="display: flex; gap: 15px; justify-content: flex-end;">
        <a href="index.php" class="btn btn-outline">Cancel</a>
        <?php if ($hasResponses): ?>
            <button type="button" class="btn btn-primary" disabled style="padding: 12px 30px; font-size: 15px; opacity: 0.5; cursor: not-allowed; background: var(--bhel-blue-accent); border-color: var(--bhel-blue-accent);" title="Quiz settings are locked because participant responses have been submitted.">
                <i class="fa-solid fa-lock"></i> Save Quiz Settings (Locked)
            </button>
        <?php else: ?>
            <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 15px;">
                <i class="fa-solid fa-floppy-disk"></i> Save Quiz Settings
            </button>
        <?php endif; ?>
    </div>
</form>

<script>
function toggleLangInputs() {
    const chkHi = document.getElementById('chk-lang-hi');
    const chkTe = document.getElementById('chk-lang-te');
    
    document.getElementById('group-lang-hi').style.display = chkHi && chkHi.checked ? 'block' : 'none';
    document.getElementById('group-lang-te').style.display = chkTe && chkTe.checked ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleLangInputs);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
