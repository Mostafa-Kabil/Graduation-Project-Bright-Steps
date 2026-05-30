<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

$clinic_id = $_GET['id'] ?? null;
if (!$clinic_id) {
    die("Clinic ID is required");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Profile - Bright Steps</title>
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/clinic-profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="top-nav">
        <div class="logo">Bright Steps</div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Parent'); ?></span>
            <a href="javascript:history.back()" class="back-btn">← Back</a>
        </div>
    </header>

    <main class="profile-container" id="app">
        <div class="loading">Loading profile...</div>
    </main>

    <script>
        const clinicId = <?php echo json_encode($clinic_id); ?>;
        
        async function fetchProfile() {
            try {
                const response = await fetch(`api_get_clinic_profile.php?id=${clinicId}`);
                const data = await response.json();
                
                if (!data.success) {
                    document.getElementById('app').innerHTML = `<div class="error">${data.error}</div>`;
                    return;
                }
                
                renderProfile(data);
            } catch (err) {
                document.getElementById('app').innerHTML = `<div class="error">Failed to load profile.</div>`;
            }
        }
        
        function renderProfile(data) {
            const cl = data.clinic;
            const specs = data.specialists;
            const rev = data.reviews;
            
            const avatar = cl.logo_url ? `<img src="${cl.logo_url}" alt="Logo">` : `<div class="avatar-initials">${cl.clinic_name[0]}</div>`;
            
            const html = `
                <section class="hero glass-card">
                    <div class="hero-content">
                        <div class="hero-avatar">${avatar}</div>
                        <div class="hero-info">
                            <h1>${cl.clinic_name}</h1>
                            <p class="location">📍 ${cl.location}</p>
                            <div class="rating">
                                <span class="stars">★</span> ${rev.avg_rating} (${rev.total} reviews)
                            </div>
                            <p class="bio">${cl.bio || cl.description || 'Welcome to our clinic.'}</p>
                            <button class="btn btn-primary cta-book" onclick="scrollToSpecialists()">Book with a Specialist Here</button>
                        </div>
                    </div>
                </section>
                
                <section class="details card mt-2">
                    <h3>About Us</h3>
                    <p>${cl.description || 'We offer excellent services.'}</p>
                    <!-- Map placeholder -->
                    <div class="map-placeholder">
                        🗺️ ${cl.location}
                    </div>
                </section>

                <section id="specialists" class="specialists mt-2">
                    <h3>Specialists at this Clinic</h3>
                    <div class="spec-grid">
                        ${specs.length === 0 ? '<p>No specialists found.</p>' : specs.map(sp => `
                            <div class="spec-card">
                                <div class="spec-info">
                                    <h4>Dr. ${sp.first_name} ${sp.last_name}</h4>
                                    <p>${sp.specialization}</p>
                                    <p>★ ${sp.avg_rating}</p>
                                </div>
                                <div class="spec-actions">
                                    <button class="btn btn-secondary" onclick="window.location.href='specialist-profile.php?id=${sp.specialist_id}'">View Profile</button>
                                    <button class="btn btn-primary" onclick="proceedToBook(${sp.specialist_id})">Book</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </section>

                <section class="reviews mt-2">
                    <h3>Clinic Reviews</h3>
                    ${rev.items.length === 0 ? '<p>Be the first to review!</p>' : rev.items.map(r => `
                        <div class="review-card">
                            <div class="review-header">
                                <strong>${r.parent_name}</strong>
                                <span class="stars">${'★'.repeat(r.rating)}</span>
                            </div>
                            <p>${r.comment}</p>
                            <small>${new Date(r.created_at).toLocaleDateString()}</small>
                        </div>
                    `).join('')}
                </section>
            `;
            
            document.getElementById('app').innerHTML = html;
        }

        function scrollToSpecialists() {
            document.getElementById('specialists').scrollIntoView({ behavior: 'smooth' });
        }

        function proceedToBook(specialistId) {
            sessionStorage.setItem('prefillSpecialistId', specialistId);
            window.location.href = 'dashboards/parent/dashboard.php?view=appointments';
        }

        document.addEventListener('DOMContentLoaded', fetchProfile);
    </script>
</body>
</html>
