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

        // 1. Tạo giao diện (DOM elements)
        // FAB
        const fab = document.createElement('div');
        fab.className = 'ccgame-sdk-fab';
        fab.innerHTML = 'CC'; // Icon đơn giản
        
        // Panel
        const panel = document.createElement('div');
        panel.className = 'ccgame-sdk-panel';

        // Panel HTML structure
        panel.innerHTML = `
            <div class="ccgame-sdk-header">
                <div class="ccgame-sdk-header-title">CCGame SDK</div>
                <button class="ccgame-sdk-close">&times;</button>
            </div>
            <div class="ccgame-sdk-tabs">
                <button class="ccgame-sdk-tab ccgame-sdk-tab--active" data-target="ccgame-sdk-pane-account">Tài khoản</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-server">Máy chủ</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-greenjade">GreenJade</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-support">Hỗ trợ</button>
            </div>
            <div class="ccgame-sdk-body">
                <!-- Tab: Tài khoản -->
                <div id="ccgame-sdk-pane-account" class="ccgame-sdk-pane ccgame-sdk-pane--active">
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">User ID</span>
                        <span class="ccgame-sdk-value ccgame-sdk-value--gold">${safeUser}</span>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Trạng thái</span>
                        <span class="ccgame-sdk-value"><span class="ccgame-sdk-badge ccgame-sdk-badge--online">Online</span></span>
                    </div>
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
                        <span class="ccgame-sdk-value"><span class="ccgame-sdk-badge ccgame-sdk-badge--soon">Chưa bind</span></span>
                    </div>
                    <button class="ccgame-sdk-btn" disabled>Đăng nhập bằng GreenJade</button>
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
