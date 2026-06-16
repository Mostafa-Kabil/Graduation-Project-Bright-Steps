<?php
/**
 * Bright Steps – PDF Export API
 * Generates branded PDF reports for child development data.
 * Supports real PDF download via Dompdf when download=1 is passed.
 */
session_start();
include 'connection.php';

// Disable Dompdf due to missing composer dependencies - using native browser print fallback
$hasDompdf = false;


if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['id'];
$userRole = $_SESSION['role'] ?? 'parent';
$type = $_GET['type'] ?? 'full-report';
$childId = $_GET['child_id'] ?? null;
$appointmentId = $_GET['appointment_id'] ?? null;
$doctorReportId = $_GET['doctor_report_id'] ?? null;

$specialistReportContent = '';
if ($type === 'specialist-report') {
    if ($appointmentId) {
        $stmtAppt = $connect->prepare("SELECT a.child_id, COALESCE(NULLIF(TRIM(a.report), ''), (SELECT CONCAT('Specialist Notes:\n', dr.doctor_notes, '\n\nRecommendations:\n', dr.recommendations) FROM doctor_report dr WHERE dr.specialist_id = a.specialist_id AND dr.child_id = a.child_id ORDER BY dr.report_date DESC LIMIT 1)) AS report FROM appointment a WHERE a.appointment_id = ?");
        $stmtAppt->execute([$appointmentId]);
        $row = $stmtAppt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $specialistReportContent = $row['report'];
            if (!$childId) $childId = $row['child_id'];
        }
    } else if ($doctorReportId) {
        $stmtDr = $connect->prepare("SELECT child_id, CONCAT('Specialist Notes:\n', doctor_notes, '\n\nRecommendations:\n', recommendations) AS report FROM doctor_report WHERE doctor_report_id = ?");
        $stmtDr->execute([$doctorReportId]);
        $row = $stmtDr->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $specialistReportContent = $row['report'];
            if (!$childId) $childId = $row['child_id'];
        }
    } else {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'appointment_id or doctor_report_id is required for specialist-report']);
        exit();
    }
    
    if (!$specialistReportContent) {
        $specialistReportContent = 'No report content available.';
    }
}

// ── Fetch child data ──
if (!$childId && $type !== 'specialist-report') {
    if ($userRole === 'parent') {
        $stmt = $connect->prepare("SELECT child_id FROM child WHERE parent_id = ? ORDER BY child_id ASC LIMIT 1");
        $stmt->execute([$userId]);
        $childId = $stmt->fetchColumn();
    }
}

if (!$childId) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No child found.']);
    exit();
}

// Child info & Authorization
if ($userRole === 'parent') {
    $stmt = $connect->prepare("SELECT * FROM child WHERE child_id = ? AND parent_id = ?");
    $stmt->execute([$childId, $userId]);
} else {
    // Doctor/Specialist/Clinic: check if shared
    $specialistId = $_SESSION['specialist_id'] ?? $_SESSION['id'] ?? null;
    $stmt = $connect->prepare("
        SELECT c.* 
        FROM child c
        JOIN shared_reports sr ON c.child_id = sr.child_id
        WHERE c.child_id = ? AND sr.doctor_id = ? AND sr.is_shared = 1
        LIMIT 1
    ");
    $stmt->execute([$childId, $specialistId]);
}

$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Child not found or you do not have permission to view this report.']);
    exit();
}

// Parent info
$stmt = $connect->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->execute([$child['parent_id']]);
$parentData = $stmt->fetch(PDO::FETCH_ASSOC);
$parentName = $parentData ? ($parentData['first_name'] . ' ' . $parentData['last_name']) : 'Unknown';

// Age
$bd = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
$ageMonths = floor((time() - $bd) / (30.44 * 86400));
$ageDisplay = $ageMonths >= 24 ? floor($ageMonths / 12) . ' years old' : $ageMonths . ' months old';
$birthFormatted = date('M d, Y', $bd);

