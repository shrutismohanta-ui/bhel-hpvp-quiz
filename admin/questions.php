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

// Parse enabled languages for this quiz (Top Level Scope)
$quizLangs = explode(',', $quiz['languages'] ?? 'en');
$quizLangs = array_values(array_filter(array_map('trim', $quizLangs)));
if (empty($quizLangs)) $quizLangs = ['en'];
$primaryLang = $quizLangs[0];

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
    'explanation_en' => '', 'explanation_hi' => '', 'explanation_te' => '',
    'question_num' => ''
];

if ($editQuestionId > 0) {
    $eqStmt = $pdo->prepare("SELECT * FROM " . tbl('questions') . " WHERE question_id = ? AND quiz_id = ?");
    $eqStmt->execute([$editQuestionId, $quizId]);
    $existingQ = $eqStmt->fetch();
    if ($existingQ) {
        $qData = $existingQ;
    }
}

// Handle Download Sample CSV Template
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    $filename = 'BHEL_Quiz_Question_Upload_Template.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Write UTF-8 Byte Order Mark (BOM) for Excel Unicode compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Header Row
    fputcsv($output, [
        'question_en', 'question_hi', 'question_te',
        'option_1_en', 'option_1_hi', 'option_1_te',
        'option_2_en', 'option_2_hi', 'option_2_te',
        'option_3_en', 'option_3_hi', 'option_3_te',
        'option_4_en', 'option_4_hi', 'option_4_te',
        'correct_option',
        'explanation_en', 'explanation_hi', 'explanation_te'
    ]);

    // Sample Row 1
    fputcsv($output, [
        'What color safety helmet is mandatory for visitors at BHEL premises?',
        'बीएचईएल परिसर में आगंतुकों के लिए किस रंग का सुरक्षा हेलमेट अनिवार्य है?',
        'BHEL ప్రాంగణంలో సందర్శకులకు ఏ రంగు సేఫ్టీ హెల్మెట్ తప్పనిసరి?',
        'Yellow', 'पीला', 'పసుపు',
        'White', 'सफेद', 'తెలుపు',
        'Green', 'हरा', 'ఆకుపచ్చ',
        'Blue', 'नीला', 'నీలం',
        '1',
        'Yellow helmets are standard for visitors and contractors.',
        'पीले हेलमेट आगंतुकों और ठेकेदारों के लिए मानक हैं।',
        'పసుపు హెల్మెట్లు సందర్శకులు మరియు కాంట్రాక్టర్లకు ప్రామాణికం.'
    ]);

    // Sample Row 2
    fputcsv($output, [
        'What does PPE stand for in workplace safety guidelines?',
        'कार्यस्थल सुरक्षा दिशानिर्देशों में PPE का क्या अर्थ है?',
        'వర్క్‌ప్లేస్ సేఫ్టీ మార్గదర్శకాలలో PPE అంటే ఏమిటి?',
        'Personal Protective Equipment', 'पर्सनल प्रोटेक्टिव इक्विपमेंट (व्यक्तिगत सुरक्षा उपकरण)', 'పర్సనల్ ప్రొటెక్టివ్ ఎక్విప్‌మెంట్',
        'Public Plant Engine', 'पब्लिक प्लांट इंजन', 'పబ్లిక్ ప్లాంట్ ఇంజిన్',
        'Power Plant Enterprise', 'पावर प्लांट एंटरप्राइज', 'పవర్ ప్లాంట్ ఎంటర్ప్రైజ్',
        'Proper Process Execution', 'प्रॉपर प्रोसेस एग्जीक्यूशन', 'ప్రాపర్ ప్రాసెస్ ఎగ్జिक्यूशन',
        '1',
        'PPE includes helmets, goggles, safety shoes, and gloves.',
        'PPE में हेलमेट, चश्मे, सुरक्षा जूते और दस्ताने शामिल हैं।',
        'PPE అంటే హెల్మెట్లు, గాగుల్స్, సేఫ్టీ షూస్ మరియు గ్లోవ్స్.'
    ]);

    fclose($output);
    exit();
}

