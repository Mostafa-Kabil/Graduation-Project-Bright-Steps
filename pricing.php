<?php
include 'connection.php';

// Fetch active subscription plans
$stmt = $connect->query("SELECT * FROM subscription WHERE status = 'active' ORDER BY price ASC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - Bright Steps</title>
    <meta name="description" content="Simple, transparent pricing for Bright Steps child development monitoring.">
    <link rel="icon" type="image/png" href="assets/logo_white.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="styles/globals.css?v=8">
    <link rel="stylesheet" href="styles/landing.css?v=8">
</head>

<body>
    <?php include 'includes/public_header.php'; ?>
    <!-- Add padding to clear the fixed global header -->
    <div class="container" style="padding-top: 8rem;">
        <div class="page-header">
            <h1 class="page-title">Simple, Transparent Pricing</h1>
            <p class="page-subtitle">Start free, upgrade when you're ready</p>
        </div>

        <div class="pricing-grid">
            <?php foreach ($plans as $plan): 
                // Fetch features
                $fStmt = $connect->prepare("SELECT feature_text FROM subscription_feature WHERE subscription_id = ?");
                $fStmt->execute([$plan['subscription_id']]);
                $features = $fStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $isPremium = ($plan['price'] > 0);
            ?>
            <div class="pricing-card <?php echo $isPremium ? 'card-premium' : 'card-free'; ?>">
                <?php if ($isPremium): ?>
                <div class="popular-badge">Most Popular</div>
                <?php endif; ?>
                
                <h3 class="pricing-plan-title <?php echo $isPremium ? 'text-white' : ''; ?>"><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                <p class="pricing-plan-subtitle <?php echo $isPremium ? 'text-light' : ''; ?>"><?php echo htmlspecialchars($plan['description']); ?></p>
                
                <div class="pricing-amount <?php echo $isPremium ? 'text-white' : ''; ?>">
                    $<?php echo htmlspecialchars($plan['price']); ?>
                    <?php if ($plan['price'] > 0): ?>
                    <span class="pricing-period">/<?php echo htmlspecialchars($plan['plan_period']); ?></span>
                    <?php endif; ?>
                </div>
                
                <button class="btn <?php echo $isPremium ? 'btn-white' : 'btn-outline'; ?> btn-lg btn-full" onclick="navigateTo('signup')">
                    <?php echo $isPremium ? 'Start 7-Day Free Trial' : 'Get Started Free'; ?>
                </button>
                
                <div class="pricing-features">
                    <?php foreach ($features as $feature): ?>
                    <div class="pricing-feature <?php echo $isPremium ? 'text-white' : ''; ?>">
                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <span><?php echo htmlspecialchars($feature); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="content-section" style="margin-top: 3rem; text-align: center;">
            <h2>Frequently Asked Questions</h2>
            <p style="margin-bottom: 2rem;">Have questions? We've got answers.</p>
            <div style="text-align: left; max-width: 700px; margin: 0 auto;">
                <p><strong style="color: white;">Can I cancel anytime?</strong><br>Yes! You can cancel your Premium
                    subscription at any time with no penalties.</p>
                <p><strong style="color: white;">Is there a free trial?</strong><br>Premium comes with a 7-day free
                    trial. No credit card required to start.</p>
                <p><strong style="color: white;">What payment methods do you accept?</strong><br>We accept all major
                    credit cards, PayPal, and Apple Pay.</p>
            </div>
        </div>
    </div>
    </main>

    <?php include 'includes/public_footer.php'; ?> <!-- Floating Theme Toggle -->
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

    
    <script src="scripts/language-toggle.js?v=8"></script>
    <script src="scripts/theme-toggle.js?v=8"></script>
    <script src="scripts/navigation.js?v=8"></script>
    <script src="scripts/mobile-menu.js?v=8"></script>
    <script src="scripts/landing.js?v=8"></script>
    <script src="scripts/mega-menu.js?v=8"></script>
</body>

</html>