<?php
/**
 * Bright Steps – Clinic Revenue Report Export (Dynamic)
 */
session_start();
include 'connection.php';

// Auth guard
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'clinic') {
    http_response_code(401);
    die("Unauthorized access.");
}

$user_id = intval($_SESSION['id']);
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Resolve clinic_id
$cStmt = $connect->prepare("SELECT clinic_id, clinic_name, email, location FROM clinic WHERE admin_id = ? OR clinic_id = ? LIMIT 1");
$cStmt->execute([$user_id, $user_id]);
$clinic = $cStmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
    die("Clinic profile not found.");
}
$clinic_id = $clinic['clinic_id'];

// ── Fetch Revenue Data ──
try {
    // 1. Transaction History for selected month
    $stmt = $connect->prepare("
        SELECT p.amount_post_discount, p.method, a.scheduled_at as paid_at, 
               s.first_name as spec_fname, s.last_name as spec_lname,
               c.first_name as child_fname, c.last_name as child_lname,
               a.status as appt_status
        FROM appointment a
        JOIN specialist s ON a.specialist_id = s.specialist_id
        LEFT JOIN child c ON a.child_id = c.child_id
        LEFT JOIN payment p ON a.payment_id = p.payment_id
        WHERE s.clinic_id = ? AND MONTH(a.scheduled_at) = ? AND YEAR(a.scheduled_at) = ?
        ORDER BY a.scheduled_at DESC
    ");
    $stmt->execute([$clinic_id, $month, $year]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Aggregate Stats
    $totalRevenue = 0;
    $cashRevenue = 0;
    $creditRevenue = 0;
    $completedCount = 0;
    $pendingCount = 0;
    
    $specBreakdown = [];

    foreach($transactions as $t) {
        $amt = floatval($t['amount_post_discount'] ?? 0);
        $status = strtolower($t['appt_status'] ?? 'pending');
        
        if ($status === 'completed') {
            $totalRevenue += $amt;
            $completedCount++;
            
            if (stripos(($t['method'] ?? ''), 'cash') !== false) {
                $cashRevenue += $amt;
            } else {
                $creditRevenue += $amt;
            }
        } else if ($status === 'pending' || $status === 'scheduled') {
            $pendingCount++;
        }

        $sName = $t['spec_fname'] . ' ' . $t['spec_lname'];
        if (!isset($specBreakdown[$sName])) {
            $specBreakdown[$sName] = ['sessions' => 0, 'revenue' => 0];
        }
        $specBreakdown[$sName]['sessions']++;
        if ($status === 'completed') {
            $specBreakdown[$sName]['revenue'] += $amt;
        }
    }

} catch (Exception $e) {
    die("Report Error: " . $e->getMessage());
}

$monthName = date('F', mktime(0, 0, 0, $month, 10));
$today = date('F j, Y');

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @page { margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: #1e293b; font-size: 10pt; line-height: 1.5; }
        
        .header { background: linear-gradient(135deg, #0d9488, #0f766e); color: white; padding: 35px; border-radius: 12px; margin-bottom: 25px; }
        .header h1 { font-size: 24pt; font-weight: 800; letter-spacing: -1px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; text-align: center; }
        .stat-card .value { font-size: 16pt; font-weight: 800; color: #0d9488; }
        .stat-card .label { font-size: 7.5pt; color: #64748b; text-transform: uppercase; font-weight: 700; margin-top: 4px; }

        .chart-container { margin-bottom: 40px; padding: 20px; border: 1.5px solid #f1f5f9; border-radius: 16px; background: white; }
        
        .section-title { font-size: 13pt; font-weight: 700; color: #0f172a; margin-bottom: 15px; border-left: 4px solid #0d9488; padding-left: 12px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th { background: #f8fafc; color: #475569; font-weight: 700; text-align: left; padding: 12px; font-size: 8.5pt; border-bottom: 2px solid #e2e8f0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 9.5pt; }
        
        .cash-tag { color: #059669; font-weight: 700; }
        .credit-tag { color: #2563eb; font-weight: 700; }
        
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 8pt; }
        @media print {
            .chart-container { page-break-inside: avoid; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Financial Summary</h1>
        <p><?= $monthName ?> <?= $year ?> • <?= htmlspecialchars($clinic['clinic_name']) ?></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="value">$<?= number_format($totalRevenue, 2) ?></div>
            <div class="label">Net Revenue</div>
        </div>
        <div class="stat-card">
            <div class="value">$<?= number_format($cashRevenue, 2) ?></div>
            <div class="label">Cash Total</div>
        </div>
        <div class="stat-card">
            <div class="value">$<?= number_format($creditRevenue, 2) ?></div>
            <div class="label">Credit Total</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= $completedCount ?></div>
            <div class="label">Sessions</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Revenue Trends Visualization</div>
        <div class="chart-container">
            <canvas id="reportChart" height="150"></canvas>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Specialist Performance (Sessions & Earnings)</div>
        <table>
            <thead>
                <tr>
                    <th>Specialist Name</th>
                    <th>Total Sessions</th>
                    <th>Net Revenue Generated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($specBreakdown as $name => $d): ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($name) ?></td>
                    <td><?= $d['sessions'] ?></td>
                    <td style="font-weight: 700; color: #0d9488;">$<?= number_format($d['revenue'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Detailed Monthly Transaction Ledger</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Patient (Child)</th>
                    <th>Doctor</th>
                    <th>Payment Method</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($transactions as $t): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($t['paid_at'])) ?></td>
                    <td><?= htmlspecialchars(($t['child_fname'] ?? 'Unknown') . ' ' . ($t['child_lname'] ?? '')) ?></td>
                    <td>Dr. <?= htmlspecialchars($t['spec_fname'] . ' ' . $t['spec_lname']) ?></td>
                    <td>
                        <?php if(stripos($t['method'] ?? '', 'cash') !== false): ?>
                            <span class="cash-tag">Cash</span>
                        <?php else: ?>
                            <span class="credit-tag">Credit Card</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 700;">$<?= number_format($t['amount_post_discount'] ?? 0, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <strong>Bright Steps Management System</strong> • Professional Clinical Analytics<br>
        Report generated on <?= $today ?>. Pending Sessions are not included in Net Revenue.
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('reportChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Cash Revenue', 'Credit Revenue', 'Total Net'],
                    datasets: [{
                        label: 'Revenue Summary ($)',
                        data: [<?= $cashRevenue ?>, <?= $creditRevenue ?>, <?= $totalRevenue ?>],
                        backgroundColor: ['#10b981', '#3b82f6', '#0d9488'],
                        borderRadius: 10,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    animation: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { display: false } }
                }
            });
            
            setTimeout(() => { window.print(); }, 1200);
        });
    </script>
</body>
</html>
<?php
$html = ob_get_clean();
echo $html;
?>