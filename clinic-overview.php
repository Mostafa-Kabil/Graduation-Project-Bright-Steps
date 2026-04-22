<?php
// ══════════════════════════════════════════════════════════════
// Clinic Public Overview — Bright Steps
// Accessible publicly: clinic-overview.php?clinic_id=X
// or uses session clinic_id when logged in
// ══════════════════════════════════════════════════════════════
session_start();
require_once 'connection.php';

// Determine clinic ID — prefer query param for public access
$clinic_id = intval($_GET['clinic_id'] ?? $_SESSION['id'] ?? 0);

$clinic     = null;
$specialists = [];

if ($clinic_id && $connect) {
    try {
        // Fetch clinic info
        $stmt = $connect->prepare("
            SELECT c.clinic_id, c.clinic_name, c.location, c.phones, c.status,
                   u.email
            FROM clinic c
            LEFT JOIN users u ON u.user_id = c.clinic_id
            WHERE c.clinic_id = ?
        ");
        $stmt->execute([$clinic_id]);
        $clinic = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch active specialists
        $stmt2 = $connect->prepare("
            SELECT specialist_id, first_name, last_name, specialization,
                   experience_years, certificate_of_experience, status
            FROM specialist
            WHERE clinic_id = ? AND status = 'active'
            ORDER BY specialization ASC
        ");
        $stmt2->execute([$clinic_id]);
        $specialists = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Graceful degradation
    }
}

// Fallback data if clinic not found
if (!$clinic) {
    $clinic = [
        'clinic_name' => 'Bright Steps Clinic',
        'location'    => 'Cairo, Egypt',
        'phones'      => '+20 100 000 0000',
        'email'       => 'contact@brightsteps.com',
    ];
}

$clinic_name     = htmlspecialchars($clinic['clinic_name']  ?? 'Clinic');
$clinic_location = htmlspecialchars($clinic['location']     ?? '—');
$clinic_phone    = htmlspecialchars($clinic['phones']       ?? '—');
$clinic_email    = htmlspecialchars($clinic['email']        ?? '—');

// Make initials
$words   = explode(' ', trim($clinic['clinic_name'] ?? 'C'));
$initials = strtoupper(substr($words[0],0,1) . (isset($words[1]) ? substr($words[1],0,1) : substr($words[0],1,1)));

// Specialist colors
$gradients = [
    'linear-gradient(135deg,#06b6d4,#0891b2)',
    'linear-gradient(135deg,#8b5cf6,#7c3aed)',
    'linear-gradient(135deg,#ec4899,#db2777)',
    'linear-gradient(135deg,#f59e0b,#d97706)',
    'linear-gradient(135deg,#10b981,#059669)',
    'linear-gradient(135deg,#3b82f6,#2563eb)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $clinic_name; ?> — Bright Steps</title>
    <meta name="description" content="Book an appointment at <?php echo $clinic_name; ?> on Bright Steps. Specialized pediatric care for your child's development.">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/clinic.css?v=6">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; }
        .ov-nav {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 65px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .ov-nav-logo { display: flex; align-items: center; gap: 0.65rem; text-decoration: none; }
        .ov-nav-logo img { height: 2rem; }
        .ov-nav-logo span { font-weight: 800; font-size: 1.1rem; color: #0e7490; }
        .ov-nav-links { display: flex; align-items: center; gap: 1rem; }
        .ov-nav-links a {
            font-size: 0.875rem; font-weight: 600; color: #64748b;
            text-decoration: none; transition: color 0.2s;
        }
        .ov-nav-links a:hover { color: #06b6d4; }
        .btn-book-nav {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.55rem 1.25rem;
            background: linear-gradient(135deg, #06b6d4, #0e7490);
            color: white; border-radius: 999px; font-size: 0.875rem;
            font-weight: 700; text-decoration: none; border: none; cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(6,182,212,0.35);
        }
        .btn-book-nav:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(6,182,212,0.45); }
        .btn-book-nav svg { width: 1rem; height: 1rem; }

        /* Sections */
        .ov-section { background: white; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 2rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }

        /* Stats */
        .ov-stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 2rem; }
        .ov-stat-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 1rem;
            padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06); transition: transform .2s, box-shadow .2s;
        }
        .ov-stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.09); }
        .ov-stat-icon { width: 3rem; height: 3rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; }
        .ov-stat-icon svg { width: 1.3rem; height: 1.3rem; }
        .ov-stat-value { font-size: 1.7rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .ov-stat-label { font-size: 0.78rem; color: #64748b; font-weight: 500; margin-top: .2rem; }

        @media (max-width: 768px) {
            .ov-stats-grid { grid-template-columns: 1fr 1fr; }
            .ov-nav-links a:not(.btn-book-nav) { display: none; }
        }
        @media (max-width: 500px) {
            .ov-stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="ov-nav">
        <a href="index.php" class="ov-nav-logo">
            <img src="assets/logo.png" alt="Bright Steps">
            <span>Bright Steps</span>
        </a>
        <div class="ov-nav-links">
            <a href="index.php">Home</a>
            <a href="book-clinic.php">Find Clinics</a>
            <?php if (isset($_SESSION['id']) && $_SESSION['role'] === 'clinic'): ?>
            <a href="dashboards/clinic/clinic-dashboard.php">My Dashboard</a>
            <?php elseif (isset($_SESSION['id'])): ?>
            <a href="dashboard.php">My Account</a>
            <?php endif; ?>
            <a href="book-clinic.php?clinic_id=<?php echo $clinic_id; ?>" class="btn-book-nav">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Book Appointment
            </a>
        </div>
    </nav>

    <!-- Hero Banner -->
    <div style="background:linear-gradient(135deg,#0e7490 0%,#06b6d4 55%,#22d3ee 100%);position:relative;overflow:hidden;">
        <div style="position:absolute;inset:0;background-image:radial-gradient(circle at 20% 50%,rgba(255,255,255,0.07) 0%,transparent 60%),radial-gradient(circle at 80% 20%,rgba(255,255,255,0.05) 0%,transparent 50%);"></div>
        <div class="overview-hero-content">
            <div class="overview-clinic-logo"><?php echo $initials; ?></div>
            <div>
                <h1 style="font-size:2.2rem;font-weight:800;color:white;margin:0 0 0.5rem;text-shadow:0 2px 10px rgba(0,0,0,0.15);">
                    <?php echo $clinic_name; ?>
                </h1>
                <div class="ov-meta" style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;font-size:0.9rem;color:rgba(255,255,255,0.9);">
                    <span style="display:flex;align-items:center;gap:0.35rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo $clinic_location; ?>
                    </span>
                    <span style="display:flex;align-items:center;gap:0.35rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.42 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.76a16 16 0 0 0 6 6l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16z"/></svg>
                        <?php echo $clinic_phone; ?>
                    </span>
                    <span style="display:flex;align-items:center;gap:0.35rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <?php echo $clinic_email; ?>
                    </span>
                </div>
                <div style="margin-top:0.85rem;display:inline-flex;align-items:center;gap:0.4rem;padding:0.35rem 0.9rem;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.35);border-radius:999px;font-size:0.8rem;font-weight:700;color:white;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Verified Clinic on Bright Steps
                </div>
            </div>
            <div style="margin-left:auto;padding-right:1rem;">
                <a href="book-clinic.php?clinic_id=<?php echo $clinic_id; ?>" class="btn-book-nav" style="padding:0.9rem 2rem;font-size:1rem;background:white;color:#0e7490;box-shadow:0 4px 20px rgba(0,0,0,0.2);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Book Now
                </a>
            </div>
        </div>
    </div>

    <!-- Body -->
    <div class="overview-body">

        <!-- Stats -->
        <div class="ov-stats-grid">
            <div class="ov-stat-card">
                <div class="ov-stat-icon" style="background:rgba(6,182,212,.1);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div><div class="ov-stat-value" style="color:#0e7490;"><?php echo count($specialists); ?>+</div><div class="ov-stat-label">Specialists</div></div>
            </div>
            <div class="ov-stat-card">
                <div class="ov-stat-icon" style="background:rgba(16,185,129,.1);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div><div class="ov-stat-value" style="color:#059669;">98%</div><div class="ov-stat-label">Satisfaction Rate</div></div>
            </div>
            <div class="ov-stat-card">
                <div class="ov-stat-icon" style="background:rgba(245,158,11,.1);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <div><div class="ov-stat-value" style="color:#d97706;">4.8</div><div class="ov-stat-label">Average Rating</div></div>
            </div>
            <div class="ov-stat-card">
                <div class="ov-stat-icon" style="background:rgba(139,92,246,.1);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div><div class="ov-stat-value" style="color:#7c3aed;">500+</div><div class="ov-stat-label">Appointments</div></div>
            </div>
        </div>

        <!-- Our Specialists -->
        <div class="ov-section">
            <h2 style="font-size:1.2rem;font-weight:700;color:#0f172a;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.6rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Our Healthcare Team
                <span style="flex:1;height:2px;background:linear-gradient(to right,rgba(6,182,212,.3),transparent);border-radius:99px;margin-left:0.5rem;"></span>
            </h2>
            <?php if (!empty($specialists)): ?>
            <div class="overview-specialist-grid">
                <?php foreach ($specialists as $i => $s):
                    $av  = strtoupper(substr($s['first_name'],0,1) . substr($s['last_name'],0,1));
                    $bg  = $gradients[$i % count($gradients)];
                ?>
                <div class="overview-spec-card">
                    <div class="overview-spec-avatar" style="background:<?php echo $bg; ?>;"><?php echo $av; ?></div>
                    <div>
                        <div class="overview-spec-name">Dr. <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></div>
                        <div class="overview-spec-type"><?php echo htmlspecialchars($s['specialization']??'Specialist'); ?></div>
                        <div class="overview-spec-exp"><?php echo intval($s['experience_years']??0); ?> years experience</div>
                        <?php if (!empty($s['certificate_of_experience'])): ?>
                        <div style="font-size:0.72rem;color:#94a3b8;margin-top:0.2rem;"><?php echo htmlspecialchars($s['certificate_of_experience']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:2.5rem 1rem;color:#94a3b8;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:1rem;opacity:0.4;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <p>Specialists coming soon</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Services & Info Grid -->
        <div class="overview-info-grid">
            <!-- Services -->
            <div class="overview-info-card">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2" style="width:1.1rem;height:1.1rem;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
                    Services Offered
                </h3>
                <?php
                $services = ['Developmental Assessments', 'Speech Therapy', 'Occupational Therapy',
                             'Behavioral Therapy', 'Pediatric Consultations', 'Child Psychology',
                             'Online Appointments', 'Progress Tracking'];
                foreach ($services as $svc): ?>
                <div class="overview-contact-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2.5" style="width:.9rem;height:.9rem;flex-shrink:0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?php echo htmlspecialchars($svc); ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Contact & Hours -->
            <div class="overview-info-card">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2" style="width:1.1rem;height:1.1rem;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Contact & Hours
                </h3>
                <div class="overview-contact-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?php echo $clinic_location; ?>
                </div>
                <div class="overview-contact-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12"/></svg>
                    <?php echo $clinic_phone; ?>
                </div>
                <div class="overview-contact-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <?php echo $clinic_email; ?>
                </div>
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
                    <div style="font-size:0.8rem;font-weight:700;color:#64748b;margin-bottom:0.5rem;">WORKING HOURS</div>
                    <div class="overview-contact-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Mon – Fri: 8:00 AM – 6:00 PM
                    </div>
                    <div class="overview-contact-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Saturday: 9:00 AM – 2:00 PM
                    </div>
                    <div class="overview-contact-row" style="color:#94a3b8;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        Sunday: Closed
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="overview-booking-cta">
            <h2>Ready to get started?</h2>
            <p>Book an appointment with one of our specialists today and give your child the care they deserve.</p>
            <a href="book-clinic.php?clinic_id=<?php echo $clinic_id; ?>" class="btn-book">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Book an Appointment
            </a>
        </div>
    </div>

    <script src="scripts/theme-toggle.js"></script>
</body>
</html>
