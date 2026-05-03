<?php
/**
 * Bright Steps - Admin Points Management Dashboard
 */
session_start();
require_once "../connection.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch summary stats
try {
    $stmt = $connect->query("SELECT COALESCE(SUM(total_points), 0) FROM parent_points_wallet");
    $totalPointsInCirculation = $stmt->fetchColumn();

    $stmt = $connect->query("SELECT COUNT(*) FROM parent_points_wallet WHERE total_points > 0");
    $activeWallets = $stmt->fetchColumn();

    $stmt = $connect->query("SELECT COALESCE(SUM(lifetime_earned), 0) FROM parent_points_wallet");
    $totalEarned = $stmt->fetchColumn();

    $stmt = $connect->query("SELECT COALESCE(SUM(lifetime_redeemed), 0) FROM parent_points_wallet");
    $totalRedeemed = $stmt->fetchColumn();

    $stmt = $connect->query("SELECT COUNT(*) FROM redemption_catalog WHERE is_active = 1");
    $catalogItems = $stmt->fetchColumn();

    $stmt = $connect->query("SELECT COUNT(*) FROM appointment_tokens WHERE status = 'available'");
    $availableTokens = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalPointsInCirculation = $totalEarned = $totalRedeemed = $activeWallets = $catalogItems = $availableTokens = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Points Management - Bright Steps Admin</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="../styles/globals.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-500: #64748b;
            --slate-700: #334155;
            --slate-900: #1e293b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: var(--slate-50); color: var(--slate-900); }

        .admin-layout { display: flex; min-height: 100vh; }

        /* Sidebar */
        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-sidebar-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-sidebar-header img { height: 2.5rem; }
        .admin-sidebar-header span { font-size: 1.25rem; font-weight: 700; }

        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: var(--slate-300);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }

        .admin-nav-item:hover, .admin-nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .admin-nav-item svg { width: 20px; height: 20px; }

        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .admin-title { font-size: 1.75rem; font-weight: 700; color: var(--slate-900); }
        .admin-subtitle { color: var(--slate-500); margin-top: 0.25rem; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card-icon.purple { background: #ede9fe; }
        .stat-card-icon.green { background: #dcfce7; }
        .stat-card-icon.blue { background: #dbeafe; }
        .stat-card-icon.orange { background: #ffedd5; }

        .stat-card-value { font-size: 1.75rem; font-weight: 700; color: var(--slate-900); }
        .stat-card-label { color: var(--slate-500); font-size: 0.875rem; }

        /* Tabs */
        .tabs-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .tabs-header {
            display: flex;
            border-bottom: 2px solid var(--slate-100);
        }

        .tab-btn {
            flex: 1;
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            color: var(--slate-500);
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn:hover { background: var(--slate-50); }
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content { padding: 1.5rem; display: none; }
        .tab-content.active { display: block; }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--slate-100);
        }

        .data-table th {
            background: var(--slate-50);
            font-weight: 600;
            color: var(--slate-700);
            font-size: 0.875rem;
        }

        .data-table tr:hover { background: var(--slate-50); }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--slate-700); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--slate-200);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-outline { border: 2px solid var(--slate-200); background: none; color: var(--slate-700); }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-green { background: #dcfce7; color: #166534; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--slate-200);
        }

        .modal-body { padding: 1.5rem; }
        .modal-footer {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--slate-200);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--slate-500);
        }

        /* Catalog Grid */
        .catalog-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .catalog-admin-item {
            background: var(--slate-50);
            border: 2px solid var(--slate-200);
            border-radius: 10px;
            padding: 1rem;
        }

        .catalog-admin-item-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .catalog-admin-item-icon { font-size: 1.5rem; }
        .catalog-admin-item-name { font-weight: 600; flex: 1; }
        .catalog-admin-item-type {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background: var(--slate-200);
        }

        .catalog-admin-item-cost {
            font-weight: 700;
            color: var(--primary);
            margin: 0.5rem 0;
        }

        .catalog-admin-item-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        /* Leaderboard */
        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: var(--slate-50);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .leaderboard-rank {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
        }

        .rank-1 { background: #fbbf24; color: #78350f; }
        .rank-2 { background: #94a3b8; color: #1e293b; }
        .rank-3 { background: #b45309; color: white; }
        .rank-other { background: var(--slate-200); color: var(--slate-700); }

        .leaderboard-info { flex: 1; }
        .leaderboard-name { font-weight: 600; }
        .leaderboard-points { color: var(--primary); font-weight: 700; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <img src="../assets/logo.png" alt="Bright Steps">
                <span>Admin Panel</span>
            </div>
            <nav>
                <a href="overview.php" class="admin-nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Dashboard
                </a>
                <a href="users.php" class="admin-nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Users
                </a>
                <a href="clinics.php" class="admin-nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M3 10h18"/><path d="M10 14h4"/><path d="M12 12v4"/>
                    </svg>
                    Clinics
                </a>
                <a href="points-management.php" class="admin-nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Points System
                </a>
                <a href="notifications_mgmt.php" class="admin-nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    Notifications
                </a>
                <a href="../dashboards/parent/dashboard.php" class="admin-nav-item" target="_blank">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    View Site
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <div>
                    <h1 class="admin-title">Points System Management</h1>
                    <p class="admin-subtitle">Configure earning rules, manage redemptions, and monitor engagement</p>
                </div>
                <button class="btn btn-primary" onclick="openAddRuleModal()">+ Add Earning Rule</button>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-value"><?php echo number_format($totalPointsInCirculation); ?></div>
                            <div class="stat-card-label">Points in Circulation</div>
                        </div>
                        <div class="stat-card-icon purple">💎</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-value"><?php echo number_format($totalEarned); ?></div>
                            <div class="stat-card-label">Total Points Earned</div>
                        </div>
                        <div class="stat-card-icon green">📈</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-value"><?php echo number_format($totalRedeemed); ?></div>
                            <div class="stat-card-label">Total Points Redeemed</div>
                        </div>
                        <div class="stat-card-icon blue">🎁</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-value"><?php echo $activeWallets; ?> / <?php echo $availableTokens; ?></div>
                            <div class="stat-card-label">Active Wallets / Tokens</div>
                        </div>
                        <div class="stat-card-icon orange">🎫</div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="switchTab('rules')">Earning Rules</button>
                    <button class="tab-btn" onclick="switchTab('catalog')">Redemption Catalog</button>
                    <button class="tab-btn" onclick="switchTab('redemptions')">Redemptions</button>
                    <button class="tab-btn" onclick="switchTab('leaderboard')">Leaderboard</button>
                    <button class="tab-btn" onclick="switchTab('transactions')">Transactions</button>
                </div>

                <!-- Earning Rules Tab -->
                <div class="tab-content active" id="tab-rules">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <h3>Earning Rules Configuration</h3>
                        <button class="btn btn-outline btn-sm" onclick="loadRules()">Refresh</button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Action Key</th>
                                <th>Action Name</th>
                                <th>Points</th>
                                <th>Daily Cap</th>
                                <th>Weekly Cap</th>
                                <th>Cooldown</th>
                                <th>Verification</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rules-table-body">
                            <tr><td colspan="8" style="text-align:center;color:var(--slate-500);">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Redemption Catalog Tab -->
                <div class="tab-content" id="tab-catalog">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <h3>Redemption Catalog</h3>
                        <button class="btn btn-primary btn-sm" onclick="openAddCatalogItemModal()">+ Add Item</button>
                    </div>
                    <div class="catalog-admin-grid" id="catalog-grid">
                        <p style="color:var(--slate-500);text-align:center;grid-column:1/-1;">Loading...</p>
                    </div>
                </div>

                <!-- Redemptions Tab -->
                <div class="tab-content" id="tab-redemptions">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <h3>Recent Redemptions</h3>
                        <select class="form-select" id="redemptions-filter" onchange="loadRedemptions()" style="padding:0.5rem 1rem;border-radius:6px;border:1px solid var(--slate-200);">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="used">Used</option>
                            <option value="expired">Expired</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Parent</th>
                                <th>Item</th>
                                <th>Points</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="redemptions-table-body">
                            <tr><td colspan="7" style="text-align:center;color:var(--slate-500);">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Leaderboard Tab -->
                <div class="tab-content" id="tab-leaderboard">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <h3>Top Points Earners</h3>
                        <button class="btn btn-outline btn-sm" onclick="loadLeaderboard()">Refresh</button>
                    </div>
                    <div id="leaderboard-container">
                        <p style="color:var(--slate-500);text-align:center;">Loading...</p>
                    </div>
                </div>

                <!-- Transactions Tab -->
                <div class="tab-content" id="tab-transactions">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <h3>Recent Transactions</h3>
                        <button class="btn btn-outline btn-sm" onclick="loadTransactions()">Refresh</button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Parent</th>
                                <th>Action</th>
                                <th>Points</th>
                                <th>Type</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="transactions-table-body">
                            <tr><td colspan="6" style="text-align:center;color:var(--slate-500);">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Rule Modal -->
    <div class="modal" id="rule-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="rule-modal-title">Add Earning Rule</h3>
                <button class="modal-close" onclick="closeRuleModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rule-id">
                <div class="form-group">
                    <label>Action Key (unique identifier)</label>
                    <input type="text" id="rule-action-key" placeholder="e.g., daily_login">
                </div>
                <div class="form-group">
                    <label>Action Name (display name)</label>
                    <input type="text" id="rule-action-name" placeholder="e.g., Daily Login">
                </div>
                <div class="form-group">
                    <label>Points Value</label>
                    <input type="number" id="rule-points-value" placeholder="e.g., 10">
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Daily Cap (optional)</label>
                        <input type="number" id="rule-daily-cap" placeholder="e.g., 10">
                    </div>
                    <div class="form-group">
                        <label>Weekly Cap (optional)</label>
                        <input type="number" id="rule-weekly-cap" placeholder="e.g., 70">
                    </div>
                </div>
                <div class="form-group">
                    <label>Cooldown (minutes)</label>
                    <input type="number" id="rule-cooldown" placeholder="e.g., 1440 for 24 hours">
                </div>
                <div class="form-group">
                    <label>Requires Verification</label>
                    <select id="rule-verification">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="rule-description" rows="2" placeholder="Brief description of the action"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeRuleModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveRule()">Save Rule</button>
            </div>
        </div>
    </div>

    <!-- Add Catalog Item Modal -->
    <div class="modal" id="catalog-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="catalog-modal-title">Add Catalog Item</h3>
                <button class="modal-close" onclick="closeCatalogModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="catalog-item-id">
                <div class="form-group">
                    <label>Item Type</label>
                    <select id="catalog-item-type">
                        <option value="appointment">Appointment</option>
                        <option value="service">Service</option>
                        <option value="badge">Badge</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" id="catalog-item-name" placeholder="e.g., Appointment Token (25% off)">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="catalog-item-description" rows="2"></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Points Cost</label>
                        <input type="number" id="catalog-points-cost" placeholder="e.g., 500">
                    </div>
                    <div class="form-group">
                        <label>Original Price ($)</label>
                        <input type="number" step="0.01" id="catalog-original-price" placeholder="e.g., 50.00">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Icon (emoji)</label>
                        <input type="text" id="catalog-icon" placeholder="e.g., 🎫" value="🎁">
                    </div>
                    <div class="form-group">
                        <label>Badge Color</label>
                        <select id="catalog-badge-color">
                            <option value="green">Green</option>
                            <option value="blue" selected>Blue</option>
                            <option value="purple">Purple</option>
                            <option value="orange">Orange</option>
                            <option value="yellow">Yellow</option>
                            <option value="red">Red</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Max Redemptions Per User (optional)</label>
                    <input type="number" id="catalog-max-redemptions" placeholder="Leave empty for unlimited">
                </div>
                <div class="form-group">
                    <label>Valid Until (optional)</label>
                    <input type="date" id="catalog-valid-until">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeCatalogModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveCatalogItem()">Save Item</button>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.querySelector(`.tab-btn[onclick="switchTab('${tabId}')"]`).classList.add('active');
            document.getElementById(`tab-${tabId}`).classList.add('active');

            // Load data for the tab
            if (tabId === 'rules') loadRules();
            else if (tabId === 'catalog') loadCatalog();
            else if (tabId === 'redemptions') loadRedemptions();
            else if (tabId === 'leaderboard') loadLeaderboard();
            else if (tabId === 'transactions') loadTransactions();
        }

        // Load earning rules
        async function loadRules() {
            try {
                const res = await fetch('../api_parent_points.php?action=rules');
                const data = await res.json();

                const tbody = document.getElementById('rules-table-body');
                if (!data.success || !data.rules.length) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--slate-500);">No rules found</td></tr>';
                    return;
                }

                tbody.innerHTML = data.rules.map(rule => `
                    <tr>
                        <td><code>${rule.action_key}</code></td>
                        <td>${rule.action_name}</td>
                        <td><span class="badge badge-purple">${rule.points_value} pts</span></td>
                        <td>${rule.daily_cap || '∞'}</td>
                        <td>${rule.weekly_cap || '∞'}</td>
                        <td>${rule.cooldown_minutes || 0} min</td>
                        <td>${rule.requires_verification ? '<span class="badge badge-yellow">Yes</span>' : '<span class="badge badge-green">No</span>'}</td>
                        <td>
                            <button class="btn btn-outline btn-sm" onclick="editRule('${rule.action_key}')">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteRule('${rule.action_key}')">Delete</button>
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                console.error(e);
                document.getElementById('rules-table-body').innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--danger);">Failed to load rules</td></tr>';
            }
        }

        // Rule modal functions
        function openAddRuleModal() {
            document.getElementById('rule-modal-title').textContent = 'Add Earning Rule';
            document.getElementById('rule-id').value = '';
            document.getElementById('rule-action-key').value = '';
            document.getElementById('rule-action-name').value = '';
            document.getElementById('rule-points-value').value = '';
            document.getElementById('rule-daily-cap').value = '';
            document.getElementById('rule-weekly-cap').value = '';
            document.getElementById('rule-cooldown').value = '';
            document.getElementById('rule-verification').value = '0';
            document.getElementById('rule-description').value = '';
            document.getElementById('rule-modal').classList.add('active');
        }

        function closeRuleModal() {
            document.getElementById('rule-modal').classList.remove('active');
        }

        async function saveRule() {
            const ruleData = {
                action: 'add_rule',
                action_name: document.getElementById('rule-action-name').value,
                points_value: parseInt(document.getElementById('rule-points-value').value) || 0,
                daily_cap: document.getElementById('rule-daily-cap').value ? parseInt(document.getElementById('rule-daily-cap').value) : null,
                weekly_cap: document.getElementById('rule-weekly-cap').value ? parseInt(document.getElementById('rule-weekly-cap').value) : null,
                cooldown_minutes: document.getElementById('rule-cooldown').value ? parseInt(document.getElementById('rule-cooldown').value) : 0,
                requires_verification: parseInt(document.getElementById('rule-verification').value),
                description: document.getElementById('rule-description').value
            };

            try {
                const res = await fetch('../api_parent_points.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(ruleData)
                });
                const data = await res.json();

                if (data.success) {
                    alert('Rule saved successfully!');
                    closeRuleModal();
                    loadRules();
                } else {
                    alert('Error: ' + (data.error || 'Failed to save rule'));
                }
            } catch (e) {
                alert('Network error');
            }
        }

        async function deleteRule(actionKey) {
            if (!confirm('Are you sure you want to delete this rule?')) return;

            // Note: You'd need to add a delete_rule endpoint to the API
            alert('Delete functionality - implement API endpoint for rule deletion');
        }

        // Catalog functions
        async function loadCatalog() {
            try {
                const res = await fetch('../api_redemption_catalog.php?action=list');
                const data = await res.json();

                const container = document.getElementById('catalog-grid');
                if (!data.success || !data.items.length) {
                    container.innerHTML = '<p style="color:var(--slate-500);text-align:center;grid-column:1/-1;">No catalog items found</p>';
                    return;
                }

                container.innerHTML = data.items.map(item => `
                    <div class="catalog-admin-item">
                        <div class="catalog-admin-item-header">
                            <span class="catalog-admin-item-icon">${item.icon || '🎁'}</span>
                            <span class="catalog-admin-item-name">${item.item_name}</span>
                            <span class="catalog-admin-item-type">${item.item_type}</span>
                        </div>
                        <div style="color:var(--slate-500);font-size:0.875rem;margin-bottom:0.5rem;">${item.description || 'No description'}</div>
                        <div class="catalog-admin-item-cost">${item.points_cost.toLocaleString()} points</div>
                        ${item.original_price ? `<div style="font-size:0.75rem;color:var(--slate-500);">Value: $${item.original_price}</div>` : ''}
                        <div class="catalog-admin-item-actions">
                            <button class="btn btn-outline btn-sm" onclick="editCatalogItem(${item.item_id})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteCatalogItem(${item.item_id})">Delete</button>
                        </div>
                    </div>
                `).join('');
            } catch (e) {
                console.error(e);
                document.getElementById('catalog-grid').innerHTML = '<p style="color:var(--danger);text-align:center;grid-column:1/-1;">Failed to load catalog</p>';
            }
        }

        function openAddCatalogItemModal() {
            document.getElementById('catalog-modal-title').textContent = 'Add Catalog Item';
            document.getElementById('catalog-item-id').value = '';
            document.getElementById('catalog-item-type').value = 'appointment';
            document.getElementById('catalog-item-name').value = '';
            document.getElementById('catalog-item-description').value = '';
            document.getElementById('catalog-points-cost').value = '';
            document.getElementById('catalog-original-price').value = '';
            document.getElementById('catalog-icon').value = '🎁';
            document.getElementById('catalog-badge-color').value = 'blue';
            document.getElementById('catalog-max-redemptions').value = '';
            document.getElementById('catalog-valid-until').value = '';
            document.getElementById('catalog-modal').classList.add('active');
        }

        function closeCatalogModal() {
            document.getElementById('catalog-modal').classList.remove('active');
        }

        async function saveCatalogItem() {
            const itemData = {
                action: 'create_item',
                item_type: document.getElementById('catalog-item-type').value,
                item_name: document.getElementById('catalog-item-name').value,
                description: document.getElementById('catalog-item-description').value,
                points_cost: parseInt(document.getElementById('catalog-points-cost').value) || 0,
                original_price: parseFloat(document.getElementById('catalog-original-price').value) || 0,
                icon: document.getElementById('catalog-icon').value,
                badge_color: document.getElementById('catalog-badge-color').value,
                max_redemptions_per_user: document.getElementById('catalog-max-redemptions').value ? parseInt(document.getElementById('catalog-max-redemptions').value) : null,
                valid_until: document.getElementById('catalog-valid-until').value || null
            };

            try {
                const res = await fetch('../api_redemption_catalog.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(itemData)
                });
                const data = await res.json();

                if (data.success) {
                    alert('Catalog item created!');
                    closeCatalogModal();
                    loadCatalog();
                } else {
                    alert('Error: ' + (data.error || 'Failed to create item'));
                }
            } catch (e) {
                alert('Network error');
            }
        }

        function editCatalogItem(itemId) {
            alert('Edit functionality - load item details and populate modal');
        }

        function deleteCatalogItem(itemId) {
            if (!confirm('Are you sure you want to delete this item?')) return;
            alert('Delete functionality - implement API endpoint');
        }

        // Load redemptions
        async function loadRedemptions() {
            const status = document.getElementById('redemptions-filter').value;
            try {
                const res = await fetch(`../api_redemption_catalog.php?action=all_redemptions&status=${status}`);
                const data = await res.json();

                const tbody = document.getElementById('redemptions-table-body');
                if (!data.success || !data.redemptions.length) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--slate-500);">No redemptions found</td></tr>';
                    return;
                }

                tbody.innerHTML = data.redemptions.map(r => {
                    const statusBadge = r.status === 'active' ? 'badge-green' :
                                       r.status === 'used' ? 'badge-blue' :
                                       r.status === 'expired' ? 'badge-yellow' : 'badge-purple';
                    return `
                        <tr>
                            <td>#${r.redemption_id}</td>
                            <td>${r.parent_email || 'N/A'}</td>
                            <td>${r.item_name}</td>
                            <td>${r.points_used} pts</td>
                            <td><span class="badge ${statusBadge}">${r.status}</span></td>
                            <td>${new Date(r.created_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-outline btn-sm" onclick="viewRedemption(${r.redemption_id})">View</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (e) {
                console.error(e);
                document.getElementById('redemptions-table-body').innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--danger);">Failed to load redemptions</td></tr>';
            }
        }

        function viewRedemption(id) {
            alert('View redemption details - implement modal');
        }

        // Load leaderboard
        async function loadLeaderboard() {
            try {
                const res = await fetch('../api_parent_points.php?action=summary');
                // Note: You'd need a dedicated leaderboard endpoint
                // For now, show placeholder
                document.getElementById('leaderboard-container').innerHTML = `
                    <div style="text-align:center;color:var(--slate-500);padding:2rem;">
                        <p>Leaderboard feature - implement dedicated API endpoint</p>
                        <p style="margin-top:0.5rem;">Use the v_weekly_points_leaderboard view</p>
                    </div>
                `;
            } catch (e) {
                console.error(e);
            }
        }

        // Load transactions
        async function loadTransactions() {
            try {
                const res = await fetch('../api_parent_points.php?action=history&limit=50');
                const data = await res.json();

                const tbody = document.getElementById('transactions-table-body');
                if (!data.success || !data.transactions.length) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--slate-500);">No transactions found</td></tr>';
                    return;
                }

                tbody.innerHTML = data.transactions.map(tx => {
                    const isPositive = tx.points_change > 0;
                    return `
                        <tr>
                            <td>#${tx.transaction_id}</td>
                            <td>Parent #${tx.parent_id || 'N/A'}</td>
                            <td>${tx.action_name || (isPositive ? 'Points earned' : 'Redemption')}</td>
                            <td style="color:${isPositive ? 'var(--success)' : 'var(--danger)'};font-weight:600;">
                                ${isPositive ? '+' : ''}${tx.points_change}
                            </td>
                            <td><span class="badge ${isPositive ? 'badge-green' : 'badge-purple'}">${tx.transaction_type}</span></td>
                            <td>${new Date(tx.created_at).toLocaleString()}</td>
                        </tr>
                    `;
                }).join('');
            } catch (e) {
                console.error(e);
                document.getElementById('transactions-table-body').innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--danger);">Failed to load transactions</td></tr>';
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadRules();
            loadCatalog();
        });
    </script>
</body>
</html>
