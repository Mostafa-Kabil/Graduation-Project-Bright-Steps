<?php
require_once "includes/auth_check.php";
$parentId = $_SESSION['id'];

// Get specialists and their clinics
$stmt = $connect->prepare("
    SELECT s.specialist_id, s.first_name, s.last_name, s.specialization, c.clinic_name, c.location 
    FROM specialist s 
    INNER JOIN clinic c ON s.clinic_id = c.clinic_id
    ORDER BY c.clinic_name ASC
");
$stmt->execute();
$specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Subscription plan to determine discounts if needed
$stmt = $connect->prepare("SELECT s.plan_name FROM parent_subscription ps INNER JOIN subscription s ON ps.subscription_id = s.subscription_id WHERE ps.parent_id = :pid LIMIT 1");
$stmt->execute(['pid' => $parentId]);
$planname = $stmt->fetchColumn() ?: 'Free';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Clinic Appointment - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .booking-card {
            background: var(--surface-light);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid var(--surface-border);
            max-width: 800px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
        }
    </style>
</head>

<body>
    <?php include "includes/header.php"; ?>
    <div class="dashboard-layout">
        <?php include "includes/sidebar.php"; ?>

        <main class="dashboard-main">
            <div class="dashboard-content">
                <div class="dashboard-header-section">
                    <div>
                        <h1 class="dashboard-title">Book an Appointment</h1>
                        <p class="dashboard-subtitle">Schedule a visit with our pediatric specialists</p>
                    </div>
                </div>

                <div class="alert alert-success" id="alert-success">Appointment booked successfully! We will see you
                    soon.</div>
                <div class="alert alert-error" id="alert-error">Failed to book appointment. Please check all fields.
                </div>

                <div class="booking-card">
                    <form id="booking-form" onsubmit="bookAppointment(event)">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="specialist_id">Select Specialist</label>
                                <select id="specialist_id" name="specialist_id" class="form-input" required>
                                    <option value="">-- Choose a Doctor --</option>
                                    <?php foreach ($specialists as $doc): ?>
                                        <option value="<?php echo htmlspecialchars($doc['specialist_id']); ?>">
                                            Dr.
                                            <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>
                                            (
                                            <?php echo htmlspecialchars($doc['specialization']); ?>) -
                                            <?php echo htmlspecialchars($doc['clinic_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="type">Appointment Type</label>
                                <select id="type" name="type" class="form-input" required>
                                    <option value="onsite">On-site (Clinic Visit)</option>
                                    <option value="online">Online (Video Consultation)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="scheduled_at">Date & Time</label>
                                <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-input"
                                    required style="color:var(--text-color);">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="payment_method">Payment Method (Session Price:
                                    $50)</label>
                                <select id="payment_method" name="payment_method" class="form-input" required>
                                    <option value="Credit Card">Credit Card (Pay Now)</option>
                                    <option value="Cash">Pay at Clinic</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label class="form-label" for="comment">Reason for Visit / Comments</label>
                            <textarea id="comment" name="comment" class="form-input" rows="4"
                                placeholder="Briefly describe your child's symptoms or reason for visit..."></textarea>
                        </div>

                        <div style="text-align: right;">
                            <button type="submit" class="btn btn-gradient" id="submit-btn">Confirm Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

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
    <script src="scripts/navigation.js"></script>
    <script>
        async function bookAppointment(e) {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            const alertSuccess = document.getElementById('alert-success');
            const alertError = document.getElementById('alert-error');

            alertSuccess.style.display = 'none';
            alertError.style.display = 'none';
            btn.disabled = true;
            btn.textContent = 'Processing...';

            const form = document.getElementById('booking-form');
            const data = new FormData(form);

            try {
                const res = await fetch('api_book_appointment.php', {
                    method: 'POST',
                    body: data
                });
                const result = await res.json();

                if (result.success) {
                    alertSuccess.style.display = 'block';
                    form.reset();
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    alertError.textContent = result.error || 'Failed to book appointment.';
                    alertError.style.display = 'block';
                }
            } catch (error) {
                alertError.textContent = 'Network error occurred.';
                alertError.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Confirm Booking';
            }
        }
    </script>
</body>

</html>