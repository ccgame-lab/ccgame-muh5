// ccgame-sdk.js — Patch 3
// Vanilla JS SDK cho MU H5 Wrapper
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('ccgame-sdk-root');
        if (!root) return;

        // Đọc data từ root element (được PHP render)
        const user = root.getAttribute('data-user') || 'Unknown';
        const serverId = root.getAttribute('data-server-id') || '?';
        const serverName = root.getAttribute('data-server-name') || 'Unknown Server';
        const authMode = root.getAttribute('data-auth-mode') || 'guest';
        const displayName = root.getAttribute('data-display-name') || 'Khách';
        const expiresAtVal = root.getAttribute('data-expires-at');
        const expiresAt = expiresAtVal ? parseInt(expiresAtVal, 10) : 0;
        let returnUrl = root.getAttribute('data-return-url') || 'https://ccgame.org';
        if (returnUrl.toLowerCase().trim().startsWith('javascript:')) {
            returnUrl = 'https://ccgame.org';
        }

        // Hàm escape HTML để chống XSS
        function escapeHtml(unsafe) {
            return (unsafe || '').toString()
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        const safeUser = escapeHtml(user);
        const safeServerId = escapeHtml(serverId);
        const safeServerName = escapeHtml(serverName);
        const safeAuthMode = escapeHtml(authMode);
        const safeDisplayName = escapeHtml(displayName);
        const safeReturnUrl = escapeHtml(returnUrl);

        // 1. Tạo giao diện (DOM elements)
        // FAB
        const fab = document.createElement('div');
        fab.className = 'ccgame-sdk-fab';
        fab.innerHTML = 'CC'; // Icon đơn giản
        
        // Panel
        const panel = document.createElement('div');
        panel.className = 'ccgame-sdk-panel';

        // Badge auth mode
        const modeBadge = safeAuthMode === 'ccgame' 
            ? '<span class="ccgame-sdk-badge ccgame-sdk-badge--online">CCGAME</span>' 
            : '<span class="ccgame-sdk-badge ccgame-sdk-badge--soon">NONE</span>';
            
        const guestNote = safeAuthMode === 'none' 
            ? '<div style="font-size: 10px; color: #ff9800; margin-top: 8px;">Vui lòng vào game từ ccgame.org</div>'
            : '';
            
        const statusBadge = safeAuthMode === 'ccgame'
            ? '<span class="ccgame-sdk-badge ccgame-sdk-badge--online">Đang chơi</span>'
            : '<span class="ccgame-sdk-badge ccgame-sdk-badge--soon">Chưa có phiên CCGame</span>';
            
        const isBound = safeAuthMode === 'ccgame';
        
        const greenjadeBindStatus = isBound 
            ? '<span class="ccgame-sdk-badge ccgame-sdk-badge--online">Đã liên kết</span>'
            : '<span class="ccgame-sdk-badge ccgame-sdk-badge--soon">Chưa bind</span>';
            
        const greenjadeBtn = isBound
            ? '<button class="ccgame-sdk-btn" style="background:#16161f;color:#4a4a6a;border-color:#2a2a3d;cursor:default;" disabled>Đã bảo vệ bằng GreenJade</button>'
            : '<button class="ccgame-sdk-btn" disabled>Đăng nhập bằng GreenJade</button>';

        // Panel HTML structure
        panel.innerHTML = `
            <div class="ccgame-sdk-header">
                <div class="ccgame-sdk-header-title">CCGame SDK</div>
                <button class="ccgame-sdk-close">&times;</button>
            </div>
            <div class="ccgame-sdk-tabs">
                <button class="ccgame-sdk-tab ccgame-sdk-tab--active" data-target="ccgame-sdk-pane-today">Hôm nay</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-account">TK</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-server">Server</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-greenjade">GJ</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-support">Hỗ trợ</button>
            </div>
            <div class="ccgame-sdk-body">
                <!-- Tab: Hôm nay -->
                <div id="ccgame-sdk-pane-today" class="ccgame-sdk-pane ccgame-sdk-pane--active">
                    <div style="margin-bottom: 12px; text-align: center;">
                        <div style="font-size: 13px; font-weight: 700; color: #c9a94e; text-transform: uppercase; margin-bottom: 2px;">Alpha Test MU H5</div>
                        <div style="font-size: 9px; color: #4a4a6a; letter-spacing: 0.05em;">SỰ KIỆN TRẢI NGHIỆM</div>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Giftcode</span>
                        <span class="ccgame-sdk-value ccgame-sdk-value--gold select-all" style="font-weight: 700;">MUH5ALPHA</span>
                    </div>
                    <div class="ccgame-sdk-row" style="flex-direction: column; align-items: flex-start; gap: 4px; border-bottom: none;">
                        <span class="ccgame-sdk-label">Mục tiêu hôm nay</span>
                        <span class="ccgame-sdk-value" style="text-align: left; color: #4cde80; font-size: 11px;">Vào game nhận quà tân thủ</span>
                    </div>
                    <a class="ccgame-sdk-btn" href="${safeReturnUrl}" target="_top" style="margin-top: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px; background: linear-gradient(135deg, #c9a94e 0%, #a3812d 100%); color: #0d0d14; border: none; font-weight: bold; box-shadow: 0 4px 12px rgba(201, 169, 78, 0.25);">
                        VỀ CCGAME
                    </a>
                </div>
                <!-- Tab: Tài khoản -->
                <div id="ccgame-sdk-pane-account" class="ccgame-sdk-pane">
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Tài khoản</span>
                        <span class="ccgame-sdk-value ccgame-sdk-value--gold">${safeDisplayName}</span>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">User ID</span>
                        <span class="ccgame-sdk-value" style="font-size: 10px;">${safeUser}</span>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Trạng thái</span>
                        <span class="ccgame-sdk-value">${statusBadge}</span>
                    </div>
                    <div id="ccgame-sdk-session-row" class="ccgame-sdk-row" style="display: none;">
                        <span class="ccgame-sdk-label">Session còn lại</span>
                        <span id="ccgame-sdk-session-value" class="ccgame-sdk-value" style="color: #ff9800; font-weight: 600; font-family: monospace;">--:--</span>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Chế độ</span>
                        <span class="ccgame-sdk-value">${modeBadge}</span>
                    </div>
                    ${guestNote}
                </div>
                <!-- Tab: Máy chủ -->
                <div id="ccgame-sdk-pane-server" class="ccgame-sdk-pane">
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Server</span>
                        <span class="ccgame-sdk-value">${safeServerName}</span>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Server ID</span>
                        <span class="ccgame-sdk-value">S${safeServerId}</span>
                    </div>
                </div>
                <!-- Tab: GreenJade -->
                <div id="ccgame-sdk-pane-greenjade" class="ccgame-sdk-pane">
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Liên kết</span>
                        <span class="ccgame-sdk-value">${greenjadeBindStatus}</span>
                    </div>
                    ${greenjadeBtn}
                </div>
                <!-- Tab: Hỗ trợ -->
                <div id="ccgame-sdk-pane-support" class="ccgame-sdk-pane">
                    <p class="ccgame-sdk-support-text">
                        Nếu gặp lỗi trong quá trình nạp thẻ hoặc đăng nhập, vui lòng liên hệ CSKH qua fanpage.
                    </p>
                </div>
            </div>
        `;

        // Gắn vào DOM
        root.appendChild(fab);
        root.appendChild(panel);

        // ── Session Countdown Timer ──────────────────────────────────────────
        if (expiresAt > 0) {
            const sessionRow = panel.querySelector('#ccgame-sdk-session-row');
            const sessionVal = panel.querySelector('#ccgame-sdk-session-value');
            if (sessionRow && sessionVal) {
                sessionRow.style.display = 'flex';
                const updateCountdown = () => {
                    const nowSeconds = Math.floor(Date.now() / 1000);
                    const remaining = expiresAt - nowSeconds;
                    if (remaining <= 0) {
                        sessionVal.textContent = 'Đã hết hạn';
                        sessionVal.style.color = '#f44336';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        const minutes = Math.floor(remaining / 60);
                        const seconds = remaining % 60;
                        sessionVal.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                };
                updateCountdown();
                const intervalId = setInterval(updateCountdown, 1000);
                window.addEventListener('unload', () => clearInterval(intervalId));
            }
        }

        // 2. Xử lý sự kiện (Events)
        let isOpen = false;

        function togglePanel() {
            isOpen = !isOpen;
            if (isOpen) {
                panel.classList.add('ccgame-sdk-panel--open');
                fab.classList.add('ccgame-sdk-fab--open');
            } else {
                panel.classList.remove('ccgame-sdk-panel--open');
                fab.classList.remove('ccgame-sdk-fab--open');
            }
        }

        fab.addEventListener('click', togglePanel);
        
        const closeBtn = panel.querySelector('.ccgame-sdk-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                if (isOpen) togglePanel();
            });
        }

        // Tabs logic
        const tabs = panel.querySelectorAll('.ccgame-sdk-tab');
        const panes = panel.querySelectorAll('.ccgame-sdk-pane');

        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                // Remove active classes
                tabs.forEach(t => t.classList.remove('ccgame-sdk-tab--active'));
                panes.forEach(p => p.classList.remove('ccgame-sdk-pane--active'));

                // Add active class to clicked tab
                this.classList.add('ccgame-sdk-tab--active');
                
                // Show target pane
                const targetId = this.getAttribute('data-target');
                const targetPane = document.getElementById(targetId);
                if (targetPane) {
                    targetPane.classList.add('ccgame-sdk-pane--active');
                }
            });
        });
    });
})();
