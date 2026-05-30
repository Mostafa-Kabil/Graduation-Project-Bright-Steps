<?php
session_start();
$_SESSION['id'] = 5100;
$_SESSION['role'] = 'parent';

$_GET['child_id'] = 5200;
$_GET['download'] = '1';
$_GET['type'] = 'child-report';

ob_start();
include 'api_export_pdf.php';
$output = ob_get_clean();

$pdfPos = strpos($output, '%PDF');
if ($pdfPos !== false) {
    echo "Found %PDF at byte offset: $pdfPos\n";
    $junk = substr($output, 0, $pdfPos);
    echo "Junk Length: " . strlen($junk) . " bytes\n";
    echo "--- JUNK HEX DUMP (First 100 bytes) ---\n";
    echo bin2hex(substr($junk, 0, 100)) . "\n";
    echo "--- JUNK TEXT DUMP ---\n";
    echo $junk . "\n";
    echo "--- END OF JUNK ---\n";
} else {
    echo "No %PDF signature found!\n";
}
