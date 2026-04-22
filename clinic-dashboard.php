<?php
// ═══════════════════════════════════════════════════════
// Clinic Dashboard — Backend API Handler
// Handles AJAX requests for Clinic management sections
// ═══════════════════════════════════════════════════════
if (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
    || isset($_GET['ajax'])
) {
    header('Content-Type: application/json');
    require_once 'connection.php';

    $method = $_SERVER['REQUEST_METHOD'];
    $section = $_GET['section'] ?? '';
    $clinic_id = intval($_GET['clinic_id'] ?? 1); // Default to clinic 1

    // ─── SPECIALISTS SECTION ─────────────────────────────
    if ($section === 'specialists') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_specialists') {
                $stmt = $connect->prepare("
                    SELECT
                        s.specialist_id, s.specialization, s.experience_years,
                        u.first_name, u.last_name, u.email, u.status,
                        ROUND(AVG(f.rating), 1) AS avg_rating,
                        COUNT(DISTINCT f.feedback_id) AS review_count,
                        COUNT(DISTINCT a.appointment_id) AS total_appointments,
                        SUM(CASE WHEN a.status IN ('scheduled','confirmed') AND a.scheduled_at >= NOW() THEN 1 ELSE 0 END) AS upcoming_appointments
                    FROM specialist s
                    INNER JOIN users u ON s.specialist_id = u.user_id
                    LEFT JOIN feedback f ON f.specialist_id = s.specialist_id
                    LEFT JOIN appointment a ON a.specialist_id = s.specialist_id
                    WHERE s.clinic_id = :clinic_id
                    GROUP BY s.specialist_id
                    ORDER BY u.first_name ASC
                ");
                $stmt->execute([':clinic_id' => $clinic_id]);
                $specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Overall stats for this clinic
                $stmt2 = $connect->prepare("
                    SELECT
                        COUNT(DISTINCT s.specialist_id) AS total_specialists,
                        COUNT(DISTINCT a.appointment_id) AS total_appointments,
                        ROUND(AVG(f.rating), 1) AS avg_rating
                    FROM specialist s
                    LEFT JOIN appointment a ON a.specialist_id = s.specialist_id
                    LEFT JOIN feedback f ON f.specialist_id = s.specialist_id
                    WHERE s.clinic_id = :clinic_id
                ");
                $stmt2->execute([':clinic_id' => $clinic_id]);
                $stats = $stmt2->fetch(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $specialists, 'stats' => $stats]);
                exit;
            }
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            if ($action === 'add_specialist') {
                $first_name  = trim($input['first_name'] ?? '');
                $last_name   = trim($input['last_name'] ?? '');
                $email       = trim($input['email'] ?? '');
                $password    = trim($input['password'] ?? '');
                $spec        = trim($input['specialization'] ?? '');
                $exp_years   = intval($input['experience_years'] ?? 0);

                if (!$first_name || !$last_name || !$email || !$password || !$spec) {
                    echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
                    exit;
                }

                // Check email uniqueness
                $check = $connect->prepare("SELECT user_id FROM users WHERE email = :email");
                $check->execute([':email' => $email]);
                if ($check->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Email already registered']);
                    exit;
                }

                try {
                    $connect->beginTransaction();
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $connect->prepare("
                        INSERT INTO users (first_name, last_name, email, password, role, status)
                        VALUES (:fn, :ln, :email, :pw, 'specialist', 'active')
                    ");
                    $stmt->execute([':fn' => $first_name, ':ln' => $last_name, ':email' => $email, ':pw' => $hashed]);
                    $user_id = $connect->lastInsertId();

                    $stmt2 = $connect->prepare("
                        INSERT INTO specialist (specialist_id, clinic_id, first_name, last_name, specialization, experience_years)
                        VALUES (:sid, :cid, :fn, :ln, :spec, :exp)
                    ");
                    $stmt2->execute([
                        ':sid'  => $user_id,
                        ':cid'  => $clinic_id,
                        ':fn'   => $first_name,
                        ':ln'   => $last_name,
                        ':spec' => $spec,
                        ':exp'  => $exp_years
                    ]);
                    $connect->commit();
                    echo json_encode(['success' => true, 'specialist_id' => $user_id]);
                } catch (Exception $e) {
                    $connect->rollBack();
                    echo json_encode(['success' => false, 'error' => 'Failed to add specialist']);
                }
                exit;
            }

            if ($action === 'update_specialist_status') {
                $specialist_id = intval($input['specialist_id'] ?? 0);
                $status        = trim($input['status'] ?? '');
                if (!$specialist_id || !in_array($status, ['active', 'inactive'])) {
                    echo json_encode(['success' => false, 'error' => 'specialist_id and valid status required']);
                    exit;
                }
                $stmt = $connect->prepare("UPDATE users SET status = :status WHERE user_id = :uid");
                $stmt->execute([':status' => $status, ':uid' => $specialist_id]);
                echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
                exit;
            }
        }
    }

    // ─── APPOINTMENTS SECTION ────────────────────────────
    if ($section === 'appointments') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_appointments') {
                $status_filter = trim($_GET['status'] ?? '');

                $sql = "
                    SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.comment, a.report,
                           u_parent.first_name AS parent_first_name, u_parent.last_name AS parent_last_name,
                           a.parent_id,
                           u_doc.first_name AS doctor_first_name, u_doc.last_name AS doctor_last_name,
                           s.specialization,
                           ch.first_name AS child_first_name, ch.last_name AS child_last_name
                    FROM appointment a
                    INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                    INNER JOIN users u_parent ON u_parent.user_id = a.parent_id
                    INNER JOIN users u_doc ON u_doc.user_id = a.specialist_id
                    LEFT JOIN child ch ON ch.parent_id = a.parent_id
                    WHERE s.clinic_id = :clinic_id
                ";
                $params = [':clinic_id' => $clinic_id];

                if ($status_filter) {
                    $sql .= " AND a.status = :status";
                    $params[':status'] = $status_filter;
                }
                $sql .= " GROUP BY a.appointment_id ORDER BY a.scheduled_at DESC LIMIT 100";

                $stmt = $connect->prepare($sql);
                $stmt->execute($params);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Summary counts
                $stmt2 = $connect->prepare("
                    SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN a.status IN ('scheduled','confirmed') AND a.scheduled_at >= NOW() THEN 1 ELSE 0 END) AS upcoming,
                        SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                        SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                        SUM(CASE WHEN DATE(a.scheduled_at) = CURDATE() THEN 1 ELSE 0 END) AS today
                    FROM appointment a
                    INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                    WHERE s.clinic_id = :clinic_id
                ");
                $stmt2->execute([':clinic_id' => $clinic_id]);
                $counts = $stmt2->fetch(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $appointments, 'counts' => $counts]);
                exit;
            }

            if ($action === 'update_appointment') {
                $appointment_id = intval($_GET['appointment_id'] ?? 0);
                $status         = trim($_GET['status'] ?? '');
                if (!$appointment_id || !$status) {
                    echo json_encode(['success' => false, 'error' => 'appointment_id and status required']);
                    exit;
                }
                $stmt = $connect->prepare("UPDATE appointment SET status = :status WHERE appointment_id = :aid");
                $stmt->execute([':status' => $status, ':aid' => $appointment_id]);
                echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
                exit;
            }
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            if ($action === 'update_appointment') {
                $appointment_id = intval($input['appointment_id'] ?? 0);
                $status         = trim($input['status'] ?? '');
                if (!$appointment_id) {
                    echo json_encode(['success' => false, 'error' => 'appointment_id required']);
                    exit;
                }
                $fields = [];
                $params = [':aid' => $appointment_id];
                if ($status) { $fields[] = "status = :status"; $params[':status'] = $status; }
                if (empty($fields)) {
                    echo json_encode(['success' => false, 'error' => 'No fields to update']);
                    exit;
                }
                $stmt = $connect->prepare("UPDATE appointment SET " . implode(', ', $fields) . " WHERE appointment_id = :aid");
                $stmt->execute($params);
                echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
                exit;
            }
        }
    }

    // ─── PATIENTS SECTION ────────────────────────────────
    if ($section === 'patients') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';
            $query  = trim($_GET['query'] ?? '');

            if ($action === 'get_patients') {
                $search = '%' . $query . '%';

                $sql = "
                    SELECT
                        c.child_id,
                        c.first_name AS child_first_name,
                        c.last_name  AS child_last_name,
                        c.gender, c.birth_year, c.birth_month, c.birth_day,
                        u_p.first_name AS parent_first_name,
                        u_p.last_name  AS parent_last_name,
                        p.parent_id,
                        MAX(a.scheduled_at) AS last_appointment_date,
                        SUBSTRING_INDEX(GROUP_CONCAT(a.status ORDER BY a.scheduled_at DESC), ',', 1) AS last_status,
                        SUBSTRING_INDEX(GROUP_CONCAT(u_d.first_name ORDER BY a.scheduled_at DESC), ',', 1) AS assigned_doctor_first,
                        SUBSTRING_INDEX(GROUP_CONCAT(u_d.last_name  ORDER BY a.scheduled_at DESC), ',', 1) AS assigned_doctor_last
                    FROM appointment a
                    INNER JOIN specialist s  ON a.specialist_id = s.specialist_id
                    INNER JOIN users u_d     ON u_d.user_id = s.specialist_id
                    INNER JOIN parent p      ON a.parent_id = p.parent_id
                    INNER JOIN users u_p     ON u_p.user_id = p.parent_id
                    INNER JOIN child c       ON c.parent_id = p.parent_id
                    WHERE s.clinic_id = :clinic_id
                ";
                $params = [':clinic_id' => $clinic_id];

                if ($query) {
                    $sql .= " AND (CONCAT(c.first_name,' ',c.last_name) LIKE :q OR CONCAT(u_p.first_name,' ',u_p.last_name) LIKE :q2)";
                    $params[':q']  = $search;
                    $params[':q2'] = $search;
                }

                $sql .= " GROUP BY c.child_id ORDER BY last_appointment_date DESC LIMIT 100";

                $stmt = $connect->prepare($sql);
                $stmt->execute($params);
                $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $patients, 'count' => count($patients)]);
                exit;
            }
        }
    }

    // ─── REVENUE SECTION ─────────────────────────────────
    if ($section === 'revenue') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_revenue') {
                // Revenue from subscriptions paid by parents who have appointments at this clinic
                $stmt = $connect->prepare("
                    SELECT
                        sub.plan_name,
                        sub.price,
                        COUNT(DISTINCT ps.parent_id) AS subscriber_count,
                        SUM(sub.price) AS plan_revenue
                    FROM parent_subscription ps
                    INNER JOIN subscription sub ON ps.subscription_id = sub.subscription_id
                    WHERE ps.parent_id IN (
                        SELECT DISTINCT a.parent_id FROM appointment a
                        INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                        WHERE s.clinic_id = :clinic_id
                    )
                    GROUP BY sub.subscription_id
                    ORDER BY sub.price DESC
                ");
                $stmt->execute([':clinic_id' => $clinic_id]);
                $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Recent payments
                $stmt2 = $connect->prepare("
                    SELECT
                        u.first_name, u.last_name,
                        sub.plan_name, pay.method,
                        pay.amount_post_discount, pay.status, pay.paid_at
                    FROM payment pay
                    INNER JOIN subscription sub ON pay.subscription_id = sub.subscription_id
                    INNER JOIN parent_subscription ps ON ps.subscription_id = sub.subscription_id
                    INNER JOIN users u ON u.user_id = ps.parent_id
                    WHERE ps.parent_id IN (
                        SELECT DISTINCT a.parent_id FROM appointment a
                        INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                        WHERE s.clinic_id = :clinic_id
                    )
                    ORDER BY pay.paid_at DESC
                    LIMIT 10
                ");
                $stmt2->execute([':clinic_id' => $clinic_id]);
                $recent_payments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                // Total aggregates
                $stmt3 = $connect->prepare("
                    SELECT
                        COALESCE(SUM(sub.price), 0) AS total_monthly_revenue,
                        COUNT(DISTINCT ps.parent_id) AS total_subscribers,
                        SUM(CASE WHEN sub.price = 0 THEN 1 ELSE 0 END) AS free_users,
                        SUM(CASE WHEN pay.status = 'pending' THEN 1 ELSE 0 END) AS pending_payments
                    FROM parent_subscription ps
                    INNER JOIN subscription sub ON ps.subscription_id = sub.subscription_id
                    LEFT JOIN payment pay ON pay.subscription_id = sub.subscription_id
                    WHERE ps.parent_id IN (
                        SELECT DISTINCT a.parent_id FROM appointment a
                        INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                        WHERE s.clinic_id = :clinic_id
                    )
                ");
                $stmt3->execute([':clinic_id' => $clinic_id]);
                $totals = $stmt3->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success'        => true,
                    'breakdown'      => $breakdown,
                    'recent_payments'=> $recent_payments,
                    'totals'         => $totals
                ]);
                exit;
            }
        }
    }

    // ─── REVIEWS SECTION ─────────────────────────────────
    if ($section === 'reviews') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_reviews') {
                $stmt = $connect->prepare("
                    SELECT
                        f.feedback_id, f.content, f.rating, f.submitted_at,
                        u_p.first_name AS parent_first_name, u_p.last_name AS parent_last_name,
                        u_d.first_name AS doctor_first_name, u_d.last_name AS doctor_last_name,
                        s.specialization
                    FROM feedback f
                    INNER JOIN specialist s ON f.specialist_id = s.specialist_id
                    INNER JOIN users u_d ON u_d.user_id = s.specialist_id
                    LEFT JOIN users u_p ON u_p.user_id = f.parent_id
                    WHERE s.clinic_id = :clinic_id
                    ORDER BY f.submitted_at DESC
                    LIMIT 50
                ");
                $stmt->execute([':clinic_id' => $clinic_id]);
                $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Aggregate stats
                $stmt2 = $connect->prepare("
                    SELECT
                        ROUND(AVG(f.rating), 1) AS avg_rating,
                        COUNT(*) AS total_reviews,
                        SUM(CASE WHEN f.rating >= 4 THEN 1 ELSE 0 END) AS positive_count
                    FROM feedback f
                    INNER JOIN specialist s ON f.specialist_id = s.specialist_id
                    WHERE s.clinic_id = :clinic_id
                ");
                $stmt2->execute([':clinic_id' => $clinic_id]);
                $stats = $stmt2->fetch(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $reviews, 'stats' => $stats]);
                exit;
            }
        }
    }

    // ─── OVERVIEW SECTION ────────────────────────────────
    if ($section === 'overview') {

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_overview') {
                // Get all key stats in one query
                $stmt = $connect->prepare("
                    SELECT
                        (SELECT COUNT(*) FROM specialist WHERE clinic_id = :cid) AS total_specialists,
                        (SELECT COUNT(*) FROM specialist s INNER JOIN users u ON s.specialist_id = u.user_id WHERE s.clinic_id = :cid AND u.status = 'active') AS active_specialists,
                        (SELECT COUNT(DISTINCT c.child_id) FROM appointment a INNER JOIN specialist s ON a.specialist_id = s.specialist_id INNER JOIN child c ON c.parent_id = a.parent_id WHERE s.clinic_id = :cid) AS total_patients,
                        (SELECT COUNT(*) FROM appointment a INNER JOIN specialist s ON a.specialist_id = s.specialist_id WHERE s.clinic_id = :cid AND DATE(a.scheduled_at) = CURDATE()) AS today_appointments,
                        (SELECT COUNT(*) FROM appointment a INNER JOIN specialist s ON a.specialist_id = s.specialist_id WHERE s.clinic_id = :cid AND a.status IN ('scheduled','confirmed') AND a.scheduled_at >= NOW()) AS upcoming_appointments,
                        (SELECT ROUND(AVG(f.rating), 1) FROM feedback f INNER JOIN specialist s ON f.specialist_id = s.specialist_id WHERE s.clinic_id = :cid) AS avg_rating,
                        (SELECT COUNT(*) FROM feedback f INNER JOIN specialist s ON f.specialist_id = s.specialist_id WHERE s.clinic_id = :cid AND DATE(f.submitted_at) <= CURDATE() AND DATE(f.submitted_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS reviews_this_month
                ");
                $stmt->execute([':cid' => $clinic_id]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);

                // Revenue calculation
                $stmt2 = $connect->prepare("
                    SELECT COALESCE(SUM(sub.price), 0) AS monthly_revenue
                    FROM parent_subscription ps
                    INNER JOIN subscription sub ON ps.subscription_id = sub.subscription_id
                    WHERE ps.parent_id IN (
                        SELECT DISTINCT a.parent_id FROM appointment a
                        INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                        WHERE s.clinic_id = :cid
                    )
                ");
                $stmt2->execute([':cid' => $clinic_id]);
                $revenue = $stmt2->fetch(PDO::FETCH_ASSOC);
                $stats['monthly_revenue'] = $revenue['monthly_revenue'];

                // Pending actions (appointments needing attention)
                $stmt3 = $connect->prepare("
                    SELECT COUNT(*) AS pending FROM appointment a
                    INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                    WHERE s.clinic_id = :cid AND a.status = 'scheduled' AND a.scheduled_at < NOW()
                ");
                $stmt3->execute([':cid' => $clinic_id]);
                $pending = $stmt3->fetch(PDO::FETCH_ASSOC);
                $stats['pending_actions'] = $pending['pending'];

                // Recent activity (last 5 appointments)
                $stmt4 = $connect->prepare("
                    SELECT a.appointment_id, a.status, a.scheduled_at, a.type,
                           CONCAT(c.first_name, ' ', c.last_name) AS patient_name,
                           CONCAT(u.first_name, ' ', u.last_name) AS doctor_name,
                           s.specialization
                    FROM appointment a
                    INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                    INNER JOIN users u ON u.user_id = s.specialist_id
                    INNER JOIN child c ON c.parent_id = a.parent_id
                    WHERE s.clinic_id = :cid
                    ORDER BY a.scheduled_at DESC LIMIT 5
                ");
                $stmt4->execute([':cid' => $clinic_id]);
                $recent_activity = $stmt4->fetchAll(PDO::FETCH_ASSOC);

                // New reviews (last 3)
                $stmt5 = $connect->prepare("
                    SELECT f.feedback_id, f.rating, f.content, f.submitted_at,
                           CONCAT(u.first_name, ' ', u.last_name) AS parent_name,
                           CONCAT(d.first_name, ' ', d.last_name) AS doctor_name
                    FROM feedback f
                    INNER JOIN specialist s ON f.specialist_id = s.specialist_id
                    INNER JOIN users d ON d.user_id = s.specialist_id
                    LEFT JOIN users u ON u.user_id = f.parent_id
                    WHERE s.clinic_id = :cid
                    ORDER BY f.submitted_at DESC LIMIT 3
                ");
                $stmt5->execute([':cid' => $clinic_id]);
                $new_reviews = $stmt5->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'stats' => $stats,
                    'recent_activity' => $recent_activity,
                    'new_reviews' => $new_reviews
                ]);
                exit;
            }

            if ($action === 'get_appointment_distribution') {
                $stmt = $connect->prepare("
                    SELECT a.status, COUNT(*) AS count
                    FROM appointment a
                    INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                    WHERE s.clinic_id = :cid
                    GROUP BY a.status
                ");
                $stmt->execute([':cid' => $clinic_id]);
                $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $distribution]);
                exit;
            }

            if ($action === 'get_revenue_trend') {
                // Get monthly revenue for last 6 months
                $stmt = $connect->prepare("
                    SELECT
                        DATE_FORMAT(paid_at, '%Y-%m') AS month,
                        SUM(amount_post_discount) AS revenue
                    FROM payment pay
                    INNER JOIN subscription sub ON pay.subscription_id = sub.subscription_id
                    INNER JOIN parent_subscription ps ON ps.subscription_id = sub.subscription_id
                    WHERE ps.parent_id IN (
                        SELECT DISTINCT a.parent_id FROM appointment a
                        INNER JOIN specialist s ON a.specialist_id = s.specialist_id
                        WHERE s.clinic_id = :cid
                    )
                    AND pay.paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    AND pay.status = 'paid'
                    GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
                    ORDER BY month ASC
                ");
                $stmt->execute([':cid' => $clinic_id]);
                $trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $trend]);
                exit;
            }
        }
    }

    // ─── PROFILE SECTION (Public Profile) ──────────────────
    if ($section === 'profile') {
        if ($method === 'GET') {
            $stmt = $connect->prepare("SELECT * FROM clinic WHERE clinic_id = :clinic_id");
            $stmt->execute([':clinic_id' => $clinic_id]);
            $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $clinic]);
            exit;
        }
        if ($method === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'update_profile') {
                $bio = trim($_POST['bio'] ?? '');
                $specialties = trim($_POST['specialties'] ?? '');
                $opening_hours = trim($_POST['opening_hours'] ?? '');
                $website = trim($_POST['website'] ?? '');
                
                $stmt = $connect->prepare("UPDATE clinic SET bio = :bio, specialties = :spec, opening_hours = :hours, website = :web WHERE clinic_id = :cid");
                $stmt->execute([':bio' => $bio, ':spec' => $specialties, ':hours' => $opening_hours, ':web' => $website, ':cid' => $clinic_id]);
                echo json_encode(['success' => true]);
                exit;
            }
            
            if ($action === 'upload_image') {
                $type = $_POST['type'] ?? 'profile';
                if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'error' => 'Upload failed']);
                    exit;
                }
                $file = $_FILES['image'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'clinic_' . $clinic_id . '_' . $type . '_' . time() . '.' . $ext;
                
                // Ensure dir exists
                $upload_dir = 'uploads/clinics/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $col = $type === 'cover' ? 'cover_image' : 'profile_image';
                    $stmt = $connect->prepare("UPDATE clinic SET $col = :path WHERE clinic_id = :cid");
                    $stmt->execute([':path' => $dest, ':cid' => $clinic_id]);
                    echo json_encode(['success' => true, 'path' => $dest]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Could not save file']);
                }
                exit;
            }
        }
    }

    // ─── SETTINGS SECTION ────────────────────────────────
    if ($section === 'settings') {

        if ($method === 'GET') {
            $stmt = $connect->prepare("
                SELECT c.*, GROUP_CONCAT(cp.phone SEPARATOR ',') AS phones
                FROM clinic c
                LEFT JOIN clinic_phone cp ON cp.clinic_id = c.clinic_id
                WHERE c.clinic_id = :clinic_id
                GROUP BY c.clinic_id
            ");
            $stmt->execute([':clinic_id' => $clinic_id]);
            $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $clinic]);
            exit;
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            if ($action === 'update_clinic') {
                $fields = [];
                $params = [':cid' => $clinic_id];
                foreach (['clinic_name', 'email', 'location'] as $f) {
                    if (isset($input[$f])) {
                        $fields[] = "$f = :$f";
                        $params[":$f"] = strip_tags(trim($input[$f]));
                    }
                }
                if (empty($fields)) {
                    echo json_encode(['success' => false, 'error' => 'No fields to update']);
                    exit;
                }
                $stmt = $connect->prepare("UPDATE clinic SET " . implode(', ', $fields) . " WHERE clinic_id = :cid");
                $stmt->execute($params);
                echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
                exit;
            }

            // ── Change Password ─────────────────────────────
            if ($action === 'change_password') {
                $current = trim($input['current_password'] ?? '');
                $new     = trim($input['new_password'] ?? '');

                if (!$current || !$new) {
                    echo json_encode(['success' => false, 'error' => 'Both passwords are required']);
                    exit;
                }
                if (strlen($new) < 8) {
                    echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters']);
                    exit;
                }

                // Get clinic admin user ID
                $stmt = $connect->prepare("
                    SELECT u.user_id, u.password FROM users u
                    INNER JOIN admin a ON u.user_id = a.admin_id
                    INNER JOIN clinic c ON c.admin_id = a.admin_id
                    WHERE c.clinic_id = :cid LIMIT 1
                ");
                $stmt->execute([':cid' => $clinic_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user || !password_verify($current, $user['password'])) {
                    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
                    exit;
                }

                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $connect->prepare("UPDATE users SET password = :pw WHERE user_id = :uid")
                        ->execute([':pw' => $hashed, ':uid' => $user['user_id']]);

                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                exit;
            }
        }
    }

    // Fallback
    echo json_encode(['success' => false, 'error' => 'Invalid section or action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Dashboard - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="styles/doctor.css">
    <link rel="stylesheet" href="styles/clinic.css">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar clinic-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <img src="assets/logo.png" alt="Bright Steps" style="height: 2.5rem; width: auto;">
                </a>
                <div class="user-profile" id="sidebar-profile">
                    <!-- Profile set by JS dynamically -->
                </div>
            </div>

            <nav class="sidebar-nav">
                <button class="nav-item active" data-view="overview">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                    </svg>
                    <span>Overview</span>
                </button>
                <button class="nav-item" data-view="specialists">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span>Specialists</span>
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
                <button class="nav-item" data-view="patients">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <line x1="19" y1="8" x2="19" y2="14" />
                        <line x1="22" y1="11" x2="16" y2="11" />
                    </svg>
                    <span>Patients</span>
                </button>
                <button class="nav-item" data-view="revenue">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23" />
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                    <span>Revenue</span>
                </button>
                <button class="nav-item" data-view="reviews">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    <span>Reviews</span>
                </button>
                <button class="nav-item" data-view="profile">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span>Public Profile</span>
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
        <main class="dashboard-main" id="clinic-main-content">
            <!-- Content loaded by JavaScript -->
        </main>
    </div>

    <!-- Language Toggle -->
    <button class="language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="2" y1="12" x2="22" y2="12" />
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
        </svg>
        عربي
    </button>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/language-toggle.js?v=5"></script>
    <script src="scripts/navigation.js"></script>
    <script src="scripts/clinic-dashboard.js?v=3"></script>
</body>

</html>
