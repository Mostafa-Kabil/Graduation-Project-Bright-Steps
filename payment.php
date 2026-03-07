<?php
session_start();
include 'connection.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['id']);
$parentId = $isLoggedIn ? $_SESSION['id'] : 0;
$userName = $isLoggedIn ? ($_SESSION['fname'] . ' ' . $_SESSION['lname']) : 'Guest';
$userEmail = $isLoggedIn ? $_SESSION['email'] : '';

// Get subscription plans from DB
$plans = [];
try {
    $stmt = $connect->prepare("SELECT * FROM subscription ORDER BY price ASC");
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bright Steps</title>
    <meta name="description" content="Upgrade to Bright Steps Premium for AI-powered child development monitoring.">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="styles/globals.css">
    <link rel="stylesheet" href="styles/payment.css">
</head>

<body>
    <!-- Back Button -->
    <a class="payment-back-btn" href="pricing.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7" />
        </svg>
        Back to Pricing
    </a>

    <div class="payment-page">
        <div class="payment-container">
            <!-- ─── Order Summary (Left Panel) ──────────────────── -->
            <div class="order-summary">
                <div class="order-summary-header">
                    <a href="index.php" class="logo-link">
                        <img src="assets/logo.png" alt="Bright Steps">
                    </a>
                    <h2>Premium Plan</h2>
                    <p class="plan-subtitle">Complete AI-powered child development monitoring</p>
                </div>

                <div class="order-details">
                    <div class="order-line">
                        <span>Premium Monthly</span>
                        <span>$9.99</span>
                    </div>
                    <div class="order-line">
                        <span>7-day free trial</span>
                        <span style="color: #86efac;">Included</span>
                    </div>
                    <div class="order-line">
                        <span>Tax</span>
                        <span>$0.00</span>
                    </div>
                    <div class="order-line total">
                        <span>Total today</span>
                        <span>$9.99</span>
                    </div>
                </div>

                <div class="order-features">
                    <h4>What's included</h4>
                    <div class="order-feature">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>AI speech & language analysis</span>
                    </div>
                    <div class="order-feature">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Motor skills video assessment</span>
                    </div>
                    <div class="order-feature">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Personalized recommendations</span>
                    </div>
                    <div class="order-feature">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Doctor-ready PDF reports</span>
                    </div>
                    <div class="order-feature">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Unlimited child profiles</span>
                    </div>
                    <div class="order-feature">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span>Priority support</span>
                    </div>
                </div>
            </div>

            <!-- ─── Checkout Form (Right Panel) ─────────────────── -->
            <div class="checkout-form-wrapper" id="checkout-form-wrapper">
                <div class="checkout-header">
                    <h2>Payment Details</h2>
                    <p>Secure checkout</p>
                </div>

                <?php if (!$isLoggedIn): ?>
                    <div style="text-align:center; padding: 2rem 0;">
                        <p style="color: #f59e0b; margin-bottom: 1rem;">Please log in to complete your purchase.</p>
                        <a href="login.php" class="result-btn result-btn-primary">Log In</a>
                    </div>
                <?php else: ?>

                    <form id="payment-form" class="checkout-form">
                        <div class="form-group">
                            <label class="form-label" for="cardholder-name">Cardholder Name</label>
                            <input type="text" id="cardholder-name" class="form-input" placeholder="Name on card"
                                value="<?php echo htmlspecialchars($userName); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="cardholder-email">Email</label>
                            <input type="email" id="cardholder-email" class="form-input" placeholder="email@example.com"
                                value="<?php echo htmlspecialchars($userEmail); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="card-number">Card Number</label>
                            <input type="text" id="card-number" class="form-input" placeholder="4242 4242 4242 4242"
                                maxlength="19" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label" for="card-expiry">Expiry Date</label>
                                <input type="text" id="card-expiry" class="form-input" placeholder="MM / YY" maxlength="7"
                                    required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="card-cvc">CVC</label>
                                <input type="text" id="card-cvc" class="form-input" placeholder="123" maxlength="4"
                                    required>
                            </div>
                        </div>

                        <div id="card-errors" class="card-errors" role="alert"></div>

                        <div class="security-badges">
                            <div class="security-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <span>SSL Encrypted</span>
                            </div>
                            <div class="security-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                <span>Secure Payment</span>
                            </div>
                            <div class="security-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <polyline points="22 4 12 14.01 9 11.01" />
                                </svg>
                                <span>Money-Back Guarantee</span>
                            </div>
                        </div>

                        <button type="submit" id="pay-button" class="pay-button">
                            <span class="btn-text">Pay $9.99</span>
                            <div class="spinner"></div>
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Success State -->
                <div id="payment-success" class="payment-result">
                    <div class="result-icon success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                    </div>
                    <h3 class="result-title">Payment Successful!</h3>
                    <p class="result-message">Welcome to Premium! Your account has been upgraded. Enjoy AI-powered child
                        development monitoring.</p>
                    <a href="dashboard.php" class="result-btn result-btn-primary">Go to Dashboard</a>
                </div>

                <!-- Error State -->
                <div id="payment-error" class="payment-result">
                    <div class="result-icon error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="15" y1="9" x2="9" y2="15" />
                            <line x1="9" y1="9" x2="15" y2="15" />
                        </svg>
                    </div>
                    <h3 class="result-title">Payment Failed</h3>
                    <p class="result-message" id="error-detail">Something went wrong. Please try again.</p>
                    <button class="result-btn result-btn-primary" onclick="resetForm()">Try Again</button>
                </div>
            </div>
        </div>
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
    <script src="scripts/theme-toggle.js"></script>
    <script src="scripts/navigation.js"></script>

    <script>
        // ── Card input formatting ─────────────────────────────────────
        const cardInput = document.getElementById('card-number');
        const expiryInput = document.getElementById('card-expiry');
        const cvcInput = document.getElementById('card-cvc');

        if (cardInput) {
            cardInput.addEventListener('input', function (e) {
                let val = this.value.replace(/\D/g, '');
                val = val.substring(0, 16);
                let formatted = val.replace(/(.{4})/g, '$1 ').trim();
                this.value = formatted;
            });
        }

        if (expiryInput) {
            expiryInput.addEventListener('input', function (e) {
                let val = this.value.replace(/\D/g, '');
                if (val.length >= 2) {
                    val = val.substring(0, 2) + ' / ' + val.substring(2, 4);
                }
                this.value = val;
            });
        }

        if (cvcInput) {
            cvcInput.addEventListener('input', function (e) {
                this.value = this.value.replace(/\D/g, '').substring(0, 4);
            });
        }

        // ── Form validation & submission ──────────────────────────────
        const SUBSCRIPTION_ID = 2; // Premium plan ID – adjust to match your DB

        const form = document.getElementById('payment-form');
        const payBtn = document.getElementById('pay-button');

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (payBtn.classList.contains('loading')) return;

                const errDiv = document.getElementById('card-errors');
                errDiv.textContent = '';

                // Basic validation
                const name = document.getElementById('cardholder-name').value.trim();
                const email = document.getElementById('cardholder-email').value.trim();
                const card = cardInput.value.replace(/\s/g, '');
                const expiry = expiryInput.value.trim();
                const cvc = cvcInput.value.trim();

                if (!name || !email || !card || !expiry || !cvc) {
                    errDiv.textContent = 'Please fill in all fields.';
                    return;
                }

                if (card.length < 13) {
                    errDiv.textContent = 'Invalid card number.';
                    return;
                }

                if (!expiry.includes('/')) {
                    errDiv.textContent = 'Invalid expiry date.';
                    return;
                }

                if (cvc.length < 3) {
                    errDiv.textContent = 'Invalid CVC.';
                    return;
                }

                payBtn.classList.add('loading');
                payBtn.disabled = true;

                try {
                    const res = await fetch('api_payment.php?action=process', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            subscription_id: SUBSCRIPTION_ID,
                            payment_method: 'card',
                        }),
                    });

                    const data = await res.json();

                    if (!res.ok || data.error) {
                        throw new Error(data.error || 'Payment failed');
                    }

                    showResult('success');

                } catch (err) {
                    document.getElementById('error-detail').textContent = err.message;
                    showResult('error');
                } finally {
                    payBtn.classList.remove('loading');
                    payBtn.disabled = false;
                }
            });
        }

        function showResult(type) {
            const form = document.getElementById('payment-form');
            const header = document.querySelector('.checkout-header');
            if (form) form.style.display = 'none';
            if (header) header.style.display = 'none';
            document.getElementById(`payment-${type}`).classList.add('show');
        }

        function resetForm() {
            const form = document.getElementById('payment-form');
            const header = document.querySelector('.checkout-header');
            if (form) form.style.display = '';
            if (header) header.style.display = '';
            document.getElementById('payment-error').classList.remove('show');
            document.getElementById('payment-success').classList.remove('show');
        }
    </script>
</body>

</html>