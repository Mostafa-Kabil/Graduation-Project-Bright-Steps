<!-- Sidebar -->
<aside class="dashboard-sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-logo">
            <img src="assets/logo.png" alt="Bright Steps" style="height: 2.5rem; width: auto;">
        </a>
        <div class="user-profile">
            <?php
            $text1 = $_SESSION['fname'] ?? 'U';
            $fletter = $text1[0];
            $text2 = $_SESSION['lname'] ?? 'S';
            $lletter = $text2[0];
            ?>
            <div class="user-avatar">
                <?php echo htmlspecialchars($fletter . $lletter); ?>
            </div>
            <div class="user-info">
                <div class="user-name">
                    <?php echo htmlspecialchars($_SESSION['fname'] ?? '') ?>
                    <?php echo htmlspecialchars($_SESSION['lname'] ?? '') ?>
                </div>
                <div class="user-badge-text">
                    <?php echo htmlspecialchars($planname ?? 'Free') ?> Member
                </div>
            </div>
            <div class="user-badge-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                    <path d="M4 22h16" />
                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                </svg>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav" id="sidebar-nav">
        <!-- Nav items will be populated by JavaScript -->
    </nav>

    <div class="sidebar-footer">
        <button class="sidebar-language-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="2" y1="12" x2="22" y2="12" />
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
            </svg>
            <span>عربي</span>
        </button>
        <button class="nav-item" data-view="settings" onclick="window.location.href='settings.php'">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3" />
                <path d="M12 1v6m0 6v6m-9-9h6m6 0h6" />
            </svg>
            <span>Settings</span>
        </button>
        <button class="nav-item nav-item-logout" onclick="handleLogout()">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14l5-5-5-5m5 5H9" />
            </svg>
            <span>Log Out</span>
        </button>
    </div>
</aside>