// Handle Bulk CSV/Excel Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_upload') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid CSV file to upload.';
    } else {
        $filePath = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $error = 'Could not open uploaded file.';
        } else {
            // Strip BOM if present
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $header = fgetcsv($handle, 4096, ',');
            if (!$header) {
                $error = 'Uploaded file is empty or missing headers.';
            } else {
                $colMap = [];
                foreach ($header as $idx => $colName) {
                    $cleanName = strtolower(trim(preg_replace('/[^a-z0-9_]/i', '', $colName)));
                    $colMap[$cleanName] = $idx;
                }

                $numStmt = $pdo->prepare("SELECT COALESCE(MAX(question_num), 0) FROM " . tbl('questions') . " WHERE quiz_id = ?");
                $numStmt->execute([$quizId]);
                $currentMaxNum = (int)$numStmt->fetchColumn();

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

                $importedCount = 0;
                $skippedCount = 0;

                while (($row = fgetcsv($handle, 4096, ',')) !== false) {
                    if (empty(array_filter($row))) continue;

                    $getVal = function($key) use ($row, $colMap) {
                        return isset($colMap[$key]) && isset($row[$colMap[$key]]) ? trim($row[$colMap[$key]]) : '';
                    };

                    $qEn = $getVal('question_en');
                    $qHi = $getVal('question_hi');
                    $qTe = $getVal('question_te');

                    if (empty($qEn) && empty($qHi) && empty($qTe)) {
                        $qGen = $getVal('question');
                        if (!empty($qGen)) {
                            if ($primaryLang === 'hi') $qHi = $qGen;
                            elseif ($primaryLang === 'te') $qTe = $qGen;
                            else $qEn = $qGen;
                        }
                    }

                    $o1En = $getVal('option_1_en') ?: ($getVal('option1_en') ?: $getVal('option_1'));
                    $o1Hi = $getVal('option_1_hi') ?: $getVal('option1_hi');
                    $o1Te = $getVal('option_1_te') ?: $getVal('option1_te');

                    $o2En = $getVal('option_2_en') ?: ($getVal('option2_en') ?: $getVal('option_2'));
                    $o2Hi = $getVal('option_2_hi') ?: $getVal('option2_hi');
                    $o2Te = $getVal('option_2_te') ?: $getVal('option2_te');

                    $o3En = $getVal('option_3_en') ?: ($getVal('option3_en') ?: $getVal('option_3'));
                    $o3Hi = $getVal('option_3_hi') ?: $getVal('option3_hi');
                    $o3Te = $getVal('option_3_te') ?: $getVal('option3_te');

                    $o4En = $getVal('option_4_en') ?: ($getVal('option4_en') ?: $getVal('option_4'));
                    $o4Hi = $getVal('option_4_hi') ?: $getVal('option4_hi');
                    $o4Te = $getVal('option_4_te') ?: $getVal('option4_te');

                    $rawCorrect = strtoupper(trim($getVal('correct_option') ?: $getVal('correct_answer')));
                    $correctOpt = 1;
                    if ($rawCorrect === '1' || $rawCorrect === 'A') $correctOpt = 1;
                    elseif ($rawCorrect === '2' || $rawCorrect === 'B') $correctOpt = 2;
                    elseif ($rawCorrect === '3' || $rawCorrect === 'C') $correctOpt = 3;
                    elseif ($rawCorrect === '4' || $rawCorrect === 'D') $correctOpt = 4;

                    $expEn = $getVal('explanation_en') ?: $getVal('explanation');
                    $expHi = $getVal('explanation_hi');
                    $expTe = $getVal('explanation_te');

                    $hasAnyQ = !empty($qEn) || !empty($qHi) || !empty($qTe);
                    $hasAnyO1 = !empty($o1En) || !empty($o1Hi) || !empty($o1Te);
                    $hasAnyO2 = !empty($o2En) || !empty($o2Hi) || !empty($o2Te);
                    $hasAnyO3 = !empty($o3En) || !empty($o3Hi) || !empty($o3Te);
                    $hasAnyO4 = !empty($o4En) || !empty($o4Hi) || !empty($o4Te);

                    if (!$hasAnyQ || !$hasAnyO1 || !$hasAnyO2 || !$hasAnyO3 || !$hasAnyO4) {
                        $skippedCount++;
                        continue;
                    }

                    $currentMaxNum++;
                    $insStmt->execute([
                        $quizId, $currentMaxNum,
                        $qEn, $qHi, $qTe,
                        $o1En, $o1Hi, $o1Te,
                        $o2En, $o2Hi, $o2Te,
                        $o3En, $o3Hi, $o3Te,
                        $o4En, $o4Hi, $o4Te,
                        $correctOpt,
                        $expEn, $expHi, $expTe
                    ]);
                    $importedCount++;
                }

                fclose($handle);

                if ($importedCount > 0) {
                    $msg = "Successfully imported {$importedCount} questions in bulk!";
                    if ($skippedCount > 0) {
                        $msg .= " ({$skippedCount} incomplete rows were skipped).";
                    }
                    header('Location: questions.php?quiz_id=' . $quizId . '&message=' . urlencode($msg));
                    exit();
                } else {
                    $error = "No valid question records found in file. Please ensure column headers match the template.";
                }
            }
        }
    }
}

