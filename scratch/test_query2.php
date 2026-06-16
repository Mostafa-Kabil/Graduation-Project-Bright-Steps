<?php
require 'connection.php';
$specialist_id = 230;
$stmt_week = $connect->prepare("SELECT SUM(CASE WHEN scheduled_at >= CURDATE() AND scheduled_at < CURDATE() + INTERVAL 7 DAY AND status IN ('pending', 'confirmed', 'pending reschedule') THEN 1 ELSE 0 END) FROM appointment WHERE specialist_id = :sid");
$stmt_week->execute([':sid' => $specialist_id]);
$this_week_kpi = (int)$stmt_week->fetchColumn();
echo "KPI: " . $this_week_kpi;
