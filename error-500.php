<?php
// Error 500 - Server Error
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fef2f2 0%, #fef9ee 50%, #f0f4ff 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 2rem;
        }

        [data-theme="dark"] .error-page {
            background: linear-gradient(135deg, #1a0f0f 0%, #1e293b 50%, #0f172a 100%);
        }

        .error-container {
            text-align: center;
            max-width: 520px;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ef4444, #f97316, #eab308);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.5rem;
            animation: shake 4s ease-in-out infinite;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-3px);
            }

            40% {
                transform: translateX(3px);
            }

            60% {
                transform: translateX(-2px);
            }

            80% {
                transform: translateX(2px);
            }
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }

        [data-theme="dark"] .error-title {
            color: #f1f5f9;
        }

        .error-description {
            color: #64748b;
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        [data-theme="dark"] .error-description {
            color: #94a3b8;
        }

        .error-illustration {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .error-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .error-btn-primary {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .error-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .error-btn-secondary {
            background: white;
            color: #ef4444;
            border: 2px solid #e2e8f0;
        }

        [data-theme="dark"] .error-btn-secondary {
            background: #1e293b;
            color: #fca5a5;
            border-color: #334155;
        }

        .error-btn-secondary:hover {
            border-color: #ef4444;
            transform: translateY(-2px);
        }

        .error-logo {
            margin-bottom: 2rem;
        }

        .error-logo img {
            height: 3rem;
            width: auto;
        }
    </style>
</head>

<body>
    <div class="error-page">
        <div class="error-container">
            <div class="error-logo">
                <a href="index.php"><img src="assets/logo.png" alt="Bright Steps"></a>
            </div>
            <div class="error-illustration">⚠️</div>
            <div class="error-code">500</div>
            <h1 class="error-title">Something Went Wrong</h1>
            <p class="error-description">
                We're experiencing a temporary issue on our end.
                Our team has been notified and is working on it. Please try again shortly.
            </p>
            <div class="error-actions">
                <a href="index.php" class="error-btn error-btn-primary">
                    🏠 Go Home
                </a>
                <a href="javascript:location.reload()" class="error-btn error-btn-secondary">
                    🔄 Try Again
                </a>
            </div>
        </div>
    </div>
    <script src="scripts/theme-toggle.js?v=3"></script>
</body>

</html>