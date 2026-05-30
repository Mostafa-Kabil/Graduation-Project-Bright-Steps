<?php
// Simulate session for doctor
session_start();
$_SESSION['id'] = 4;
$_SESSION['role'] = 'doctor';
$_SESSION['specialist_id'] = 4;

// Simulate GET parameters
$_GET['child_id'] = 5201;
$_GET['download'] = '1';
$_GET['type'] = 'growth-report';

// Capture output
ob_start();
try {
    include 'api_export_pdf.php';
} catch (Throwable $e) {
    echo "\nFATAL EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
$output = ob_get_clean();

// Check if output starts with PDF signature (%PDF) or has other text first
if (empty($output)) {
    echo "ERROR: Output is completely empty!\n";
} else {
    $prefix = substr($output, 0, 200);
    echo "--- OUTPUT START (First 200 bytes) ---\n";
    echo $prefix . "\n";
    echo "--------------------------------------\n";
    
    if (strpos($output, '%PDF') === 0) {
        echo "SUCCESS: Output starts with %PDF signature!\n";
        echo "Total length: " . strlen($output) . " bytes\n";
        file_put_contents('scratch/test_output.pdf', $output);
        echo "Saved to scratch/test_output.pdf\n";
    } else {
        echo "FAILURE: Output does NOT start with %PDF!\n";
        echo "It starts with: " . bin2hex(substr($output, 0, 10)) . " (hex)\n";
        $pdfPos = strpos($output, '%PDF');
        if ($pdfPos !== false) {
            echo "Found %PDF at byte offset: $pdfPos\n";
            echo "--- LEADING JUNK PREVIEW (First 1000 chars) ---\n";
            echo substr($output, 0, min($pdfPos, 1000)) . "\n";
            echo "--- END LEADING JUNK ---\n";
        } else {
            echo "No %PDF signature found anywhere in the output!\n";
            echo "Full output length: " . strlen($output) . " bytes\n";
            echo "Output content:\n" . substr($output, 0, 2000) . "\n";
        }
    }
}
