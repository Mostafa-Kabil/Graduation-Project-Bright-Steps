<?php
$apiUrl = 'http://127.0.0.1:8000/docs';
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo json_encode([
    'httpCode' => $httpCode,
    'error'    => $curlError,
    'hasResponse' => !empty($response)
]);
