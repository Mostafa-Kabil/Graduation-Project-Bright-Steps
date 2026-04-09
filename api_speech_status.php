<?php
/**
 * Speech Analysis API Status Check
 * Returns whether the Python speech server is running and ready
 */
session_start();
header('Content-Type: application/json');
require_once 'connection.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$action = $_GET['action'] ?? 'check';

// Check if Python server is running on port 8000
function isPythonServerRunning($port = 8000) {
    $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

// Try to start the Python server
function startPythonServer() {
    $scriptDir = realpath(__DIR__ . '/APIs/Speech Analysis');
    if (!$scriptDir) return false;

    // Check if server is already running
    if (isPythonServerRunning(8000)) {
        return true;
    }

    // Start the server in background using Windows start command
    $batFile = $scriptDir . '\\start-server.bat';
    if (file_exists($batFile)) {
        // Use Windows start command to run in new process
        pclose(popen('start /B "' . $scriptDir . '" "' . $batFile . '"', 'r'));
    } else {
        // Fallback: try direct Python command
        pclose(popen('cd "' . $scriptDir . '" && start /B python -m uvicorn app:app --port 8000 --host 0.0.0.0 > NUL 2> NUL', 'r'));
    }

    // Wait up to 15 seconds for server to start
    $maxWait = 30;
    while (!isPythonServerRunning(8000) && $maxWait > 0) {
        usleep(500000); // 0.5 second
        $maxWait--;
    }

    return isPythonServerRunning(8000);
}

switch ($action) {
    case 'check':
        $isRunning = isPythonServerRunning(8000);
        echo json_encode([
            'success' => true,
            'running' => $isRunning,
            'message' => $isRunning
                ? 'Speech analysis server is online'
                : 'Speech analysis server is offline. Run APIs/Speech Analysis/start-server.bat to start it.'
        ]);
        break;

    case 'start':
        $started = startPythonServer();
        echo json_encode([
            'success' => $started,
            'running' => $started,
            'message' => $started
                ? 'Speech analysis server started successfully'
                : 'Failed to start server. Please manually run APIs/Speech Analysis/start-server.bat'
        ]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action. Use: check, start']);
}
