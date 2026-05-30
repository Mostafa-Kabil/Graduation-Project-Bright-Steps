<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

$specialist_id = $_GET['id'] ?? null;
if (!$specialist_id) {
    die("Specialist ID is required");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specialist Profile - Bright Steps</title>
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/specialist-profile.css">
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
        <!-- Content loaded via JS -->
        <div class="loading">Loading profile...</div>
    </main>

    <script>
        const specialistId = <?php echo json_encode($specialist_id); ?>;
        
        async function fetchProfile() {
            try {
                const response = await fetch(`api_get_specialist_profile.php?id=${specialistId}`);
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
            const sp = data.specialist;
            const cl = data.clinic;
            const rev = data.reviews;
            const avail = data.availability;
            
            const avatar = sp.profile_photo ? `<img src="${sp.profile_photo}" alt="Avatar">` : `<div class="avatar-initials">${sp.first_name[0]}${sp.last_name[0]}</div>`;
            
            const html = `
                <section class="hero glass-card">
                    <div class="hero-content">
                        <div class="hero-avatar">${avatar}</div>
                        <div class="hero-info">
                            <h1>Dr. ${sp.first_name} ${sp.last_name}</h1>
                            <div class="badge">${sp.specialization}</div>
                            <div class="rating">
                                <span class="stars">★</span> ${rev.avg_rating} (${rev.total} reviews)
                            </div>
                            <p class="bio">${sp.bio || sp.description || 'No bio available.'}</p>
                            <button class="btn btn-primary cta-book" onclick="scrollToBook()">Book Appointment</button>
                        </div>
                    </div>
                </section>
                
                <section class="details grid">
                    <div class="card">
                        <h3>Professional Details</h3>
                        <ul>
                            <li><strong>Experience:</strong> ${sp.experience_years} years</li>
                            <li><strong>Age Group:</strong> ${sp.patient_age_group || 'All ages'}</li>
                            <li><strong>Therapy:</strong> ${sp.therapy_approaches || 'Standard'}</li>
                            <li><strong>Focus:</strong> ${sp.focus_areas ? sp.focus_areas.split(',').map(f => `<span class="tag">${f}</span>`).join(' ') : 'General'}</li>
                        </ul>
                    </div>
                    
                    ${cl ? `
                    <div class="card">
                        <h3>Clinic & Availability</h3>
                        <div class="clinic-info">
                            <h4>${cl.clinic_name}</h4>
                            <p>📍 ${cl.location}</p>
                        </div>
                        <div class="availability">
                            ${avail.map(a => `<div class="avail-slot"><strong>${a.day_name}:</strong> ${a.start_time} - ${a.end_time}</div>`).join('')}
                        </div>
                    </div>
                    ` : ''}
                </section>

                <section class="reviews">
                    <h3>Reviews</h3>
                    ${rev.items.length === 0 ? '<p>No reviews yet.</p>' : rev.items.map(r => `
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
                
                <section id="book" class="booking-section card">
                    <h3>Book an Appointment</h3>
                    <button class="btn btn-primary" onclick="proceedToBook()">Proceed to Book in Dashboard</button>
                </section>
            `;
            
            document.getElementById('app').innerHTML = html;
        }

        function scrollToBook() {
            document.getElementById('book').scrollIntoView({ behavior: 'smooth' });
        }

        function proceedToBook() {
            sessionStorage.setItem('prefillSpecialistId', specialistId);
            window.location.href = 'dashboards/parent/dashboard.php?view=appointments';
        }

        document.addEventListener('DOMContentLoaded', fetchProfile);
    </script>
</body>
</html>