// Handle Single Question Form Save
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

    $isValid = true;
    $primaryQ = trim($_POST['question_' . $primaryLang] ?? '');
    $primaryO1 = trim($_POST['option_1_' . $primaryLang] ?? '');
    $primaryO2 = trim($_POST['option_2_' . $primaryLang] ?? '');
    $primaryO3 = trim($_POST['option_3_' . $primaryLang] ?? '');
    $primaryO4 = trim($_POST['option_4_' . $primaryLang] ?? '');

    if (empty($primaryQ) || empty($primaryO1) || empty($primaryO2) || empty($primaryO3) || empty($primaryO4)) {
        $isValid = false;
        $langLabels = ['en' => 'English', 'hi' => 'Hindi', 'te' => 'Telugu'];
        $error = 'Question Text and all 4 Option fields in ' . ($langLabels[$primaryLang] ?? strtoupper($primaryLang)) . ' (primary set language) are required.';
    }

    if ($isValid) {
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
            header('Location: questions.php?quiz_id=' . $quizId . '&message=' . urlencode('New question added!'));
            exit();
        }
    }
}

// Fetch Existing Questions List
$qListStmt = $pdo->prepare("SELECT * FROM " . tbl('questions') . " WHERE quiz_id = ? ORDER BY question_num ASC");
$qListStmt->execute([$quizId]);
$questionsList = $qListStmt->fetchAll();

$quizTitle = !empty($quiz['title_' . $primaryLang]) ? $quiz['title_' . $primaryLang] : (!empty($quiz['title_en']) ? $quiz['title_en'] : (!empty($quiz['title_hi']) ? $quiz['title_hi'] : $quiz['title_te']));

$pageTitle = 'Manage Questions - ' . $quizTitle;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fa-solid fa-list-check" style="color: var(--bhel-gold);"></i> Question Builder</h2>
        <p>Quiz: <strong><?= sanitize($quizTitle) ?></strong> (Languages Set: <span class="badge badge-info"><i class="fa-solid fa-language"></i> <?= strtoupper(implode(', ', $quizLangs)) ?></span> | Total Questions: <?= count($questionsList) ?>)</p>
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

<!-- Bulk Excel / CSV Upload Card -->
<div class="card" style="margin-bottom: 25px; border-left: 4px solid var(--status-active);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
        <div>
            <h3 style="font-size: 17px; color: #FFF; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-file-excel" style="color: var(--status-active);"></i> Bulk Upload Questions via Excel / CSV
            </h3>
            <p style="font-size: 13px; color: var(--text-secondary);">
                Upload multiple questions, choices (A/B/C/D), correct options, and explanations at once using a CSV file.
            </p>
        </div>
        <div>
            <a href="questions.php?quiz_id=<?= $quizId ?>&action=download_template" class="btn btn-outline" style="font-size: 13px;">
                <i class="fa-solid fa-download" style="color: var(--bhel-gold);"></i> Download Sample CSV Template
            </a>
        </div>
    </div>

    <form method="POST" action="questions.php?quiz_id=<?= $quizId ?>" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
        <input type="hidden" name="action" value="bulk_upload">
        <input type="file" name="csv_file" accept=".csv, text/csv, text/plain" class="form-control" style="max-width: 420px; padding: 8px 12px;" required>
        <button type="submit" class="btn btn-success" style="padding: 10px 20px;">
            <i class="fa-solid fa-cloud-arrow-up"></i> Upload & Import Questions Now
        </button>
    </form>
