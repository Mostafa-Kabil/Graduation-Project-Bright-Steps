<?php
session_start();
include "connection.php";

$mStmt = $connect->prepare("SELECT setting_value FROM system_config WHERE setting_key = 'maintenance_mode'");
$mStmt->execute();
if ($mStmt->fetchColumn() !== '1') {
    // If not in maintenance, redirect to index
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--blue-50), var(--purple-50));
            font-family: 'Inter', system-ui, sans-serif;
            margin: 0;
            padding: 1rem;
            text-align: center;
        }
        .maintenance-card {
            background: var(--bg-card, #ffffff);
            padding: 3rem 2rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            max-width: 500px;
            width: 100%;
            border: 1px solid var(--border, #e2e8f0);
            position: relative;
            overflow: hidden;
        }
        .maintenance-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
        }
        .icon-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #eff6ff, #f3e8ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: pulse 3s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(139, 92, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
        }
        .icon-container svg {
            width: 40px;
            height: 40px;
            stroke: #8b5cf6;
        }
        h1 {
            color: var(--text-primary, #1e293b);
            font-size: 2rem;
            margin: 0 0 1rem;
            font-weight: 800;
        }
        p {
            color: var(--text-secondary, #64748b);
            font-size: 1.1rem;
            line-height: 1.6;
            margin: 0 0 2rem;
        }
        .btn-admin {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            color: var(--slate-500, #64748b);
            border: 1px solid var(--border, #e2e8f0);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-admin:hover {
            background: var(--slate-50, #f8fafc);
            color: var(--slate-700, #334155);
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="icon-container">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/>
            </svg>
        </div>
        <h1>We're updating Bright Steps!</h1>
        <p>Our platform is currently undergoing scheduled maintenance to improve your experience. We'll be back online shortly. Thank you for your patience!</p>
        
        <a href="admin-login.php" class="btn-admin">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            Admin Access
        </a>
    </div>
</body>
</html>
