<?php
session_start();
require_once 'connection.php';

$clinic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($clinic_id === 0) {
    die("Invalid clinic ID.");
}

// Fetch clinic info
$stmt = $connect->prepare("
    SELECT c.clinic_name, c.location, c.email,
           GROUP_CONCAT(cp.phone SEPARATOR ', ') AS phones
    FROM clinic c
    LEFT JOIN clinic_phone cp ON cp.clinic_id = c.clinic_id
    WHERE c.clinic_id = ?
    GROUP BY c.clinic_id
");
$stmt->execute([$clinic_id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
    die("Clinic not found.");
}

// Fetch specialists
$stmtSpec = $connect->prepare("
    SELECT s.first_name, s.last_name, s.specialization, s.experience_years, s.certificate_of_experience
    FROM specialist s
    LEFT JOIN users u ON u.user_id = s.specialist_id
    WHERE s.clinic_id = ?
    ORDER BY s.specialist_id DESC
");
$stmtSpec->execute([$clinic_id]);
$specialists = $stmtSpec->fetchAll(PDO::FETCH_ASSOC);

$clinic_name = htmlspecialchars($clinic['clinic_name']);
$initials = strtoupper(substr($clinic_name, 0, 1));
$words = explode(' ', $clinic_name);
if (count($words) > 1) {
    $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $clinic_name; ?> - Bright Steps Clinic</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: var(--slate-800); }
        .hero { background: linear-gradient(135deg, #0f172a, #1e293b); padding: 4rem 2rem; color: white; text-align: center; }
        .hero-logo { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #3b82f6); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; margin: 0 auto 1.5rem; border: 4px solid rgba(255,255,255,0.2); }
        .hero-title { font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: #fff; }
        .hero-subtitle { font-size: 1.1rem; color: var(--slate-300); max-width: 600px; margin: 0 auto 1.5rem; }
        .verified-badge { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; background: rgba(34, 197, 94, 0.2); color: #4ade80; padding: 0.35rem 0.85rem; border-radius: 20px; font-weight: 600; }
        
        .container { max-width: 1000px; margin: -2rem auto 3rem; padding: 0 1rem; position: relative; }
        .clinic-info-card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .info-block div { font-size: 0.75rem; color: var(--slate-500); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
        .info-block p { font-size: 1rem; color: var(--slate-800); font-weight: 500; margin: 0; }
        
        .section-title { font-size: 1.5rem; font-weight: 800; color: var(--slate-800); margin: 3rem 0 1.5rem; }
        
        .specialist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .specialist-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 1rem; transition: transform 0.2s, box-shadow 0.2s; }
        .specialist-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .spec-avatar { width: 3.5rem; height: 3.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 700; color: white; background: linear-gradient(135deg, #8b5cf6, #ec4899); flex-shrink: 0; }
        .spec-info h3 { font-size: 1.05rem; font-weight: 700; margin: 0 0 0.15rem; color: var(--slate-800); line-height: 1.2; }
        .spec-info p.spec { font-size: 0.85rem; color: #06b6d4; font-weight: 600; margin: 0 0 0.25rem; }
        .spec-info p.exp { font-size: 0.75rem; color: var(--slate-500); margin: 0; }
        
        .book-btn-wrapper { text-align: center; margin-top: 3rem; }
        .book-btn { background: linear-gradient(135deg, #06b6d4, #3b82f6); color: white; border: none; padding: 1rem 2rem; font-size: 1.1rem; border-radius: 12px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4); transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; display: inline-block; }
        .book-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6); }
    </style>
</head>
<body>

    <div class="hero">
        <div class="hero-logo"><?php echo $initials; ?></div>
        <h1 class="hero-title"><?php echo $clinic_name; ?></h1>
        <p class="hero-subtitle">Providing premier pediatric and child development healthcare services for Bright Steps parents.</p>
        <div class="verified-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            Verified Bright Steps Partner
        </div>
    </div>

    <div class="container">
        <div class="clinic-info-card">
            <div class="info-block">
                <div>Email Contact</div>
                <p><?php echo htmlspecialchars($clinic['email'] ?: 'Not provided'); ?></p>
            </div>
            <div class="info-block">
                <div>Location</div>
                <p><?php echo htmlspecialchars($clinic['location'] ?: 'Not provided'); ?></p>
            </div>
            <div class="info-block">
                <div>Phone</div>
                <p><?php echo htmlspecialchars($clinic['phones'] ?: 'Not provided'); ?></p>
            </div>
        </div>

        <h2 class="section-title">Our Healthcare Team</h2>
        <?php if (empty($specialists)): ?>
            <p style="color: var(--slate-500);">No specialists are registered for this clinic yet.</p>
        <?php else: ?>
            <div class="specialist-grid">
                <?php foreach ($specialists as $s): 
                    $sp_initials = strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1));
                ?>
                <div class="specialist-card">
                    <div class="spec-avatar"><?php echo $sp_initials; ?></div>
                    <div class="spec-info">
                        <h3>Dr. <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></h3>
                        <p class="spec"><?php echo htmlspecialchars($s['specialization'] ?: 'Specialist'); ?></p>
                        <p class="exp"><?php echo intval($s['experience_years']); ?> years of experience</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="book-btn-wrapper">
            <a href="login.php?redirect=appointments&clinic_id=<?php echo $clinic_id; ?>" class="book-btn">Book an Appointment</a>
        </div>
    </div>

</body>
</html>
