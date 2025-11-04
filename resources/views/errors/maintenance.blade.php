<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Maintenance - E-Lingkod Dasol HRIS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .maintenance-container {
            background: white;
            border-radius: 16px;
            padding: 48px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            margin-bottom: 32px;
        }

        .system-name {
            font-size: 28px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 8px;
        }

        .municipality {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .system-type {
            font-size: 14px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .maintenance-icon {
            width: 120px;
            height: 120px;
            margin: 32px auto;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .maintenance-icon svg {
            width: 60px;
            height: 60px;
            fill: white;
        }

        .maintenance-title {
            font-size: 24px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
        }

        .maintenance-message {
            font-size: 16px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .progress-section {
            margin: 32px 0;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
            width: 60%;
            animation: loading 2s ease-in-out infinite;
        }

        @keyframes loading {
            0% {
                width: 0%;
            }
            50% {
                width: 80%;
            }
            100% {
                width: 100%;
            }
        }

        .progress-text {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .auto-refresh {
            font-size: 14px;
            color: #94a3b8;
            margin-top: 16px;
        }

        .contact-section {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        .contact-title {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }

        .contact-info {
            font-size: 14px;
            color: #64748b;
            line-height: 1.5;
        }

        @media (max-width: 640px) {
            .maintenance-container {
                padding: 32px 24px;
                margin: 16px;
            }

            .system-name {
                font-size: 24px;
            }

            .maintenance-title {
                font-size: 20px;
            }

            .maintenance-message {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="logo-section">
            <div class="system-name">E-Lingkod Dasol</div>
            <div class="municipality">Municipality of Dasol, Pangasinan</div>
            <div class="system-type">Human Resource Information System</div>
        </div>

        <div class="maintenance-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
            </svg>
        </div>

        <h1 class="maintenance-title">System Maintenance in Progress</h1>

        <div class="maintenance-message">
            {{ $message ?? 'We are currently performing essential system updates and database migrations. This ensures the best performance and security for your HRIS system.' }}
        </div>

        <div class="progress-section">
            <div class="progress-text">Initializing system components...</div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="progress-text">This usually takes just a few moments.</div>
        </div>

        <div class="auto-refresh">
            This page will automatically refresh when the system is ready.
        </div>

        <div class="contact-section">
            <div class="contact-title">Need Assistance?</div>
            <div class="contact-info">
                If this page persists for more than a few minutes,<br>
                please contact the HR Office or Municipal IT Support.
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 10 seconds
        setTimeout(function() {
            window.location.reload();
        }, 10000);

        // Add some dynamic messages based on time
        const messages = [
            'Setting up database connections...',
            'Running system migrations...',
            'Configuring application settings...',
            'Optimizing system performance...',
            'Finalizing system setup...'
        ];

        let messageIndex = 0;
        setInterval(function() {
            const progressText = document.querySelector('.progress-text');
            if (progressText) {
                progressText.textContent = messages[messageIndex % messages.length];
                messageIndex++;
            }
        }, 4000);
    </script>
</body>
</html>