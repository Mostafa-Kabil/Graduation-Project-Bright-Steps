<?php
require_once 'connection.php'; // DB connection, auth helpers
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Specialist Profile – Bright Steps</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Detailed profile of a specialist in the Bright Steps platform.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/globals.css">
  <link rel="stylesheet" href="css/specialist-profile.css">
</head>
<body style="margin:0; padding:0;">
  <script>
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
      document.body.classList.add('theme-dark');
    } else {
      document.body.classList.add('theme-light');
    }
  </script>
  
  <!-- We'll include the topbar if user is logged in -->
  <?php if(isset($_SESSION['user_id'])): ?>
    <!-- Basic topbar mock since include 'components/navbar.php' might not exist exactly -->
    <div style="background:#1e293b; color:#fff; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-size:1.5rem; font-weight:bold; color:#10b981;">Bright Steps</div>
        <a href="dashboards/parent/dashboard.php" style="color:#cbd5e1; text-decoration:none;">Dashboard</a>
    </div>
  <?php endif; ?>

  <main id="profile-root" class="profile-container">
    <!-- All content will be injected by specialist-profile.js -->
    <div class="loader">Loading…</div>
  </main>

  <script src="js/specialist-profile.js?v=<?= time() ?>"></script>
</body>
</html>