// Growth records
$stmt = $connect->prepare("SELECT * FROM growth_record WHERE child_id = ? ORDER BY recorded_at DESC LIMIT 10");
$stmt->execute([$childId]);
$growthRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Appointments
$stmt = $connect->prepare(
    "SELECT a.*, s.first_name AS doc_fname, s.last_name AS doc_lname, c.clinic_name
     FROM appointment a
     INNER JOIN specialist s ON a.specialist_id = s.specialist_id
     INNER JOIN clinic c ON s.clinic_id = c.clinic_id
     WHERE a.parent_id = ? ORDER BY a.scheduled_at DESC LIMIT 10"
);
$stmt->execute([$child['parent_id']]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Speech Analysis
$stmt = $connect->prepare("
    SELECT vs.sent_at, sa.transcript, sa.vocabulary_score, sa.clarify_score, vs.feedback as status
    FROM voice_sample vs
    LEFT JOIN speech_analysis sa ON sa.sample_id = vs.sample_id
    WHERE vs.child_id = ? ORDER BY vs.sent_at DESC LIMIT 10
");
$stmt->execute([$childId]);
$speechRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Badges
$stmt = $connect->prepare("SELECT COUNT(*) FROM child_badge WHERE child_id = ?");
$stmt->execute([$childId]);
$badgeCount = (int) $stmt->fetchColumn();

// Streaks
$stmt = $connect->prepare("SELECT current_count FROM streaks WHERE child_id = ? AND streak_type = 'daily_login' LIMIT 1");
$stmt->execute([$childId]);
$streak = $stmt->fetchColumn() ?: 0;

// Points
$stmt = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = ? LIMIT 1");
$stmt->execute([$childId]);
$points = $stmt->fetchColumn() ?: 0;

// Motor Skills
$stmt = $connect->prepare("SELECT milestone_name, category, MAX(is_achieved) AS is_achieved FROM motor_milestones WHERE child_id = ? GROUP BY milestone_name, category ORDER BY category, milestone_name");
$stmt->execute([$childId]);
$motorSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Behavior Checklist
$stmt = $connect->prepare("
    SELECT b.*, bc.category_name, bc.category_type, bc.category_description, ceb.frequency, ceb.severity
    FROM behavior b
    INNER JOIN behavior_category bc ON b.category_id = bc.category_id
    LEFT JOIN child_exhibited_behavior ceb ON b.behavior_id = ceb.behavior_id AND ceb.child_id = ?
    WHERE LOWER(bc.category_name) LIKE '%attention%' OR LOWER(bc.category_name) LIKE '%communication%' OR LOWER(bc.category_name) LIKE '%social%' OR LOWER(bc.category_name) LIKE '%motor%'
    ORDER BY bc.category_type, bc.category_name, b.behavior_details
");
$stmt->execute([$childId]);
$behaviorChecklist = $stmt->fetchAll(PDO::FETCH_ASSOC);



// ── Generate PDF as HTML ──
$today = date('F j, Y');
$reportTitle = 'Child Development Report';
if ($type === 'growth-report')
    $reportTitle = 'Growth Report';
if ($type === 'child-report')
    $reportTitle = 'Child Profile Report';
if ($type === 'speech-report')
    $reportTitle = 'Speech Analysis Report';
if ($type === 'motor-skills-report')
    $reportTitle = 'Motor Skills Report';
if ($type === 'specialist-report')
    $reportTitle = 'Specialist Appointment Report';

ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>
        <?= htmlspecialchars($reportTitle) ?> – Bright Steps
    </title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1e293b;
            font-size: 11pt;
            line-height: 1.6;
        }

        /* ── Header: use table layout for Dompdf ── */
        .header {
            background-color: #6C63FF;
            color: white;
            padding: 24px 30px;
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .header-inner {
            width: 100%;
        }

        .header-left {
            float: left;
            width: 60%;
        }

        .header-right {
            float: right;
            width: 35%;
            text-align: right;
        }

        .header h1 {
            font-size: 22pt;
            font-weight: 800;
        }

        .header .meta {
            text-align: right;
            font-size: 9pt;
            opacity: 0.9;
        }

        .clearfix::after {
            content: "";
            display: block;
            clear: both;
        }

        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 14pt;
            font-weight: 700;
            color: #6C63FF;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        /* ── Info grid: use table layout for Dompdf ── */
        .info-grid {
            width: 100%;
            margin-bottom: 16px;
        }

        .info-row {
            overflow: hidden;
            margin-bottom: 6px;
        }

        .info-item {
            float: left;
            width: 48%;
            margin-right: 2%;
            padding: 2px 0;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            display: inline;
        }

        .info-value {
            font-weight: 500;
            display: inline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            text-align: left;
            padding: 10px 12px;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10pt;
        }

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: 600;
        }

        .badge-green {
            background: #dcfce7;
            color: #166534;
        }

        .badge-yellow {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-red {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ── Stat cards: use table layout for Dompdf ── */
        .stat-cards {
            width: 100%;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .stat-card {
            float: left;
            width: 48%;
            margin-right: 2%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
        }

        .stat-card:last-child {
            margin-right: 0;
        }

        .stat-card .value {
            font-size: 18pt;
            font-weight: 800;
            color: #6C63FF;
        }

        .stat-card .label {
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 8pt;
        }

        .watermark {
            color: #6C63FF;
            font-weight: 600;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <div class="header clearfix">
        <div class="header-inner">
            <div class="header-left">
                <h1>Bright Steps</h1>
                <div style="font-size: 10pt; margin-top: 4px;">
                    <?= htmlspecialchars($reportTitle) ?>
                </div>
            </div>
            <div class="header-right">
                <div class="meta">
                    <div>Generated: <?= $today ?></div>
                    <div>Parent: <?= htmlspecialchars($parentName) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Child Profile -->
    <div class="section">
        <div class="section-title">Child Profile</div>
        <div class="info-grid">
            <div class="info-row clearfix">
                <div class="info-item">
                    <span class="info-label">Name: </span>
                    <span class="info-value"><?= htmlspecialchars($child['first_name'] . ' ' . $child['last_name']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date of Birth: </span>
                    <span class="info-value"><?= $birthFormatted ?></span>
                </div>
            </div>
            <div class="info-row clearfix" style="margin-top: 6px;">
                <div class="info-item">
                    <span class="info-label">Age: </span>
                    <span class="info-value"><?= $ageDisplay ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Gender: </span>
                    <span class="info-value"><?= htmlspecialchars($child['gender'] ?? 'Not specified') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stat-cards clearfix">
        <div class="stat-card">
            <div class="value">
                <?= $growthRecords ? htmlspecialchars($growthRecords[0]['weight'] ?? '—') : '—' ?>
            </div>
            <div class="label">Weight (kg)</div>
        </div>
        <div class="stat-card">
            <div class="value">
                <?= $growthRecords ? htmlspecialchars($growthRecords[0]['height'] ?? '—') : '—' ?>
            </div>
            <div class="label">Height (cm)</div>
        </div>
    </div>

    <?php if ($type === 'specialist-report'): ?>
        <div class="section">
            <div class="section-title">Specialist Report</div>
            <div style="white-space: pre-wrap; font-size: 11pt; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <?= htmlspecialchars($specialistReportContent) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (in_array($type, ['full-report', 'growth-report'])): ?>
        <!-- Growth History -->
        <div class="section">
            <div class="section-title">Growth History</div>
            <?php if (count($growthRecords) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Weight (kg)</th>
                            <th>Height (cm)</th>
                            <th>Head Circ. (cm)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($growthRecords as $g): ?>
                            <tr>
                                <td>
                                    <?= date('M d, Y', strtotime($g['recorded_at'])) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($g['weight'] ?? '—') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($g['height'] ?? '—') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($g['head_circumference'] ?? '—') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #94a3b8; padding: 12px;">No growth records found.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (in_array($type, ['full-report', 'child-report'])): ?>
        <!-- Appointment History -->
        <div class="section">
            <div class="section-title">Appointment History</div>
            <?php if (count($appointments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Doctor</th>
                            <th>Clinic</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                            <tr>
                                <td>
                                    <?= date('M d, Y', strtotime($a['scheduled_at'])) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($a['type'] ?? '—') ?>
                                </td>
                                <td>Dr.
                                    <?= htmlspecialchars($a['doc_fname'] . ' ' . $a['doc_lname']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($a['clinic_name'] ?? '—') ?>
                                </td>
                                <td><span class="badge <?= $a['status'] === 'completed' ? 'badge-green' : 'badge-yellow' ?>">
                                        <?= htmlspecialchars($a['status'] ?? 'pending') ?>
                                    </span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #94a3b8; padding: 12px;">No appointments on record.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (in_array($type, ['full-report', 'speech-report'])): ?>
        <!-- Speech Analysis History -->
        <?php 
        $ageMonthsForSpeech = floor((time() - mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year'])) / 2629743);
        $expectedSentence = '1 word';
        $expectedTotal = '10-50 words';
        if ($ageMonthsForSpeech >= 60) { $expectedSentence = '5+ words'; $expectedTotal = '5000+ words'; }
        elseif ($ageMonthsForSpeech >= 48) { $expectedSentence = '4-5 words'; $expectedTotal = '2000+ words'; }
        elseif ($ageMonthsForSpeech >= 36) { $expectedSentence = '3-4 words'; $expectedTotal = '1000+ words'; }
        elseif ($ageMonthsForSpeech >= 24) { $expectedSentence = '2-3 words'; $expectedTotal = '200+ words'; }
        elseif ($ageMonthsForSpeech >= 18) { $expectedSentence = '2 words'; $expectedTotal = '50-100 words'; }
        ?>
        <div class="section">
            <div class="section-title">Speech Analysis History</div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px; page-break-inside: avoid;">
                <strong style="color: #6C63FF; font-size: 10pt;">Age Expectations (<?= $ageMonthsForSpeech ?> months):</strong><br>
                <span style="font-size: 9pt; color: #475569;">Expected words in a single recording: <strong><?= $expectedSentence ?></strong></span><br>
                <span style="font-size: 9pt; color: #475569;">Total expected vocabulary: <strong><?= $expectedTotal ?></strong></span>
            </div>
            <?php if (count($speechRecords) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Vocab Score</th>
                            <th>Clarity</th>
                            <th>Transcript</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($speechRecords as $s): ?>
                            <tr>
                                <td>
                                    <?= date('M d, Y', strtotime($s['sent_at'])) ?>
                                </td>
                                <td><span class="badge <?= (strpos((string)$s['status'], 'Within') !== false || strpos((string)$s['status'], 'Above') !== false) ? 'badge-green' : 'badge-yellow' ?>">
                                        <?= htmlspecialchars($s['status'] ?: 'Unknown') ?>
                                    </span></td>
                                <td>
                                    <?= htmlspecialchars($s['vocabulary_score'] ? round((float)$s['vocabulary_score']) : '—') ?> words
                                </td>
                                <td>
                                    <?= htmlspecialchars($s['clarify_score'] ? (round((float)$s['clarify_score'] * 100) . '%') : '—') ?>
                                </td>
                                <td style="font-style: italic; color: #475569; max-width: 200px;">
                                    "<?= htmlspecialchars($s['transcript'] ?: '—') ?>"
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #94a3b8; padding: 12px;">No speech recordings on record.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (in_array($type, ['full-report', 'motor-skills-report'])): ?>
        <!-- Motor Skills History -->
        <div class="section">
            <div class="section-title">Motor Skills History</div>
            <?php if (count($motorSkills) > 0): ?>
                <?php
                $groupedMotor = [];
                foreach ($motorSkills as $m) {
                    $cat = ucwords(str_replace('_', ' ', $m['category']));
                    if (!isset($groupedMotor[$cat])) $groupedMotor[$cat] = [];
                    $groupedMotor[$cat][] = $m;
                }
                ?>
                <div class="motor-grid" style="width: 100%;">
                <?php foreach ($groupedMotor as $cat => $skills): ?>
                    <div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; background: #f8fafc; page-break-inside: avoid;">
                        <h4 style="margin-bottom: 10px; color: #334155; font-size: 11pt; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;"><?= htmlspecialchars($cat) ?></h4>
                        <table style="width: 100%; border: none; margin-top: 0;">
                        <?php foreach ($skills as $skill): ?>
                            <tr>
                                <td style="width: 30px; border: none; padding: 4px 0;">
                                    <div style="width: 16px; height: 16px; border-radius: 4px; border: 2px solid <?= $skill['is_achieved'] ? '#16a34a' : '#cbd5e1' ?>; background: <?= $skill['is_achieved'] ? '#16a34a' : 'white' ?>; text-align: center; color: white; font-size: 12px; line-height: 16px; font-family: sans-serif;">
                                        <?= $skill['is_achieved'] ? '✓' : '' ?>
                                    </div>
                                </td>
                                <td style="border: none; padding: 4px 0; color: <?= $skill['is_achieved'] ? '#94a3b8' : '#334155' ?>; text-decoration: <?= $skill['is_achieved'] ? 'line-through' : 'none' ?>; font-size: 10pt;">
                                    <?= htmlspecialchars($skill['milestone_name']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </table>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #94a3b8; padding: 12px;">No motor milestones on record.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (in_array($type, ['full-report'])): ?>
        <?php
        $behaviorStats = [
            'attention' => ['total' => 0, 'achieved' => 0, 'behaviors' => [], 'label' => 'Attention', 'icon' => '🧠'],
            'communication' => ['total' => 0, 'achieved' => 0, 'behaviors' => [], 'label' => 'Communication', 'icon' => '💬'],
            'social' => ['total' => 0, 'achieved' => 0, 'behaviors' => [], 'label' => 'Social', 'icon' => '🤝'],
            'motor' => ['total' => 0, 'achieved' => 0, 'behaviors' => [], 'label' => 'Motor', 'icon' => '🏃'],
            'fine_motor' => ['total' => 0, 'achieved' => 0, 'behaviors' => [], 'label' => 'Fine Motor', 'icon' => '✍️']
        ];

        $pillarMap = [
            'attention' => 'attention', 'cognitive' => 'attention',
            'communication' => 'communication', 'language' => 'communication',
            'social' => 'social', 'social-emotional' => 'social', 'emotional' => 'social',
            'motor' => 'motor', 'gross_motor' => 'motor',
            'fine_motor' => 'fine_motor',
            'sensory' => 'motor', 'physical' => 'motor'
        ];

        foreach ($behaviorChecklist as $b) {
            $catType = strtolower($b['category_type'] ?? 'motor');
            $pillar = $pillarMap[$catType] ?? 'motor';
            $behaviorStats[$pillar]['total']++;
            if (!empty($b['frequency'])) {
                $behaviorStats[$pillar]['achieved']++;
                $behaviorStats[$pillar]['behaviors'][] = $b['behavior_details'];
            }
        }

        $totalAchieved = 0;
        $totalMilestones = 0;
        foreach ($behaviorStats as $s) {
            $totalAchieved += $s['achieved'];
            $totalMilestones += $s['total'];
        }
        $overallPercent = $totalMilestones > 0 ? round(($totalAchieved / $totalMilestones) * 100) : 0;
        
        $ageExpectations = [
            '0-12' => ['attention' => 3, 'communication' => 4, 'social' => 3, 'motor' => 8, 'fine_motor' => 4],
            '13-24' => ['attention' => 6, 'communication' => 8, 'social' => 6, 'motor' => 12, 'fine_motor' => 8],
            '25-36' => ['attention' => 9, 'communication' => 12, 'social' => 9, 'motor' => 15, 'fine_motor' => 12],
            '37-48' => ['attention' => 12, 'communication' => 15, 'social' => 12, 'motor' => 18, 'fine_motor' => 15]
        ];
        $bd = mktime(0, 0, 0, $child['birth_month'], $child['birth_day'], $child['birth_year']);
        $ageMonthsChecklist = floor((time() - $bd) / 2629743);
        $ageRange = '37-48';
        if ($ageMonthsChecklist <= 12) $ageRange = '0-12';
        elseif ($ageMonthsChecklist <= 24) $ageRange = '13-24';
        elseif ($ageMonthsChecklist <= 36) $ageRange = '25-36';
        $expectations = $ageExpectations[$ageRange];
        ?>
        <div class="section" style="page-break-inside: avoid;">
            <div class="section-title">Developmental Progress (Behavior Checklist)</div>
            
            <div style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border:1px solid #bae6fd;border-radius:12px;padding:15px;margin-bottom:20px;">
                <h3 style="margin:0 0 8px;font-size:11pt;color:#0369a1;">Executive Summary</h3>
                <p style="margin:0;font-size:10pt;color:#0c4a6e;line-height:1.5;">
                    <?= htmlspecialchars($child['first_name']) ?> is <?= $ageMonthsChecklist ?> months old and has been assessed on <?= $totalMilestones ?> developmental milestones.
                    <?= $totalAchieved ?> milestones (<?= $overallPercent ?>%) are currently observed.
                    <?= $overallPercent >= 70 ? 'Development appears to be progressing within expected ranges.' : ($overallPercent >= 40 ? 'Some areas show emerging skills that may benefit from targeted support.' : 'Multiple areas show delays that warrant professional evaluation.') ?>
                </p>
            </div>

            <div style="width: 100%; overflow: hidden;">
                <?php foreach ($behaviorStats as $key => $stat): ?>
                    <?php if ($stat['total'] === 0) continue; ?>
                    <?php 
                        $pct = round(($stat['achieved'] / $stat['total']) * 100);
                        $expected = $expectations[$key] ?? 5;
                        $diff = $expected - $stat['achieved'];
                    ?>
                    <div style="float: left; width: 48%; margin-right: 2%; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; background: #ffffff; page-break-inside: avoid; box-sizing: border-box;">
                        <div style="overflow: hidden; margin-bottom: 10px;">
                            <div style="float: left; font-weight: 700; font-size: 11pt; color: #1e293b;">
                                <?= $stat['icon'] ?> <?= $stat['label'] ?>
                            </div>
                            <div style="float: right;">
                                <div style="width: 16px; height: 16px; border-radius: 50%; background: <?= $pct >= 70 ? '#22c55e' : ($pct >= 40 ? '#eab308' : '#ef4444') ?>;"></div>
                            </div>
                        </div>
                        
                        <div style="overflow: hidden; font-size: 10pt; color: #475569; margin-bottom: 5px;">
                            <div style="float: left;"><?= $stat['achieved'] ?>/<?= $stat['total'] ?> skills observed</div>
                            <div style="float: right; font-weight: 600;"><?= $pct ?>%</div>
                        </div>
                        
                        <div style="font-size: 9pt; font-weight: 600; color: <?= $diff > 0 ? '#ea580c' : '#16a34a' ?>; border-bottom: 1px dashed #e2e8f0; padding-bottom: 10px; margin-bottom: 10px;">
                            <?= $diff > 0 ? "⚠ {$diff} below expectation" : '✓ Age-appropriate' ?>
                        </div>
                        
                        <?php if (count($stat['behaviors']) > 0): ?>
                            <div style="font-size: 9pt; color: #64748b; margin-bottom: 5px;">Observed behaviors:</div>
                            <ul style="margin: 0; padding-left: 15px; font-size: 9pt; color: #475569;">
                                <?php foreach ($stat['behaviors'] as $bh): ?>
                                    <li style="margin-bottom: 3px;"><?= htmlspecialchars($bh) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div style="font-size: 9pt; color: #94a3b8; font-style: italic;">No behaviors observed yet.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="footer">
        <span class="watermark">Bright Steps</span> — AI-Powered Child Development Platform<br>
        This report was generated on
        <?= $today ?>. Consult your pediatrician for medical advice.
    </div>

</body>

</html>
<?php
$html = ob_get_clean();

$isDownload = isset($_GET['download']) && $_GET['download'] == '1';
$isViewOnly = isset($_GET['view']) && $_GET['view'] == '1';

// ── Download mode: generate a real PDF file using Dompdf ──
if ($isDownload && $hasDompdf) {
    // Prevent any PHP warnings or notices from prepending to the PDF stream
    error_reporting(0);
    ini_set('display_errors', 0);

    try {
        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $childName = preg_replace('/[^a-zA-Z0-9_-]/', '_', ($child['first_name'] ?? '') . '_' . ($child['last_name'] ?? ''));
        $fileName = 'BrightSteps_' . ucfirst(str_replace('-', '_', $type)) . '_' . $childName . '_' . date('Y-m-d') . '.pdf';

        // Clear all active output buffers to ensure no leading/trailing junk
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Stream PDF inline so browser's native viewer opens it cleanly without forcing Adobe save dialog
        $dompdf->stream($fileName, ['Attachment' => false]);
        exit;
    } catch (Exception $e) {
        // Fallback to HTML if Dompdf fails
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        echo '<script>window.onload = function() { window.print(); }</script>';
    }
} else {
    // ── View / default mode: output HTML ──
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    if (!$isViewOnly) {
        echo '<script>window.onload = function() { window.print(); }</script>';
    }
}
