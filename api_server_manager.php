<?php
// api_server_manager.php
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'check_and_start') {
    $speech_port = 8002;
    $motor_port = 8003;
    
    $status = [
        'speech' => 'online',
        'motor' => 'online'
    ];

    // Check Speech Server
    $fp_speech = @fsockopen('127.0.0.1', $speech_port, $errno, $errstr, 1);
    if (!$fp_speech) {
        $scriptDir = realpath(__DIR__ . '/APIs/Speech Analysis');
        if ($scriptDir) {
            pclose(popen('cd "' . $scriptDir . '" && start /B start-server.bat > NUL 2> NUL', "r"));
        }
        $status['speech'] = 'starting';
    } else {
        fclose($fp_speech);
    }

    // Check Motor Server
    $fp_motor = @fsockopen('127.0.0.1', $motor_port, $errno, $errstr, 1);
    if (!$fp_motor) {
        $scriptDir = realpath(__DIR__ . '/APIs/Motor Skills');
        if ($scriptDir) {
            pclose(popen('cd "' . $scriptDir . '" && start /B start-server.bat > NUL 2> NUL', "r"));
        }
        $status['motor'] = 'starting';
    } else {
        fclose($fp_motor);
    }

    echo json_encode(['success' => true, 'servers' => $status]);
    exit;
}
