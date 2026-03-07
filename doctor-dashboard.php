<?php
// ═══════════════════════════════════════════════════════
// Doctor Dashboard — Backend API Handler
// Handles AJAX requests for Reports & Messages
// ═══════════════════════════════════════════════════════
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'
    || isset($_GET['ajax'])
) {
    header('Content-Type: application/json');
    require_once 'connection.php';

    $method = $_SERVER['REQUEST_METHOD'];
    $section = $_GET['section'] ?? '';

    // ─── REPORTS SECTION ────────────────────────────────
    if ($section === 'reports') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_shared_reports') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                if (!$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    SELECT 
                        csr.child_id, csr.report,
                        c.first_name AS child_first_name, c.last_name AS child_last_name,
                        c.gender, c.birth_year, c.birth_month,
                        p.parent_id,
                        u.first_name AS parent_first_name, u.last_name AS parent_last_name
                    FROM child_generated_system_report csr
                    JOIN child c ON csr.child_id = c.child_id
                    JOIN parent p ON c.parent_id = p.parent_id
                    JOIN users u ON p.parent_id = u.user_id
                    JOIN appointment a ON a.parent_id = p.parent_id AND a.specialist_id = :sid
                    GROUP BY csr.child_id, csr.report
                    ORDER BY csr.child_id DESC
                ");
                $stmt->execute([':sid' => $specialist_id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;

            } elseif ($action === 'get_doctor_reports') {
                $specialist_id = intval($_GET['specialist_id'] ?? 0);
                if (!$specialist_id) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    SELECT dr.*, c.first_name AS child_first_name, c.last_name AS child_last_name
                    FROM doctor_report dr
                    JOIN child c ON dr.child_id = c.child_id
                    WHERE dr.specialist_id = :sid
                    ORDER BY dr.created_at DESC
                ");
                $stmt->execute([':sid' => $specialist_id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;
            }

        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            if ($action === 'submit_report') {
                $specialist_id = intval($input['specialist_id'] ?? 0);
                $child_id = intval($input['child_id'] ?? 0);
                $child_report = trim($input['child_report'] ?? '');
                $doctor_notes = trim($input['doctor_notes'] ?? '');
                $recommendations = trim($input['recommendations'] ?? '');
                $report_date = trim($input['report_date'] ?? date('Y-m-d'));

                if (!$specialist_id || !$child_id || !$doctor_notes) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id, child_id, and doctor_notes are required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    INSERT INTO doctor_report (specialist_id, child_id, child_report, doctor_notes, recommendations, report_date)
                    VALUES (:sid, :cid, :cr, :notes, :rec, :rdate)
                ");
                $stmt->execute([
                    ':sid' => $specialist_id,
                    ':cid' => $child_id,
                    ':cr' => $child_report,
                    ':notes' => $doctor_notes,
                    ':rec' => $recommendations,
                    ':rdate' => $report_date
                ]);
                echo json_encode(['success' => true, 'doctor_report_id' => $connect->lastInsertId()]);
                exit;
            }
        }
    }

    // ─── MESSAGES SECTION ───────────────────────────────
    if ($section === 'messages') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_conversations') {
                $user_id = intval($_GET['user_id'] ?? 0);
                if (!$user_id) {
                    echo json_encode(['success' => false, 'error' => 'user_id required']);
                    exit;
                }
                // Get latest message per conversation partner
                $stmt = $connect->prepare("
                    SELECT 
                        partner.user_id AS partner_id,
                        partner.first_name AS partner_first_name,
                        partner.last_name  AS partner_last_name,
                        partner.role       AS partner_role,
                        latest.content     AS last_message,
                        latest.sent_at     AS last_message_time,
                        (SELECT COUNT(*) FROM message m2 
                         WHERE m2.sender_id = partner.user_id 
                           AND m2.receiver_id = :uid2 
                           AND m2.is_read = 0) AS unread_count
                    FROM users partner
                    JOIN message latest ON (
                        (latest.sender_id = partner.user_id AND latest.receiver_id = :uid3)
                        OR (latest.sender_id = :uid4 AND latest.receiver_id = partner.user_id)
                    )
                    WHERE partner.user_id != :uid5
                      AND latest.sent_at = (
                          SELECT MAX(m3.sent_at) FROM message m3
                          WHERE (m3.sender_id = :uid6 AND m3.receiver_id = partner.user_id)
                             OR (m3.sender_id = partner.user_id AND m3.receiver_id = :uid7)
                      )
                    GROUP BY partner.user_id
                    ORDER BY latest.sent_at DESC
                ");
                $stmt->execute([
                    ':uid2' => $user_id,
                    ':uid3' => $user_id,
                    ':uid4' => $user_id,
                    ':uid5' => $user_id,
                    ':uid6' => $user_id,
                    ':uid7' => $user_id
                ]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;

            } elseif ($action === 'get_messages') {
                $user_id = intval($_GET['user_id'] ?? 0);
                $partner_id = intval($_GET['partner_id'] ?? 0);
                if (!$user_id || !$partner_id) {
                    echo json_encode(['success' => false, 'error' => 'user_id and partner_id required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    SELECT m.*, u.first_name AS sender_first_name, u.last_name AS sender_last_name
                    FROM message m
                    JOIN users u ON m.sender_id = u.user_id
                    WHERE (m.sender_id = :uid AND m.receiver_id = :pid)
                       OR (m.sender_id = :pid2 AND m.receiver_id = :uid2)
                    ORDER BY m.sent_at ASC
                ");
                $stmt->execute([':uid' => $user_id, ':pid' => $partner_id, ':pid2' => $partner_id, ':uid2' => $user_id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;
            }

        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            if ($action === 'send_message') {
                $sender_id = intval($input['sender_id'] ?? 0);
                $receiver_id = intval($input['receiver_id'] ?? 0);
                $content = trim($input['content'] ?? '');
                $appointment_id = !empty($input['appointment_id']) ? intval($input['appointment_id']) : null;
                $child_id = !empty($input['child_id']) ? intval($input['child_id']) : null;

                if (!$sender_id || !$receiver_id || !$content) {
                    echo json_encode(['success' => false, 'error' => 'sender_id, receiver_id, and content are required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    INSERT INTO message (sender_id, receiver_id, appointment_id, child_id, content)
                    VALUES (:sid, :rid, :aid, :cid, :content)
                ");
                $stmt->execute([
                    ':sid' => $sender_id,
                    ':rid' => $receiver_id,
                    ':aid' => $appointment_id,
                    ':cid' => $child_id,
                    ':content' => $content
                ]);
                echo json_encode(['success' => true, 'message_id' => $connect->lastInsertId()]);
                exit;

            } elseif ($action === 'mark_read') {
                $user_id = intval($input['user_id'] ?? 0);
                $partner_id = intval($input['partner_id'] ?? 0);
                if (!$user_id || !$partner_id) {
                    echo json_encode(['success' => false, 'error' => 'user_id and partner_id required']);
                    exit;
                }
                $stmt = $connect->prepare("
                    UPDATE message SET is_read = 1 
                    WHERE sender_id = :pid AND receiver_id = :uid AND is_read = 0
                ");
                $stmt->execute([':pid' => $partner_id, ':uid' => $user_id]);
                echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
                exit;
            }
        }
    }

    // Fallback for unknown ajax requests
    echo json_encode(['success' => false, 'error' => 'Invalid section or action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="styles/doctor.css">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar doctor-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <img src="assets/logo.png" alt="Bright Steps" style="height: 2.5rem; width: auto;">
                </a>
                <div class="user-profile">
                    <div class="user-avatar doctor-avatar">DS</div>
                    <div class="user-info">
                        <div class="user-name">Dr. Sarah Mitchell</div>
                        <div class="user-badge-text">Pediatrician</div>
                    </div>
                    <div class="verified-badge" title="Verified Provider">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <button class="nav-item active" data-view="patients">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span>My Patients</span>
                </button>
                <button class="nav-item" data-view="reports">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                        <polyline points="10 9 9 9 8 9" />
                    </svg>
                    <span>Reports</span>
                </button>
                <button class="nav-item" data-view="appointments">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    <span>Appointments</span>
                </button>
                <button class="nav-item" data-view="messages">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>Messages</span>
                </button>
                <button class="nav-item" data-view="analytics">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10" />
                        <line x1="12" y1="20" x2="12" y2="4" />
                        <line x1="6" y1="20" x2="6" y2="14" />
                    </svg>
                    <span>Analytics</span>
                </button>
            </nav>

            <div class="sidebar-footer">
                <button class="nav-item" data-view="settings">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                    <span>Settings</span>
                </button>
                <button class="nav-item nav-item-logout" onclick="handleLogout()">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    <span>Log Out</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Welcome, Dr. Mitchell</h1>
                        <p class="dashboard-subtitle">You have 12 patients assigned to your care</p>
                    </div>
                    <div class="header-actions-inline">
                        <button class="btn btn-outline">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            Add Patient
                        </button>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="doctor-stats-grid">
                    <div class="stat-card stat-card-blue">
                        <div class="stat-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                            </svg>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">12</div>
                            <div class="stat-card-label">Active Patients</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-green">
                        <div class="stat-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                <polyline points="22 4 12 14.01 9 11.01" />
                            </svg>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">8</div>
                            <div class="stat-card-label">On Track</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-yellow">
                        <div class="stat-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">3</div>
                            <div class="stat-card-label">Needs Attention</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-purple">
                        <div class="stat-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">5</div>
                            <div class="stat-card-label">This Week</div>
                        </div>
                    </div>
                </div>

                <!-- Patients List -->
                <div class="section-card">
                    <div class="section-card-header">
                        <h2 class="section-heading">Recent Patients</h2>
                        <input type="text" class="search-input" placeholder="Search patients...">
                    </div>
                    <div class="patients-list">
                        <div class="patient-row">
                            <div class="patient-avatar">EJ</div>
                            <div class="patient-info">
                                <div class="patient-name">Emma Johnson</div>
                                <div class="patient-details">15 months • Parent: Sarah Johnson</div>
                            </div>
                            <div class="patient-status status-green">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                On Track
                            </div>
                            <div class="patient-last-update">Updated 2 days ago</div>
                            <button class="btn btn-sm btn-outline">View Report</button>
                        </div>
                        <div class="patient-row">
                            <div class="patient-avatar">LT</div>
                            <div class="patient-info">
                                <div class="patient-name">Liam Thompson</div>
                                <div class="patient-details">18 months • Parent: Michael Thompson</div>
                            </div>
                            <div class="patient-status status-yellow">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" y1="8" x2="12" y2="12" />
                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                </svg>
                                Needs Review
                            </div>
                            <div class="patient-last-update">Updated 5 days ago</div>
                            <button class="btn btn-sm btn-outline">View Report</button>
                        </div>
                        <div class="patient-row">
                            <div class="patient-avatar">OW</div>
                            <div class="patient-info">
                                <div class="patient-name">Olivia Williams</div>
                                <div class="patient-details">12 months • Parent: Jennifer Williams</div>
                            </div>
                            <div class="patient-status status-green">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                On Track
                            </div>
                            <div class="patient-last-update">Updated today</div>
                            <button class="btn btn-sm btn-outline">View Report</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Floating Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5" />
            <path
                d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
    </button>

    <script src="scripts/theme-toggle.js"></script>

    <!-- Language Toggle -->
    <button class="language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="2" y1="12" x2="22" y2="12" />
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
        </svg>
        عربي
    </button>
    <script src="scripts/language-toggle.js?v=5"></script>

    <script src="scripts/navigation.js"></script>
    <script src="scripts/doctor-dashboard.js?v=5"></script>
</body>

</html>