</div>

<!-- Question Form Card -->
<div class="card">
    <h3 style="font-size: 18px; color: #FFF; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
        <span><i class="fa-solid fa-plus-circle" style="color: var(--bhel-blue-accent);"></i> <?= $editQuestionId > 0 ? 'Edit Question #' . $qData['question_num'] : 'Add New Question' ?></span>
        <?php if ($editQuestionId > 0): ?>
            <a href="questions.php?quiz_id=<?= $quizId ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 12px;">
                <i class="fa-solid fa-plus"></i> Add New Question
            </a>
        <?php endif; ?>
    </h3>

    <form method="POST" action="questions.php?quiz_id=<?= $quizId ?><?= $editQuestionId > 0 ? '&edit_id=' . $editQuestionId : '' ?>">
        <input type="hidden" name="action" value="save_question">

        <!-- Language Tabs Header (Render only enabled languages for this quiz in configured order) -->
        <div class="tab-header">
            <?php 
            $langNames = ['en' => 'English Content', 'hi' => 'हिन्दी (Hindi) Content', 'te' => 'తెలుగు (Telugu) Content'];
            foreach ($quizLangs as $idx => $lCode): 
            ?>
                <button type="button" class="tab-link <?= $idx === 0 ? 'active' : '' ?>" onclick="openLangTab(event, 'tab-<?= $lCode ?>')">
                    <i class="fa-solid fa-language"></i> <?= $langNames[$lCode] ?? strtoupper($lCode) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- TABS CONTENT FOR CONFIGURED QUIZ LANGUAGES -->
        <?php foreach ($quizLangs as $idx => $lCode): 
            $isActive = ($idx === 0);
            $lName = ['en' => 'English', 'hi' => 'हिन्दी - Hindi', 'te' => 'తెలుగు - Telugu'][$lCode] ?? strtoupper($lCode);
            $phQ = ['en' => 'Enter question in English', 'hi' => 'हिन्दी में प्रश्न दर्ज करें', 'te' => 'తెలుగులో ప్రశ్నను ఎంటర్ చేయండి'][$lCode] ?? 'Enter question';
            $phExp = ['en' => 'Explanation for correct answer displayed on scorecard', 'hi' => 'उत्तर का विवरण', 'te' => 'సరైన సమాధానం వివరణ'][$lCode] ?? 'Explanation';
            $req = ($lCode === $primaryLang) ? 'required' : '';
        ?>
            <div id="tab-<?= $lCode ?>" class="tab-content <?= $isActive ? 'active' : '' ?>">
                <div class="form-group">
                    <label>Question Text (<?= $lName ?>) <?= $req ? '*' : '' ?></label>
                    <textarea name="question_<?= $lCode ?>" class="form-control" rows="2" <?= $req ?> placeholder="<?= $phQ ?>"><?= sanitize($qData['question_' . $lCode]) ?></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Option 1 (A - <?= $lName ?>) <?= $req ? '*' : '' ?></label>
                        <input type="text" name="option_1_<?= $lCode ?>" class="form-control" value="<?= sanitize($qData['option_1_' . $lCode]) ?>" <?= $req ?>>
                    </div>

                    <div class="form-group">
                        <label>Option 2 (B - <?= $lName ?>) <?= $req ? '*' : '' ?></label>
                        <input type="text" name="option_2_<?= $lCode ?>" class="form-control" value="<?= sanitize($qData['option_2_' . $lCode]) ?>" <?= $req ?>>
                    </div>

                    <div class="form-group">
                        <label>Option 3 (C - <?= $lName ?>) <?= $req ? '*' : '' ?></label>
                        <input type="text" name="option_3_<?= $lCode ?>" class="form-control" value="<?= sanitize($qData['option_3_' . $lCode]) ?>" <?= $req ?>>
                    </div>

                    <div class="form-group">
                        <label>Option 4 (D - <?= $lName ?>) <?= $req ? '*' : '' ?></label>
                        <input type="text" name="option_4_<?= $lCode ?>" class="form-control" value="<?= sanitize($qData['option_4_' . $lCode]) ?>" <?= $req ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label>Answer Explanation (<?= $lName ?> Optional)</label>
                    <textarea name="explanation_<?= $lCode ?>" class="form-control" rows="2" placeholder="<?= $phExp ?>"><?= sanitize($qData['explanation_' . $lCode]) ?></textarea>
                </div>
            </div>
        <?php endforeach; ?>

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
            <?php foreach ($questionsList as $idx => $q): 
                $primaryQText = !empty($q["question_{$primaryLang}"]) ? $q["question_{$primaryLang}"] : (!empty($q['question_en']) ? $q['question_en'] : (!empty($q['question_hi']) ? $q['question_hi'] : $q['question_te']));
                $opt1Text = !empty($q["option_1_{$primaryLang}"]) ? $q["option_1_{$primaryLang}"] : (!empty($q['option_1_en']) ? $q['option_1_en'] : (!empty($q['option_1_hi']) ? $q['option_1_hi'] : $q['option_1_te']));
                $opt2Text = !empty($q["option_2_{$primaryLang}"]) ? $q["option_2_{$primaryLang}"] : (!empty($q['option_2_en']) ? $q['option_2_en'] : (!empty($q['option_2_hi']) ? $q['option_2_hi'] : $q['option_2_te']));
                $opt3Text = !empty($q["option_3_{$primaryLang}"]) ? $q["option_3_{$primaryLang}"] : (!empty($q['option_3_en']) ? $q['option_3_en'] : (!empty($q['option_3_hi']) ? $q['option_3_hi'] : $q['option_3_te']));
                $opt4Text = !empty($q["option_4_{$primaryLang}"]) ? $q["option_4_{$primaryLang}"] : (!empty($q['option_4_en']) ? $q['option_4_en'] : (!empty($q['option_4_hi']) ? $q['option_4_hi'] : $q['option_4_te']));
            ?>
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); border-radius: 10px; padding: 18px; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-weight: 700; color: var(--bhel-blue-accent); font-size: 15px; margin-bottom: 6px;">
                            Q<?= $idx + 1 ?>. <?= sanitize($primaryQText) ?>
                        </div>
                        <?php 
                        $extraTranslations = [];
                        foreach ($quizLangs as $lCode) {
                            if ($lCode !== $primaryLang && !empty($q["question_{$lCode}"])) {
                                $extraTranslations[] = strtoupper($lCode) . ': ' . $q["question_{$lCode}"];
                            }
                        }
                        if (!empty($extraTranslations)): 
                        ?>
                            <div style="font-size: 13px; color: var(--bhel-gold); margin-bottom: 10px;">
                                <?= sanitize(implode(' | ', $extraTranslations)) ?>
                            </div>
                        <?php endif; ?>

                        <div style="font-size: 13px; color: var(--text-secondary); display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px;">
                            <div <?= $q['correct_option'] == 1 ? 'style="color: var(--status-active); font-weight:700;"' : '' ?>>A: <?= sanitize($opt1Text) ?></div>
                            <div <?= $q['correct_option'] == 2 ? 'style="color: var(--status-active); font-weight:700;"' : '' ?>>B: <?= sanitize($opt2Text) ?></div>
                            <div <?= $q['correct_option'] == 3 ? 'style="color: var(--status-active); font-weight:700;"' : '' ?>>C: <?= sanitize($opt3Text) ?></div>
                            <div <?= $q['correct_option'] == 4 ? 'style="color: var(--status-active); font-weight:700;"' : '' ?>>D: <?= sanitize($opt4Text) ?></div>
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
