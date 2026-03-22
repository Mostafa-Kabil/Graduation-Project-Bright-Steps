<?php
/**
 * Bright Steps – Dashboard Router
 * Redirects to the appropriate dashboard based on user role.
 * This file exists for backward compatibility with old links.
 */
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

switch ($_SESSION['role']) {
    case 'admin':
        header("Location: dashboards/admin/admin-dashboard.php");
        break;
    case 'doctor':
    case 'specialist':
        header("Location: dashboards/doctor/doctor-dashboard.php");
        break;
    case 'clinic':
        header("Location: dashboards/clinic/clinic-dashboard.php");
        break;
    default:
        header("Location: dashboards/parent/dashboard.php");
        break;
}
exit;