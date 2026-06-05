<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>{{ $serverName ?? 'MU Archangel H5' }}</title>
    
    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CCGame SDK Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/sdk/ccgame-sdk.css') }}?v={{ filemtime(public_path('assets/sdk/ccgame-sdk.css')) }}">
    
    <style>
        :root {
            --bg-dark: #07070a;
            --bg-card: rgba(17, 17, 24, 0.45);
            --gold-primary: #c9a94e;
            --gold-hover: #dfbe5e;
            --gold-glow: rgba(201, 169, 78, 0.25);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: rgba(201, 169, 78, 0.15);
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Plus Sans Jakarta', sans-serif;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: var(--font-body);
            -webkit-font-smoothing: antialiased;
        }

        /* Unified Iframe Layout */
        #game-frame {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
            z-index: 1;
            background: #000;
        }

        /* Beautiful Golden Overlay Loader */
        #game-loader {
            position: fixed;
            inset: 0;
            z-index: 100;
            background-color: var(--bg-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            transition: opacity 0.4s cubic-bezier(0.25, 1, 0.5, 1), visibility 0.4s;
        }

        .loader-spinner {
            position: relative;
            width: 3.5rem;
            height: 3.5rem;
        }

        .loader-ring {
            box-sizing: border-box;
            display: block;
            position: absolute;
            width: 3.5rem;
            height: 3.5rem;
            border: 3px solid transparent;
            border-radius: 50%;
            animation: spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            border-top-color: var(--gold-primary);
        }
        
        .loader-ring:nth-child(1) { animation-delay: -0.45s; }
        .loader-ring:nth-child(2) { animation-delay: -0.3s; }
        .loader-ring:nth-child(3) { animation-delay: -0.15s; }

        .loader-text {
            font-family: var(--font-display);
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-main);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-shadow: 0 0 10px var(--gold-glow);
        }

        .loader-subtext {
            font-size: 0.75rem;
            font-family: monospace;
            color: var(--text-muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        /* Premium Glassmorphic Fallback Screen */
        .fallback-container {
            position: fixed;
            inset: 0;
            z-index: 200;
            background: radial-gradient(circle at center, #14141e 0%, #07070a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .fallback-card {
            width: 100%;
            max-width: 26rem;
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 1.25rem;
            padding: 2.25rem 2rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 40px rgba(201, 169, 78, 0.03);
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .fallback-icon-wrap {
            margin: 0 auto 1.5rem;
            width: 3.5rem;
            height: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(201, 169, 78, 0.08);
            border: 1px solid rgba(201, 169, 78, 0.2);
            box-shadow: 0 0 20px rgba(201, 169, 78, 0.05);
        }

        .fallback-icon {
            font-size: 1.5rem;
            color: var(--gold-primary);
        }

        .fallback-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-main);
            margin: 0 0 0.75rem 0;
            letter-spacing: -0.01em;
        }

        .fallback-desc {
            font-size: 0.875rem;
            line-height: 1.6;
            color: var(--text-muted);
            margin: 0 0 1.75rem 0;
        }

        /* Beautiful Golden CTA Button */
        .fallback-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.85rem 1.5rem;
            font-family: var(--font-display);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #07070a;
            background: var(--gold-primary);
            border: none;
            border-radius: 0.75rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px var(--gold-glow);
        }

        .fallback-btn:hover {
            background: var(--gold-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(201, 169, 78, 0.4);
        }

        .fallback-btn:active {
            transform: translateY(0);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    @if($playAllowed)
        <!-- Beautiful Auto-hiding Loading Screen -->
        <div id="game-loader">
            <div class="loader-spinner">
                <div class="loader-ring"></div>
                <div class="loader-ring"></div>
                <div class="loader-ring"></div>
                <div class="loader-ring"></div>
            </div>
            <div class="loader-text">Đang kết nối phiên...</div>
            <div class="loader-subtext">{{ $serverName }} • Sẵn sàng khởi hành</div>
        </div>

        <!-- Define hideLoader BEFORE the iframe: a cached/instant onload must not fire before the function exists -->
        <script>
            function hideLoader() {
                const loader = document.getElementById('game-loader');
                if (loader) {
                    loader.style.opacity = '0';
                    setTimeout(() => {
                        loader.style.display = 'none';
                    }, 400);
                }
            }
            // Fail-safe auto-hide loader after 15 seconds in case WebGL initialization takes too long
            setTimeout(hideLoader, 15000);
        </script>

        <!-- Fullscreen Game Iframe -->
        <iframe
            id="game-frame"
            src="{{ $gameUrl }}"
            allow="autoplay; fullscreen"
            referrerpolicy="no-referrer"
            scrolling="no"
            loading="eager"
            onload="hideLoader()"
        ></iframe>
    @else
        <!-- Beautiful Glassmorphism Fallback Screen -->
        <div class="fallback-container">
            <div class="fallback-card">
                <div class="fallback-icon-wrap">
                    @if(($errorReason ?? '') === 'invalid_launch')
                        <!-- Expiration / Warning icon -->
                        <span class="fallback-icon">🕒</span>
                    @else
                        <!-- Shield / Portal entry icon -->
                        <span class="fallback-icon">🛡️</span>
                    @endif
                </div>
                
                <h1 class="fallback-title">
                    @if(($errorReason ?? '') === 'invalid_launch')
                        Phiên chơi đã hết hạn
                    @else
                        Lối vào trò chơi yêu cầu xác thực
                    @endif
                </h1>
                
                <p class="fallback-desc">
                    @if(($errorReason ?? '') === 'invalid_launch')
                        Phiên chơi này đã hết hạn hoặc không còn hợp lệ. Vui lòng mở lại trò chơi từ CCGame để bắt đầu phiên mới.
                    @else
                        Bạn cần đăng nhập và mở trò chơi trực tiếp từ CCGame để tạo kết nối an toàn.
                    @endif
                </p>
                
                <a class="fallback-btn" href="{{ config('portal.url') ?: 'https://ccgame.org' }}" target="_parent">
                    Về CCGame
                </a>
            </div>
        </div>
    @endif

    @if($playAllowed)
    <!-- CCGame SDK Root Container for Overlay Integrations -->
    <div id="ccgame-sdk-root"
         data-user="{{ $user ?? '' }}"
         data-server-id="{{ $serverId ?? '' }}"
         data-server-name="{{ $serverName ?? '' }}"
         data-auth-mode="{{ $authMode ?? 'guest' }}"
         data-display-name="{{ $displayName ?? 'Khách' }}"
         data-expires-at="{{ $expiresAt ?? '' }}"
         data-return-url="{{ config('portal.url') ?: 'https://ccgame.org' }}">
    </div>
    
    <!-- CCGame SDK JavaScript Core -->
    <script>window.ccgame = { user: "{{ $user ?? '' }}" };</script>
    <script type="module" src="{{ asset('assets/sdk/ccgame-sdk.js') }}?v={{ filemtime(public_path('assets/sdk/ccgame-sdk.js')) }}"></script>
    @endif
</body>
</html>
