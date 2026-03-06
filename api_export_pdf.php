<?php
/**
 * Bright Steps – PDF Export API
 * Generates branded PDF reports for child development data.
 * Uses pure PHP – no external libraries required.
 */
session_start();
include 'connection.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$parentId = $_SESSION['id'];
$type = $_GET['type'] ?? 'full-report';
$childId = $_GET['child_id'] ?? null;

// ── Fetch child data ──
if (!$childId) {
    $stmt = $connect->prepare("SELECT child_id FROM child WHERE parent_id = ? ORDER BY child_id ASC LIMIT 1");
    $stmt->execute([$parentId]);
    $childId = $stmt->fetchColumn();
}

if (!$childId) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No child found. Add a child profile first.']);
    exit();
}

// Child info
$stmt = $connect->prepare("SELECT * FROM child WHERE child_id = ? AND parent_id = ?");
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Child not found.']);
    exit();
}

// Parent info
$parentName = ($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '');

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
$stmt->execute([$parentId]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Badges
$stmt = $connect->prepare("SELECT COUNT(*) FROM child_badge WHERE child_id = ?");
$stmt->execute([$childId]);
$badgeCount = (int) $stmt->fetchColumn();

// Points
$stmt = $connect->prepare("SELECT total_points FROM points_wallet WHERE child_id = ? LIMIT 1");
$stmt->execute([$childId]);
$points = $stmt->fetchColumn() ?: 0;

// ── Generate PDF as HTML ──
$today = date('F j, Y');
$reportTitle = 'Child Development Report';
if ($type === 'growth-report')
    $reportTitle = 'Growth Report';
if ($type === 'child-report')
    $reportTitle = 'Child Profile Report';

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

        .header {
            background: linear-gradient(135deg, #6C63FF, #a78bfa);
            color: white;
            padding: 24px 30px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 24px;
            margin-bottom: 16px;
        }

        .info-item {
            display: flex;
            gap: 8px;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            min-width: 110px;
        }

        .info-value {
            font-weight: 500;
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

        tr:hover td {
            background: #fafbfc;
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

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
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

    <div class="header">
        <div>
            <h1>✦ Bright Steps</h1>
            <div style="font-size: 10pt; margin-top: 4px;">
                <?= htmlspecialchars($reportTitle) ?>
            </div>
        </div>
        <div class="meta">
            <div>Generated:
                <?= $today ?>
            </div>
            <div>Parent:
                <?= htmlspecialchars($parentName) ?>
            </div>
        </div>
    </div>

    <!-- Child Profile -->
    <div class="section">
        <div class="section-title">👶 Child Profile</div>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Name:</span><span class="info-value">
                    <?= htmlspecialchars($child['first_name'] . ' ' . $child['last_name']) ?>
                </span></div>
            <div class="info-item"><span class="info-label">Date of Birth:</span><span class="info-value">
                    <?= $birthFormatted ?>
                </span></div>
            <div class="info-item"><span class="info-label">Age:</span><span class="info-value">
                    <?= $ageDisplay ?>
                </span></div>
            <div class="info-item"><span class="info-label">Gender:</span><span class="info-value">
                    <?= htmlspecialchars($child['gender'] ?? 'Not specified') ?>
                </span></div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stat-cards">
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
        <div class="stat-card">
            <div class="value">
                <?= $badgeCount ?>
            </div>
            <div class="label">Badges</div>
        </div>
        <div class="stat-card">
            <div class="value">
                <?= $points ?>
            </div>
            <div class="label">Points</div>
        </div>
    </div>

    <?php if ($type !== 'child-report'): ?>
        <!-- Growth History -->
        <div class="section">
            <div class="section-title">📏 Growth History</div>
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

    <?php if ($type === 'full-report'): ?>
        <!-- Appointments -->
        <div class="section">
            <div class="section-title">📅 Appointment History</div>
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

    <div class="footer">
        <span class="watermark">✦ Bright Steps</span> — AI-Powered Child Development Platform<br>
        This report was generated on
        <?= $today ?>. Consult your pediatrician for medical advice.
    </div>

</body>

</html>
<?php
$html = ob_get_clean();

// Output as HTML for browser print-to-PDF
header('Content-Type: text/html; charset=UTF-8');
echo $html;
// Auto-trigger print dialog
echo '<script>window.onload = function() { window.print(); }</script>';
