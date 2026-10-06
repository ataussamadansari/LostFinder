<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LostFinder | Verified Vehicle Scan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --obsidian: #0B0F17;
            --surface: #151C28;
            --surface-card: #1E2738;
            --blue: #2563EB;
            --blue-hover: #1D4ED8;
            --white: #FFFFFF;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --emerald: #10B981;
            --emerald-bg: rgba(16, 185, 129, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--obsidian);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 420px;
            background-color: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .header-bar {
            background: linear-gradient(135deg, var(--blue), #1E40AF);
            padding: 24px 20px;
            text-align: center;
        }

        .brand-pill {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .header-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--white);
        }

        .content {
            padding: 28px 24px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--emerald-bg);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--emerald);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .unavailable-card {
            text-align: center;
            padding: 30px 10px;
        }

        .unavailable-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .card-detail {
            background: var(--surface-card);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .reg-number {
            font-size: 24px;
            font-weight: 800;
            color: var(--white);
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .vehicle-meta {
            color: var(--gray-400);
            font-size: 14px;
            margin-bottom: 16px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 13px;
        }

        .detail-label {
            color: var(--gray-400);
        }

        .detail-value {
            color: var(--white);
            font-weight: 600;
        }

        .action-btn {
            display: block;
            width: 100%;
            background-color: var(--blue);
            color: var(--white);
            text-align: center;
            padding: 14px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            margin-bottom: 12px;
        }

        .action-btn:hover {
            background-color: var(--blue-hover);
            transform: translateY(-1px);
        }

        .footer-note {
            text-align: center;
            color: var(--gray-500);
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <span class="brand-pill">LostFinder</span>
            <h1 class="header-title">Vehicle Verification</h1>
        </div>

        <div class="content">
            @if ($payload)
                <div style="text-align: center;">
                    <div class="status-badge">
                        <span>●</span> VERIFIED VEHICLE
                    </div>
                </div>

                <div class="card-detail">
                    <div class="reg-number">{{ $payload['vehicle']['registration_number'] }}</div>
                    <div class="vehicle-meta">{{ $payload['vehicle']['make'] }} {{ $payload['vehicle']['model'] }} &bull; {{ $payload['vehicle']['color'] }}</div>

                    <div class="detail-row">
                        <span class="detail-label">Vehicle Type</span>
                        <span class="detail-value">{{ ucfirst($payload['vehicle']['vehicle_type']) }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Vehicle Code</span>
                        <span class="detail-value">{{ $payload['vehicle']['vehicle_code'] }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Driver Status</span>
                        <span class="detail-value" style="color: var(--emerald);">
                            {{ $payload['driver'] && $payload['driver']['is_verified'] ? 'Verified Driver' : 'Registered' }}
                        </span>
                    </div>
                    @if ($payload['driver'] && $payload['driver']['rating_avg'])
                        <div class="detail-row">
                            <span class="detail-label">Driver Rating</span>
                            <span class="detail-value">★ {{ number_format($payload['driver']['rating_avg'], 1) }}</span>
                        </div>
                    @endif
                </div>

                <a href="/login" class="action-btn">
                    Connect to this Journey
                </a>

                <p class="footer-note">
                    Scanned in vehicle. Safe, encrypted connection point.
                </p>
            @else
                <div class="unavailable-card">
                    <div class="unavailable-icon">⚠️</div>
                    <h2 style="font-size: 20px; margin-bottom: 8px;">QR Code Unavailable</h2>
                    <p style="color: var(--gray-400); font-size: 14px; margin-bottom: 24px;">
                        This LostFinder QR code is currently inactive or invalid.
                    </p>
                    <a href="/" class="action-btn" style="background-color: var(--surface-card);">
                        Return to Homepage
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
