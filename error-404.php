<?php
// Error 404 - Page Not Found
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Bright Steps</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="styles/globals.css">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f5e9 50%, #fff3e0 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 2rem;
        }

        [data-theme="dark"] .error-page {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }

        .error-container {
            text-align: center;
            max-width: 520px;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.5rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
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
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .error-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .error-btn-secondary {
            background: white;
            color: #6366f1;
            border: 2px solid #e2e8f0;
        }

        [data-theme="dark"] .error-btn-secondary {
            background: #1e293b;
            color: #a5b4fc;
            border-color: #334155;
        }

        .error-btn-secondary:hover {
            border-color: #6366f1;
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
            <div class="error-illustration">🔍</div>
            <div class="error-code">404</div>
            <h1 class="error-title">Oops! Page Not Found</h1>
            <p class="error-description">
                The page you're looking for doesn't exist or has been moved.
                Don't worry — let's get you back on track!
            </p>
            <div class="error-actions">
                <a href="index.php" class="error-btn error-btn-primary">
                    🏠 Go Home
                </a>
                <a href="dashboard.php" class="error-btn error-btn-secondary">
                    📊 Dashboard
                </a>
            </div>
        </div>
    </div>
    <script src="scripts/theme-toggle.js?v=3"></script>
</body>

</html>