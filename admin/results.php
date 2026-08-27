<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Administrator User Responses & Analytics View
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = getDBConnection();
$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

// Fetch all quizzes for selector
$quizzesStmt = $pdo->query("SELECT quiz_id, title_en, title_hi, title_te, languages FROM " . tbl('quizzes') . " ORDER BY created_at DESC");
$allQuizzes = $quizzesStmt->fetchAll();

if ($quizId <= 0 && !empty($allQuizzes)) {
    $quizId = $allQuizzes[0]['quiz_id'];
}

$quiz = null;
$attempts = [];
$questionStats = [];
$totalAttempts = 0;
$passedCount = 0;
$avgScore = 0;
$highestScore = 0;
$enabledLangs = ['en'];
$primaryLang = 'en';
$primaryTitle = '';

if ($quizId > 0) {
    // Fetch Quiz header
    $qStmt = $pdo->prepare("SELECT * FROM " . tbl('quizzes') . " WHERE quiz_id = ?");
    $qStmt->execute([$quizId]);
    $quiz = $qStmt->fetch();

    if ($quiz) {
        $enabledLangs = explode(',', $quiz['languages'] ?? 'en');
        $enabledLangs = array_values(array_filter(array_map('trim', $enabledLangs)));
        if (empty($enabledLangs)) $enabledLangs = ['en'];
        $primaryLang = $enabledLangs[0];

        if ($primaryLang === 'hi' && !empty($quiz['title_hi'])) {
            $primaryTitle = $quiz['title_hi'];
        } elseif ($primaryLang === 'te' && !empty($quiz['title_te'])) {
            $primaryTitle = $quiz['title_te'];
        } elseif (!empty($quiz['title_en'])) {
            $primaryTitle = $quiz['title_en'];
        } else {
            $primaryTitle = !empty($quiz['title_hi']) ? $quiz['title_hi'] : (!empty($quiz['title_te']) ? $quiz['title_te'] : $quiz['title_en']);
        }

        // Fetch completed attempts
        $attStmt = $pdo->prepare("
            SELECT a.*, u.full_name, u.staff_no, u.department 
            FROM " . tbl('attempts') . " a
            JOIN " . tbl('users') . " u ON a.user_id = u.user_id
            WHERE a.quiz_id = ? AND a.status = 'completed'
            ORDER BY a.score_achieved DESC, a.submit_time ASC
        ");
        $attStmt->execute([$quizId]);
        $attempts = $attStmt->fetchAll();

        $totalAttempts = count($attempts);
        if ($totalAttempts > 0) {
            $sumScore = 0;
            foreach ($attempts as $att) {
                $sumScore += $att['score_achieved'];
                if ($att['score_achieved'] > $highestScore) {
                    $highestScore = $att['score_achieved'];
                }
                $pct = $att['total_marks'] > 0 ? ($att['score_achieved'] / $att['total_marks']) * 100 : 0;
                if ($pct >= (float)$quiz['pass_percentage']) {
                    $passedCount++;
                }
            }
            $avgScore = $sumScore / $totalAttempts;
        }

        // Fetch Question Accuracy Stats with Trilingual Question Text
        $qsStmt = $pdo->prepare("
            SELECT q.question_id, q.question_num, q.question_en, q.question_hi, q.question_te, q.correct_option,
                   COUNT(r.response_id) as total_responses,
                   SUM(CASE WHEN r.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                   SUM(CASE WHEN r.selected_option = 1 THEN 1 ELSE 0 END) as opt1_count,
                   SUM(CASE WHEN r.selected_option = 2 THEN 1 ELSE 0 END) as opt2_count,
                   SUM(CASE WHEN r.selected_option = 3 THEN 1 ELSE 0 END) as opt3_count,
                   SUM(CASE WHEN r.selected_option = 4 THEN 1 ELSE 0 END) as opt4_count
            FROM " . tbl('questions') . " q
            LEFT JOIN " . tbl('attempt_responses') . " r ON q.question_id = r.question_id
            WHERE q.quiz_id = ?
            GROUP BY q.question_id
            ORDER BY q.question_num ASC
        ");
        $qsStmt->execute([$quizId]);
        $questionStats = $qsStmt->fetchAll();

        // Handle Excel / CSV Export Download
        if (isset($_GET['export']) && ($_GET['export'] === 'excel' || $_GET['export'] === 'csv')) {
            $filename = 'BHEL_HPVP_Quiz_Results_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $primaryTitle) . '_' . date('Ymd_His') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Excel UTF-8 Compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Summary Metadata Rows
            fputcsv($output, ['BHEL-HPVP Visakhapatnam - Quiz Attempt Performance Report']);
            fputcsv($output, ['Quiz Title', $primaryTitle]);
            fputcsv($output, ['Export Date & Time', date('d M Y, h:i A')]);
            fputcsv($output, ['Total Participants', $totalAttempts]);
            fputcsv($output, ['Overall Pass Rate', ($totalAttempts > 0 ? round(($passedCount / $totalAttempts) * 100, 1) : 0) . '%']);
            fputcsv($output, []); // Empty line

            // Header Row
            fputcsv($output, [
                'Rank',
                'Staff Number',
                'Employee Name',
                'Department',
                'Submitted Date & Time',
                'Score Achieved',
                'Total Marks',
                'Accuracy (%)',
                'Correct Count',
                'Wrong Count',
                'Unattempted Count',
                'Result Status'
            ]);

            // Data Rows
            foreach ($attempts as $rank => $att) {
                $pct = $att['total_marks'] > 0 ? round(($att['score_achieved'] / $att['total_marks']) * 100, 1) : 0;
                $passed = $pct >= (float)$quiz['pass_percentage'];

                fputcsv($output, [
                    $rank + 1,
                    $att['staff_no'],
                    $att['full_name'],
                    $att['department'],
                    format_datetime($att['submit_time']),
                    number_format($att['score_achieved'], 2),
                    number_format($att['total_marks'], 2),
                    $pct . '%',
                    $att['correct_answers'],
                    $att['wrong_answers'],
                    $att['unattempted'],
                    $passed ? 'PASSED' : 'FAILED'
                ]);
            }

            fclose($output);
            exit();
        }
    }
}

$pageTitle = 'Quiz Analytics & User Responses';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fa-solid fa-chart-column" style="color: var(--bhel-gold);"></i> Quiz Responses & Performance Analytics</h2>
        <p>Comprehensive user stats, passing metrics, and question-level accuracy breakdown</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Admin Dashboard</a>
    </div>
</div>

<!-- Select Quiz Dropdown Card -->
<div class="card" style="padding: 15px 25px; margin-bottom: 25px;">
    <form method="GET" action="results.php" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
        <label style="font-weight: 700; color: #FFF;"><i class="fa-solid fa-filter"></i> Select Quiz to View Stats:</label>
        <select name="quiz_id" class="form-control" style="max-width: 450px;" onchange="this.form.submit()">
            <?php foreach ($allQuizzes as $qItem): 
                $qL = explode(',', $qItem['languages'] ?? 'en');
                $pL = trim($qL[0]);
                $qTitle = !empty($qItem['title_' . $pL]) ? $qItem['title_' . $pL] : (!empty($qItem['title_en']) ? $qItem['title_en'] : (!empty($qItem['title_hi']) ? $qItem['title_hi'] : $qItem['title_te']));
            ?>
                <option value="<?= $qItem['quiz_id'] ?>" <?= $qItem['quiz_id'] == $quizId ? 'selected' : '' ?>>
                    <?= sanitize($qTitle) ?> [<?= strtoupper($qItem['languages']) ?>]
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($quiz): ?>
    <!-- Analytics Metric Cards -->
    <div class="grid-4" style="margin-bottom: 30px;">
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(0, 210, 255, 0.1); color: var(--bhel-blue-accent);">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalAttempts ?></h3>
                <p>Total Employee Participants</p>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--status-active);">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalAttempts > 0 ? round(($passedCount / $totalAttempts) * 100, 1) : 0 ?>%</h3>
                <p>Overall Pass Rate (<?= $passedCount ?> Passed)</p>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: var(--bhel-gold);">
                <i class="fa-solid fa-calculator"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($avgScore, 2) ?></h3>
                <p>Average Score Achieved</p>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: var(--status-review);">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($highestScore, 2) ?></h3>
                <p>Highest Score Achieved</p>
            </div>
        </div>
    </div>

    <!-- User Responses Leaderboard & Roster -->
    <div class="card" style="margin-bottom: 30px;">
        <h3 style="font-size: 18px; color: #FFF; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <span><i class="fa-solid fa-list-ol" style="color: var(--bhel-blue-accent);"></i> Employee Quiz Attempts Roster</span>
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="results.php?quiz_id=<?= $quizId ?>&export=excel" class="btn btn-success" style="padding: 6px 14px; font-size: 13px;">
                    <i class="fa-solid fa-file-excel"></i> Export to Excel (CSV)
                </a>
                <span style="font-size: 13px; font-weight: normal; color: var(--text-secondary);">Sorted by Rank & Score</span>
            </div>
        </h3>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Staff No</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Submitted At</th>
                        <th>Score Achieved</th>
                        <th>Accuracy</th>
                        <th>Result Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attempts)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No employee attempt records found for this quiz yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attempts as $rank => $att): 
                            $pct = $att['total_marks'] > 0 ? round(($att['score_achieved'] / $att['total_marks']) * 100, 1) : 0;
                            $passed = $pct >= (float)$quiz['pass_percentage'];
                        ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--bhel-gold);">#<?= $rank + 1 ?></td>
                                <td><code><?= sanitize($att['staff_no']) ?></code></td>
                                <td style="font-weight: 600; color: #FFF;"><?= sanitize($att['full_name']) ?></td>
                                <td><?= sanitize($att['department']) ?></td>
                                <td style="font-size: 12px;"><?= format_datetime($att['submit_time']) ?></td>
                                <td>
                                    <strong style="color: var(--bhel-gold);"><?= number_format($att['score_achieved'], 2) ?></strong> / <?= number_format($att['total_marks'], 2) ?>
                                </td>
                                <td><?= $pct ?>%</td>
                                <td>
                                    <span class="badge <?= $passed ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $passed ? 'PASSED' : 'FAILED' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../quiz_result.php?attempt_id=<?= $att['attempt_id'] ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 12px;">
                                        <i class="fa-solid fa-eye"></i> View Response Sheet
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Question-by-Question Accuracy Breakdown Card -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <h3 style="font-size: 18px; color: #FFF; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-chart-line" style="color: var(--bhel-gold);"></i> Question-by-Question Accuracy Breakdown
            </h3>

            <!-- Language switcher for accuracy breakdown table (if multiple languages configured for quiz) -->
            <?php if (count($enabledLangs) > 1): ?>
                <div class="lang-switcher-bar" style="margin-bottom: 0;">
                    <?php 
                    $langLabels = ['en' => 'English', 'hi' => 'हिन्दी', 'te' => 'తెలుగు'];
                    foreach ($enabledLangs as $idx => $lCode): 
                    ?>
                        <button type="button" class="lang-btn <?= $lCode === $primaryLang ? 'active' : '' ?>" onclick="switchBreakdownLang('<?= $lCode ?>', this)">
                            <i class="fa-solid fa-language"></i> <?= $langLabels[$lCode] ?? strtoupper($lCode) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Q#</th>
                        <th>Question Text</th>
                        <th style="width: 140px;">Correct Option</th>
                        <th style="width: 130px;">Total Responses</th>
                        <th style="width: 130px;">Correct Rate %</th>
                        <th style="width: 200px;">Choice Breakdown (A / B / C / D)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questionStats as $qs): 
                        $totalR = (int)$qs['total_responses'];
                        $accRate = $totalR > 0 ? round(($qs['correct_count'] / $totalR) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--bhel-blue-accent);">Q<?= $qs['question_num'] ?></td>
                            <td style="max-width: 380px;">
                                <?php foreach ($enabledLangs as $lCode): 
                                    $qText = !empty($qs["question_{$lCode}"]) ? $qs["question_{$lCode}"] : (!empty($qs['question_en']) ? $qs['question_en'] : (!empty($qs['question_hi']) ? $qs['question_hi'] : $qs['question_te']));
                                    $displayStyle = ($lCode === $primaryLang) ? 'display: block;' : 'display: none;';
                                ?>
                                    <div class="q-breakdown-lang q-bd-<?= $lCode ?>" style="<?= $displayStyle ?>">
                                        <?= sanitize($qText) ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td><span class="badge badge-success">Option <?= $qs['correct_option'] ?> (<?= chr(64 + $qs['correct_option']) ?>)</span></td>
                            <td><?= $totalR ?></td>
                            <td>
                                <strong style="color: <?= $accRate >= 50 ? '#6EE7B7' : '#FCA5A5' ?>;"><?= $accRate ?>%</strong>
                            </td>
                            <td style="font-size: 12px;">
                                <span style="margin-right: 8px;">A: <strong><?= (int)$qs['opt1_count'] ?></strong></span>
                                <span style="margin-right: 8px;">B: <strong><?= (int)$qs['opt2_count'] ?></strong></span>
                                <span style="margin-right: 8px;">C: <strong><?= (int)$qs['opt3_count'] ?></strong></span>
                                <span>D: <strong><?= (int)$qs['opt4_count'] ?></strong></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function switchBreakdownLang(lang, btn) {
        document.querySelectorAll('.q-breakdown-lang').forEach(el => {
            if (el.classList.contains('q-bd-' + lang)) {
                el.style.display = 'block';
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
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
