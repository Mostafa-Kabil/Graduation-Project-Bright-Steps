<?php
session_start();
include 'connection.php';

$clinic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$clinic_id) {
    echo "Clinic not found.";
    exit;
}

// Fetch Clinic Info
$stmt = $connect->prepare("SELECT * FROM clinic WHERE clinic_id = :id");
$stmt->execute([':id' => $clinic_id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
    echo "Clinic not found.";
    exit;
}

// Fetch Doctors
$doc_stmt = $connect->prepare("
    SELECT s.*, u.first_name, u.last_name, 
           (SELECT ROUND(AVG(rating), 1) FROM feedback WHERE specialist_id = s.specialist_id) as avg_rating
    FROM specialist s
    INNER JOIN users u ON s.specialist_id = u.user_id
    WHERE s.clinic_id = :id AND u.status = 'active'
");
$doc_stmt->execute([':id' => $clinic_id]);
$specialists = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);

// Cover & Profile images with fallbacks
$cover_img = !empty($clinic['cover_image']) ? $clinic['cover_image'] : 'assets/default-clinic-cover.jpg';
$profile_img = !empty($clinic['profile_image']) ? $clinic['profile_image'] : 'assets/logo.png';
$location = $clinic['location'] ?: 'Location not specified';
$rating = $clinic['rating'] ?: '0.0';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($clinic['clinic_name']); ?> - Bright Steps Profile</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/parent.css">
    <style>
        .profile-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
        .cover-photo { width: 100%; height: 350px; background-size: cover; background-position: center; border-radius: var(--radius-2xl); position: relative; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.2); margin-bottom: -5rem; }
        
        .profile-header-card { background: var(--bg-card); border-radius: var(--radius-2xl); padding: 2rem; box-shadow: var(--shadow-xl); display: flex; gap: 2rem; border: 1px solid var(--border-color); position: relative; align-items: flex-end; margin-bottom: 3rem; flex-wrap: wrap; }
        
        .profile-img-wrap { width: 160px; height: 160px; border-radius: 20px; background: white; padding: 0.5rem; box-shadow: 0 15px 35px -5px rgba(0,0,0,0.2); }
        .profile-img-wrap img { width: 100%; height: 100%; object-fit: cover; border-radius: 14px; }
        
        .profile-info { flex: 1; min-width: 300px; padding-bottom: 0.5rem; }
        .clinic-name { font-size: 2.5rem; font-weight: 800; color: var(--text-primary); margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.75rem; }
        
        .clinic-details-row { display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .detail-item { display: flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); font-size: 0.95rem; font-weight: 500; }
        .detail-item svg { width: 1.25rem; height: 1.25rem; stroke: var(--purple-500); }
        
        .verified-check { background: var(--green-100); color: var(--green-600); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        [data-theme="dark"] .verified-check { background: rgba(34,197,94,0.2); color: var(--green-400); }
        
        .profile-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        @media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }
        
        .section-box { background: var(--bg-card); border-radius: var(--radius-xl); padding: 2rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-md); margin-bottom: 2rem; }
        .section-title { font-size: 1.25rem; font-weight: 700; margin: 0 0 1.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem; }
        
        .tag-list { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .tag { padding: 0.5rem 1rem; border-radius: 99px; background: var(--bg-secondary); color: var(--text-primary); font-size: 0.875rem; font-weight: 600; border: 1px solid var(--border-color); }
        
        /* Specialists list */
        .doctor-card { display: flex; gap: 1rem; padding: 1.25rem; border: 1px solid var(--border-color); border-radius: var(--radius-lg); margin-bottom: 1rem; transition: transform 0.2s; align-items: center; }
        .doctor-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--blue-300); }
        .doc-avatar { width: 3.5rem; height: 3.5rem; border-radius: 50%; background: linear-gradient(135deg, var(--blue-500), var(--purple-500)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.25rem; flex-shrink: 0; }
        .doc-info { flex: 1; }
        .doc-name { font-weight: 700; font-size: 1.125rem; color: var(--text-primary); }
        .doc-spec { color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.25rem; }
    </style>
</head>
<body>
    <?php include 'includes/public_header.php'; ?>

    <main class="profile-container">
        <!-- Cover Photo -->
        <div class="cover-photo" style="background-image: url('<?php echo htmlspecialchars($cover_img); ?>');"></div>
        
        <!-- Header Profile Card -->
        <div class="profile-header-card">
            <div class="profile-img-wrap">
                <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Clinic Profile">
            </div>
            <div class="profile-info">
                <h1 class="clinic-name">
                    <?php echo htmlspecialchars($clinic['clinic_name']); ?>
                    <?php if ($clinic['status'] === 'verified'): ?>
                        <div class="verified-check" title="Verified Clinic">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:16px;height:16px"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                    <?php endif; ?>
                </h1>
                
                <div class="clinic-details-row">
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo htmlspecialchars($location); ?>
                    </div>
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24" fill="none" style="stroke:var(--amber-500)"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?php echo $rating; ?> / 5.0 Clinic Rating
                    </div>
                    <?php if (!empty($clinic['website'])): ?>
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1 4-10z"/></svg>
                        <a href="<?php echo htmlspecialchars($clinic['website']); ?>" target="_blank" style="color:inherit;text-decoration:none">Visit Website</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="text-align: right;">
                <button class="btn btn-gradient btn-lg" onclick="alert('Booking portal integration coming soon!');">Book Appointment</button>
            </div>
        </div>

        <div class="profile-grid">
            <!-- Left Column -->
            <div>
                <!-- About Us -->
                <div class="section-box">
                    <h2 class="section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.5rem;height:1.5rem"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        About the Clinic
                    </h2>
                    <div style="color:var(--text-secondary);line-height:1.7;">
                        <?php echo !empty($clinic['bio']) ? nl2br(htmlspecialchars($clinic['bio'])) : 'No biography available for this clinic.'; ?>
                    </div>
                </div>

                <!-- Specialists -->
                <div class="section-box">
                    <h2 class="section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.5rem;height:1.5rem"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Our Specialists
                    </h2>
                    
                    <?php if (count($specialists) > 0): ?>
                        <?php foreach($specialists as $s): ?>
                        <div class="doctor-card">
                            <div class="doc-avatar">
                                <?php echo htmlspecialchars(mb_substr($s['first_name'],0,1).mb_substr($s['last_name'],0,1)); ?>
                            </div>
                            <div class="doc-info">
                                <div class="doc-name">Dr. <?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></div>
                                <div class="doc-spec"><?php echo htmlspecialchars($s['specialization']); ?> &bull; <?php echo $s['experience_years']; ?> Yrs Exp</div>
                            </div>
                            <div style="font-weight:700;color:var(--amber-500);display:flex;align-items:center;gap:0.25rem;">
                                <svg viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <?php echo $s['avg_rating'] ?: 'New'; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--text-secondary)">No active specialists listed.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Specialties -->
                <div class="section-box">
                    <h2 class="section-title">Specialties</h2>
                    <div class="tag-list">
                        <?php 
                        if (!empty($clinic['specialties'])) {
                            $specs = array_map('trim', explode(',', $clinic['specialties']));
                            foreach($specs as $sp) {
                                echo '<span class="tag">'.htmlspecialchars($sp).'</span>';
                            }
                        } else {
                            echo '<span class="tag">General Services</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="section-box">
                    <h2 class="section-title">Contact Information</h2>
                    
                    <div style="margin-bottom: 1.5rem">
                        <div style="font-weight:600;font-size:0.875rem;color:var(--text-secondary);margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:1px">Opening Hours</div>
                        <div style="color:var(--text-primary);line-height:1.6">
                            <?php echo !empty($clinic['opening_hours']) ? nl2br(htmlspecialchars($clinic['opening_hours'])) : 'Contact clinic for hours.'; ?>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem">
                        <div style="font-weight:600;font-size:0.875rem;color:var(--text-secondary);margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:1px">Email address</div>
                        <div style="color:var(--text-primary);line-height:1.6">
                            <?php echo htmlspecialchars($clinic['email']); ?>
                        </div>
                    </div>
                    
                    <button class="btn btn-outline" style="width:100%;justify-content:center;padding:0.75rem" onclick="navigator.clipboard.writeText('<?php echo addslashes($clinic['email']); ?>'); alert('Email copied!');">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        Copy Email
                    </button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/public_footer.php'; ?>
    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/language-toggle.js"></script>
</body>
</html>
