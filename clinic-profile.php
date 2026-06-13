<?php
session_start();
include "connection.php";

$clinic_id = $_GET['id'] ?? 0;

if (!$clinic_id) {
    die("Clinic ID is required");
}

try {
    // Fetch Clinic Details
    $sql = "SELECT * FROM clinic WHERE clinic_id = :id";
    $stmt = $connect->prepare($sql);
    $stmt->execute(['id' => $clinic_id]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$clinic) {
        die("Clinic not found");
    }

    // Fetch Specialists in this clinic
    $sql2 = "SELECT * FROM specialist WHERE clinic_id = :id";
    $stmt2 = $connect->prepare($sql2);
    $stmt2->execute(['id' => $clinic_id]);
    $specialists = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Reviews (if table exists)
    $reviews = [];
    $avgRating = 0;
    try {
        // Clinic Reviews
        $sql3 = "SELECT r.rating, r.comment, r.created_at, u.first_name, u.last_name, 'Clinic Review' as review_type 
                 FROM clinic_reviews r 
                 JOIN users u ON r.parent_id = u.user_id 
                 WHERE r.clinic_id = :id";
        $stmt3 = $connect->prepare($sql3);
        $stmt3->execute(['id' => $clinic_id]);
        $clinic_reviews = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        // Specialist Reviews
        $sql4 = "SELECT r.rating, r.comment, r.created_at, u.first_name, u.last_name, CONCAT('Dr. ', s.first_name, ' ', s.last_name) as review_type 
                 FROM specialist_reviews r
                 JOIN users u ON r.parent_id = u.user_id
                 JOIN specialist s ON r.specialist_id = s.specialist_id
                 WHERE s.clinic_id = :id";
        $stmt4 = $connect->prepare($sql4);
        $stmt4->execute(['id' => $clinic_id]);
        $spec_reviews = $stmt4->fetchAll(PDO::FETCH_ASSOC);

        $clinicAvg = 0;
        $specAvg = 0;
        if (count($clinic_reviews) > 0) {
            $clinicAvg = round(array_sum(array_column($clinic_reviews, 'rating')) / count($clinic_reviews), 1);
        }
        if (count($spec_reviews) > 0) {
            $specAvg = round(array_sum(array_column($spec_reviews, 'rating')) / count($spec_reviews), 1);
        }

        $reviews = array_merge($clinic_reviews, $spec_reviews);
        usort($reviews, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        if (count($reviews) > 0) {
            $sum = array_sum(array_column($reviews, 'rating'));
            $avgRating = round($sum / count($reviews), 1);
        }
    } catch (Exception $e) {
        // Ignore errors
    }

} catch (Exception $e) {
    die("Error loading clinic profile: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($clinic['clinic_name']) ?> - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="dashboards/parent/dashboard.css">
    <style>
        .profile-header {
            background: var(--blue-50);
            padding: 4rem 2rem 3rem;
            text-align: center;
            border-bottom: 1px solid var(--blue-100);
            position: relative;
        }
        .profile-avatar {
            width: 140px;
            height: 140px;
            background: #fff;
            color: var(--blue-600);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 800;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(37,99,235,0.1);
            border: 4px solid #fff;
        }
        .profile-name {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--slate-900);
            margin-bottom: 0.5rem;
        }
        .profile-role {
            font-size: 1.1rem;
            color: var(--blue-600);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .stat-item {
            background: #fff;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            min-width: 120px;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--slate-900);
        }
        .stat-label {
            font-size: 0.85rem;
            color: var(--slate-500);
            font-weight: 600;
        }
        .profile-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 3rem 2rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        @media (max-width: 768px) {
            .profile-container { grid-template-columns: 1fr; }
        }
        .section-card {
            background: #fff;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--slate-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .review-card {
            padding: 1.5rem;
            border: 1px solid var(--slate-200);
            border-radius: 16px;
            margin-bottom: 1rem;
            background: #f8fafc;
        }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; align-items:center; }
        .reviewer-name { font-weight: 700; color: var(--slate-800); }
        .review-date { font-size: 0.8rem; color: var(--slate-400); }
        .stars { color: #f59e0b; font-size: 1.1rem; margin-bottom: 0.5rem; }

        .spec-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .spec-card {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            border: 1px solid var(--slate-200);
            border-radius: 16px;
            background: #fff;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .spec-card:hover {
            border-color: var(--blue-300);
            box-shadow: 0 10px 15px -3px rgba(37,99,235,0.1);
            transform: translateY(-2px);
        }
        .spec-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--blue-100);
            color: var(--blue-700);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
        }
    </style>
</head>
<body style="background:var(--slate-50);margin:0;">
    <div class="profile-header">
        <button onclick="history.back()" style="position:absolute;top:2rem;left:2rem;background:#fff;border:none;padding:0.75rem 1rem;border-radius:12px;font-weight:600;color:var(--slate-700);cursor:pointer;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);text-decoration:none;display:flex;align-items:center;gap:0.5rem;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back</button>
        
        <?php if (!empty($clinic['logo_url'])): ?>
            <img src="<?= htmlspecialchars($clinic['logo_url']) ?>" class="profile-avatar" style="object-fit:cover;" alt="Clinic Logo">
        <?php else: ?>
            <div class="profile-avatar">🏥</div>
        <?php endif; ?>
        
        <h1 class="profile-name"><?= htmlspecialchars($clinic['clinic_name']) ?></h1>
        <div class="profile-role">📍 <?= htmlspecialchars($clinic['location'] ?? 'Location not specified') ?></div>
        
        <div class="profile-stats">
            <div class="stat-item">
                <div class="stat-value"><?= count($specialists) ?></div>
                <div class="stat-label">Specialists</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color:var(--emerald-600);">⭐ <?= !empty($clinicAvg) ? $clinicAvg : 'N/A' ?></div>
                <div class="stat-label">Clinic Rating (<?= count($clinic_reviews ?? []) ?>)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color:var(--indigo-600);">⭐ <?= !empty($specAvg) ? $specAvg : 'N/A' ?></div>
                <div class="stat-label">Specialists Rating (<?= count($spec_reviews ?? []) ?>)</div>
            </div>
        </div>
    </div>

    <div class="profile-container">
        <div>
            <div class="section-card">
                <h2 class="section-title">About the Clinic</h2>
                <p style="color:var(--slate-600);line-height:1.7;font-size:1.05rem;"><?= nl2br(htmlspecialchars($clinic['description'] ?? 'No description provided yet.')) ?></p>
            </div>

            <div class="section-card">
                <h2 class="section-title">Our Specialists (<?= count($specialists) ?>)</h2>
                <?php if (count($specialists) > 0): ?>
                    <div class="spec-list">
                    <?php foreach ($specialists as $s): ?>
                        <a href="specialist-profile.php?id=<?= $s['specialist_id'] ?>" class="spec-card">
                            <?php if (!empty($s['profile_photo'])): ?>
                                <img src="<?= htmlspecialchars($s['profile_photo']) ?>" class="spec-avatar" style="object-fit:cover;" alt="Avatar">
                            <?php else: ?>
                                <div class="spec-avatar"><?= strtoupper($s['first_name'][0] . $s['last_name'][0]) ?></div>
                            <?php endif; ?>
                            
                            <div style="flex:1;">
                                <h3 style="margin:0 0 0.25rem;font-size:1.1rem;color:var(--slate-900);">Dr. <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></h3>
                                <div style="color:var(--blue-600);font-size:0.9rem;font-weight:600;margin-bottom:0.25rem;"><?= htmlspecialchars($s['specialization']) ?></div>
                                <div style="color:var(--slate-500);font-size:0.85rem;"><?= htmlspecialchars($s['experience_years']) ?>+ Years Exp.</div>
                            </div>
                            
                            <div style="text-align:right;">
                                <div style="font-weight:800;color:var(--slate-900);">$<?= htmlspecialchars($s['consultation_fee']) ?></div>
                                <div style="color:var(--slate-500);font-size:0.8rem;">Consultation</div>
                                <div style="margin-top:0.5rem;color:var(--blue-600);font-weight:600;font-size:0.85rem;">View Profile →</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center;padding:2rem;color:var(--slate-500);background:#f8fafc;border-radius:12px;">No specialists currently available.</div>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <h2 class="section-title">Patient Reviews</h2>
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $r): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <div class="reviewer-name"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'][0] . '.') ?></div>
                                    <div style="font-size:0.75rem;color:var(--blue-600);font-weight:600;margin-top:0.2rem;"><?= htmlspecialchars($r['review_type']) ?></div>
                                </div>
                                <div class="review-date"><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
                            </div>
                            <div class="stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></div>
                            <p style="color:var(--slate-600);font-size:0.95rem;margin:0;line-height:1.5;"><?= htmlspecialchars($r['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center;padding:2rem;color:var(--slate-500);background:#f8fafc;border-radius:12px;">No reviews yet for this clinic.</div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="section-card">
                <h2 class="section-title">Contact Information</h2>
                <div style="margin-bottom:1.5rem;">
                    <div style="color:var(--slate-500);font-size:0.85rem;font-weight:600;margin-bottom:0.25rem;">Address</div>
                    <div style="color:var(--slate-900);font-weight:500;"><?= htmlspecialchars($clinic['location'] ?? 'Not provided') ?></div>
                </div>
                <div style="margin-bottom:1.5rem;">
                    <div style="color:var(--slate-500);font-size:0.85rem;font-weight:600;margin-bottom:0.25rem;">Phone</div>
                    <div style="color:var(--slate-900);font-weight:500;"><?= htmlspecialchars($clinic['contact_number'] ?? 'Not provided') ?></div>
                </div>
                <div>
                    <div style="color:var(--slate-500);font-size:0.85rem;font-weight:600;margin-bottom:0.25rem;">Email</div>
                    <div style="color:var(--slate-900);font-weight:500;"><?= htmlspecialchars($clinic['email'] ?? 'Not provided') ?></div>
                </div>
            </div>

            <div class="section-card">
                <h2 class="section-title">Operating Hours</h2>
                <p style="color:var(--slate-500);font-size:0.95rem;">Please check individual specialist profiles for their specific availability.</p>
            </div>
        </div>
    </div>
</body>
</html>
