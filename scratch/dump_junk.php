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
    $junk = substr($output, 0, $pdfPos);
    file_put_contents('scratch/junk_content.txt', "Offset: $pdfPos\nLength: " . strlen($junk) . "\nHex start: " . bin2hex(substr($junk, 0, 100)) . "\n\nContent:\n" . $junk);
    echo "Dumped junk to scratch/junk_content.txt\n";
} else {
    file_put_contents('scratch/junk_content.txt', "No %PDF signature found. Full output length: " . strlen($output) . "\n\nContent:\n" . substr($output, 0, 10000));
    echo "No PDF signature. Dumped start of output to scratch/junk_content.txt\n";
}
