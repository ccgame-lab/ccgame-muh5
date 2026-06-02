// ccgame-sdk.js - Hub Integration
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('ccgame-sdk-root');
        if (!root) return;

        // Đọc data từ root element được play.php render
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

        // Hàm escape HTML chống XSS
        function escapeHtml(unsafe) {
            return (unsafe || '').toString()
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        // Hệ thống Toast thông báo gọn nhẹ
        function showToast(message, type = 'info') {
            const body = panel.querySelector('.ccgame-sdk-body');
            if (!body) return;

            let container = panel.querySelector('.ccgame-sdk-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'ccgame-sdk-toast-container';
                container.style.cssText = `
                    position: absolute;
                    top: 10px;
                    left: 10px;
                    right: 10px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                    pointer-events: none;
                `;
                body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.style.cssText = `
                background: #161624;
                border: 1px solid #222235;
                border-radius: 6px;
                padding: 8px 12px;
                font-size: 10px;
                color: #fff;
                box-shadow: 0 4px 12px rgba(0,0,0,0.5);
                opacity: 0;
                transform: translateY(-8px);
                transition: all 0.25s ease;
                pointer-events: auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
            `;

            if (type === 'success') {
                toast.style.borderColor = '#1a5c33';
                toast.style.background = '#0e2a1a';
                toast.style.color = '#4cde80';
            } else if (type === 'error') {
                toast.style.borderColor = '#5c1a1a';
                toast.style.background = '#2a0e0e';
                toast.style.color = '#f44336';
            } else {
                toast.style.borderColor = '#c9a94e';
                toast.style.background = '#1a1810';
                toast.style.color = '#c9a94e';
            }

            toast.innerHTML = `<span style="flex: 1; line-height: 1.3;">${escapeHtml(message)}</span>`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            }, 10);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-8px)';
                setTimeout(() => {
                    toast.remove();
                    if (container.children.length === 0) {
                        container.remove();
                    }
                }, 250);
            }, 3000);
        }

        const safeUser = escapeHtml(user);
        const safeServerId = escapeHtml(serverId);
        const safeServerName = escapeHtml(serverName);
        const safeAuthMode = escapeHtml(authMode);
        const safeDisplayName = escapeHtml(displayName);
        const safeReturnUrl = escapeHtml(returnUrl);

        // 1. Khởi tạo Giao diện DOM
        
        // FAB (Floating Action Button Toggle)
        const fab = document.createElement('div');
        fab.className = 'ccgame-sdk-fab';
        fab.innerHTML = 'CC';
        
        // Panel chính
        const panel = document.createElement('div');
        panel.className = 'ccgame-sdk-panel';

        // Khung sườn HTML của Panel gồm Header, Tabs, Body
        panel.innerHTML = `
            <div class="ccgame-sdk-header">
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <div class="ccgame-sdk-header-title">MUH5</div>
                    <div id="ccgame-sdk-header-user-info" style="font-size: 8px; color: #8a8aaa; display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
                        <span>Đang tải thông tin...</span>
                    </div>
                </div>
                <button class="ccgame-sdk-close">&times;</button>
            </div>
            <div class="ccgame-sdk-tabs">
                <button class="ccgame-sdk-tab ccgame-sdk-tab--active" data-target="ccgame-sdk-pane-overview">Tổng quan</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-history">Giao dịch</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-ranking">BXH</button>
                <button class="ccgame-sdk-tab" data-target="ccgame-sdk-pane-announcements">Thông báo</button>
            </div>
            <div class="ccgame-sdk-body" style="position: relative; min-height: 250px;">
                <!-- 1. Tab: Tổng quan -->
                <div id="ccgame-sdk-pane-overview" class="ccgame-sdk-pane ccgame-sdk-pane--active">
                    <div style="margin-bottom: 12px; text-align: center;">
                        <div style="font-size: 13px; font-weight: 700; color: #c9a94e; text-transform: uppercase; margin-bottom: 2px;">Vui lòng kết nối...</div>
                        <div style="font-size: 9px; color: #4a4a6a; letter-spacing: 0.05em;">ĐANG TẢI THÔNG TIN</div>
                    </div>
                </div>

                <!-- 2. Tab: BXH -->
                <div id="ccgame-sdk-pane-ranking" class="ccgame-sdk-pane">
                    <div style="font-size: 11px; color: #4a4a6a; text-align: center; padding: 20px 0;">Chưa có dữ liệu bảng vinh danh.</div>
                </div>

                <!-- 3. Tab: Thông báo -->
                <div id="ccgame-sdk-pane-announcements" class="ccgame-sdk-pane">
                    <div style="font-size: 11px; color: #4a4a6a; text-align: center; padding: 20px 0;">Chưa có thông báo.</div>
                </div>

                <!-- 4. Tab: Giao dịch -->
                <div id="ccgame-sdk-pane-history" class="ccgame-sdk-pane">
                    <div style="font-size: 11px; color: #4a4a6a; text-align: center; padding: 20px 0;">Chưa có lịch sử giao dịch.</div>
                </div>
            </div>
        `;

        // Gắn fab và panel vào SDK Root
        root.appendChild(fab);
        root.appendChild(panel);

        // 2. Logic Lazy-Loading Fetch Bootstrap
        let isLoaded = false;

        function loadBootstrapData() {
            const body = panel.querySelector('.ccgame-sdk-body');
            if (!body) return;

            // Hiển thị loading spinner phủ lên body
            const overlay = document.createElement('div');
            overlay.className = 'ccgame-sdk-loading-overlay';
            overlay.innerHTML = `
                <div class="ccgame-sdk-spinner"></div>
                <div style="font-size: 9px; color: #4a4a6a; letter-spacing: 0.05em; text-transform: uppercase;">Đang tải dữ liệu...</div>
            `;
            body.appendChild(overlay);

            // Fetch API bootstrap từ thư mục tương đối
            fetch('api/sdk/bootstrap')
                .then(res => {
                    if (res.status === 401) {
                        throw new Error('401');
                    }
                    if (!res.ok) {
                        throw new Error('500');
                    }
                    return res.json();
                })
                .then(data => {
                    overlay.remove();
                    isLoaded = true;
                    renderBootstrapData(data);
                })
                .catch(err => {
                    console.error('CCGame SDK: could not load bootstrap data.', err);
                    overlay.remove();
                    
                    const errorMsg = err.message === '401'
                        ? 'Phiên chơi chưa đăng nhập hoặc đã hết hạn.<br><span style="font-size: 9px; color:#4a4a6a; margin-top:6px; display:block;">Vui lòng đăng nhập lại qua ccgame.org</span>'
                        : 'Không tải được dữ liệu.<br><span style="font-size: 9px; color:#4a4a6a; margin-top:6px; display:block;">Vui lòng F5 trang hoặc thử lại sau.</span>';

                    // Điền lỗi vào tất cả các tab cần thiết (trừ tab Hỗ trợ)
                    const errorHtml = `<div class="ccgame-sdk-error-msg">${errorMsg}</div>`;
                    
                    const panIds = [
                        'ccgame-sdk-pane-overview', 
                        'ccgame-sdk-pane-ranking',
                        'ccgame-sdk-pane-announcements', 
                        'ccgame-sdk-pane-history'
                    ];
                    panIds.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.innerHTML = errorHtml;
                    });
                });
        }

        // Render dữ liệu nhận được từ DB vào các Pane tương ứng
        function renderBootstrapData(data) {
            // Cập nhật Header User Info
            const headerUserInfo = document.getElementById('ccgame-sdk-header-user-info');
            if (headerUserInfo) {
                const name = data.user && data.user.name ? data.user.name : safeDisplayName;
                const sId = safeServerId ? 'S' + safeServerId : 'S1';
                const modeText = safeAuthMode === 'ccgame' ? 'GreenJade' : 'Guest';
                const wcoin = data.user && data.user.wallet && data.user.wallet.wcoin !== undefined ? data.user.wallet.wcoin : 0;
                const wpoint = data.user && data.user.wallet && data.user.wallet.wpoint !== undefined ? data.user.wallet.wpoint : 0;
                headerUserInfo.innerHTML = `
                    <span style="color: #c9a94e; font-weight: bold;">${escapeHtml(name)}</span>
                    <span style="color: #4a4a6a;">&middot;</span>
                    <span>${sId}</span>
                    <span style="color: #4a4a6a;">&middot;</span>
                    <span style="color: #8a8aaa;">${modeText}</span>
                `;
            }

            // Tab 1: Tổng quan
            const paneOverview = document.getElementById('ccgame-sdk-pane-overview');
            if (paneOverview) {
                const name = data.user && data.user.name ? data.user.name : safeDisplayName;
                const username = data.user && data.user.username ? data.user.username : safeUser;
                const vip = data.user && data.user.vip !== undefined ? 'VIP ' + data.user.vip : (data.user && data.user.tier ? data.user.tier : 'Thường');
                const wcoin = data.user && data.user.wallet && data.user.wallet.wcoin !== undefined ? data.user.wallet.wcoin.toLocaleString() : '0';
                const wpoint = data.user && data.user.wallet && data.user.wallet.wpoint !== undefined ? data.user.wallet.wpoint.toLocaleString() : '0';

                paneOverview.innerHTML = `
                    <div style="margin-bottom: 12px; text-align: center;">
                        <div style="font-size: 14px; font-weight: 700; color: #c9a94e; text-transform: uppercase; margin-bottom: 2px;">${escapeHtml(name)}</div>
                    </div>
                    
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Tài khoản</span>
                        <span class="ccgame-sdk-value" style="font-size: 11px;">${escapeHtml(username)}</span>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">GreenJade</span>
                        <span class="ccgame-sdk-value">
                            ${safeAuthMode === 'ccgame'
                                ? '<span class="ccgame-sdk-badge ccgame-sdk-badge--online">Đã liên kết</span>'
                                : '<span class="ccgame-sdk-badge ccgame-sdk-badge--soon">Chưa bind</span>'}
                        </span>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Cấp VIP</span>
                        <span class="ccgame-sdk-value ccgame-sdk-value--gold" style="font-weight:700;">${escapeHtml(vip)}</span>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Số dư Wcoin</span>
                        <span class="ccgame-sdk-value ccgame-sdk-value--gold">${wcoin}</span>
                    </div>
                    <div class="ccgame-sdk-row">
                        <span class="ccgame-sdk-label">Số dư Wpoint</span>
                        <span class="ccgame-sdk-value ccgame-sdk-value--gold">${wpoint}</span>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 14px;">
                        <button class="ccgame-sdk-btn" id="ccgame-sdk-btn-play" style="margin-top: 0; background: linear-gradient(135deg, #c9a94e 0%, #a3812d 100%); color: #0d0d14; border: none; font-weight: bold; text-transform: uppercase;">Đóng panel</button>
                        <a class="ccgame-sdk-btn" href="${safeReturnUrl}" target="_top" style="text-decoration: none; display: flex; align-items: center; justify-content: center; margin-top: 0; background: #161624; border-color: #2a2a3d; color: #c9a94e; font-weight: bold;">NẠP TIỀN / VÍ CHÍNH</a>
                    </div>
                `;
            }

            // Tab 2: Thông báo
            const paneAnn = document.getElementById('ccgame-sdk-pane-announcements');
            if (paneAnn) {
                if (data.announcements && data.announcements.length > 0) {
                    let html = '<div class="ccgame-sdk-list">';
                    data.announcements.forEach(ann => {
                        const title = ann.title || 'Thông báo hệ thống';
                        const date = ann.created_at || '';
                        const content = ann.content || '';
                        html += `
                            <div class="ccgame-sdk-item-card">
                                <div class="ccgame-sdk-item-title">${escapeHtml(title)}</div>
                                <div class="ccgame-sdk-item-meta">${escapeHtml(date)}</div>
                                <div class="ccgame-sdk-item-body">${escapeHtml(content)}</div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    paneAnn.innerHTML = html;
                } else {
                    paneAnn.innerHTML = '<div style="font-size: 11px; color: #4a4a6a; text-align: center; padding: 20px 0;">Không có thông báo mới</div>';
                }
            }

            
            // Tab 4: Giao dịch
            const paneHistory = document.getElementById('ccgame-sdk-pane-history');
            if (paneHistory) {
                let wcoinRows = '';
                if (data.transactions && data.transactions.wcoin && data.transactions.wcoin.length > 0) {
                    data.transactions.wcoin.forEach(tx => {
                        const amt = tx.amount > 0 ? `+${tx.amount}` : tx.amount;
                        const typeColor = tx.amount > 0 ? '#4cde80' : '#f44336';
                        wcoinRows += `
                            <tr>
                                <td style="color: #4a4a6a; font-size: 8px;">${escapeHtml(tx.created_at ? tx.created_at.substring(5, 16) : '')}</td>
                                <td style="color: ${typeColor}; font-weight: 600; text-align: right;">${escapeHtml(amt)}</td>
                                <td style="max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(tx.description || '')}</td>
                            </tr>
                        `;
                    });
                } else {
                    wcoinRows = '<tr><td colspan="3" style="text-align: center; color: #4a4a6a; padding: 10px 0;">Chưa có giao dịch Wcoin</td></tr>';
                }

                let wpointRows = '';
                if (data.transactions && data.transactions.wpoint && data.transactions.wpoint.length > 0) {
                    data.transactions.wpoint.forEach(tx => {
                        const amt = tx.amount > 0 ? `+${tx.amount}` : tx.amount;
                        const typeColor = tx.amount > 0 ? '#4cde80' : '#f44336';
                        wpointRows += `
                            <tr>
                                <td style="color: #4a4a6a; font-size: 8px;">${escapeHtml(tx.created_at ? tx.created_at.substring(5, 16) : '')}</td>
                                <td style="color: ${typeColor}; font-weight: 600; text-align: right;">${escapeHtml(amt)}</td>
                                <td style="max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(tx.description || '')}</td>
                            </tr>
                        `;
                    });
                } else {
                    wpointRows = '<tr><td colspan="3" style="text-align: center; color: #4a4a6a; padding: 10px 0;">Chưa có giao dịch Wpoint</td></tr>';
                }

                paneHistory.innerHTML = `
                    <div class="ccgame-sdk-section-title" style="margin-top: 0;">Lịch sử Wcoin</div>
                    <div class="ccgame-sdk-table-wrapper" style="max-height: 95px; overflow-y: auto;">
                        <table class="ccgame-sdk-table">
                            <thead>
                                <tr>
                                    <th style="width: 32%;">Thời gian</th>
                                    <th style="width: 25%; text-align: right;">Biến động</th>
                                    <th style="width: 43%;">Nội dung</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${wcoinRows}
                            </tbody>
                        </table>
                    </div>

                    <div class="ccgame-sdk-section-title">Lịch sử Wpoint</div>
                    <div class="ccgame-sdk-table-wrapper" style="max-height: 95px; overflow-y: auto;">
                        <table class="ccgame-sdk-table">
                            <thead>
                                <tr>
                                    <th style="width: 32%;">Thời gian</th>
                                    <th style="width: 25%; text-align: right;">Biến động</th>
                                    <th style="width: 43%;">Nội dung</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${wpointRows}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            // Tab 2: Ranking
            const paneRanking = document.getElementById('ccgame-sdk-pane-ranking');
            if (paneRanking) {
                if (data.ranking && data.ranking.length > 0) {
                    let html = '<div class="ccgame-sdk-list">';
                    data.ranking.forEach(rank => {
                        let rewardText = '';
                        try {
                            const rewards = typeof rank.rewards === 'string' ? JSON.parse(rank.rewards) : rank.rewards;
                            if (Array.isArray(rewards)) {
                                rewardText = rewards.join(', ');
                            } else {
                                rewardText = rank.rewards;
                            }
                        } catch (e) {
                            rewardText = rank.rewards;
                        }
                        const categoryName = rank.category === 'combat' ? '👑 VUA LỰC CHIẾN' : '💰 VUA TÀI PHIỆT';
                        html += `
                            <div class="ccgame-sdk-item-card">
                                <div class="ccgame-sdk-item-title" style="font-size: 11px;">${escapeHtml(rank.server_name)}</div>
                                <div class="ccgame-sdk-item-meta" style="color:#c9a94e; font-weight:700; font-size: 9px; margin-top:2px;">${escapeHtml(categoryName)}</div>
                                <div class="ccgame-sdk-item-body" style="font-size: 9px; background:#0d0d14; padding:5px 8px; border-radius:4px; border:1px solid #1a1a2a; color:#c2c2e0; margin-top:5px;">
                                    <strong>Quà:</strong> ${escapeHtml(rewardText)}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    paneRanking.innerHTML = html;
                } else {
                    paneRanking.innerHTML = '<div style="font-size: 11px; color: #4a4a6a; text-align: center; padding: 20px 0;">Chưa có thông tin vinh danh</div>';
                }
            }
        }

        // 4. Xử lý Sự kiện Toggle & Tabs
        let isOpen = false;

        function togglePanel() {
            isOpen = !isOpen;
            if (isOpen) {
                panel.classList.add('ccgame-sdk-panel--open');
                fab.classList.add('ccgame-sdk-fab--open');
                // Tải dữ liệu từ DB duy nhất một lần khi mở panel lần đầu
                if (!isLoaded) {
                    loadBootstrapData();
                }
            } else {
                panel.classList.remove('ccgame-sdk-panel--open');
                fab.classList.remove('ccgame-sdk-fab--open');
            }
        }

        // Lắng nghe click Fab nút mở panel
        fab.addEventListener('click', togglePanel);
        
        // Lắng nghe click nút đóng Panel
        const closeBtn = panel.querySelector('.ccgame-sdk-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                if (isOpen) togglePanel();
            });
        }

        // Xử lý chuyển đổi qua lại giữa các Tabs
        const tabs = panel.querySelectorAll('.ccgame-sdk-tab');
        const panes = panel.querySelectorAll('.ccgame-sdk-pane');

        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                // Xóa trạng thái active của các tab và pane cũ
                tabs.forEach(t => t.classList.remove('ccgame-sdk-tab--active'));
                panes.forEach(p => p.classList.remove('ccgame-sdk-pane--active'));

                // Kích hoạt tab được click
                this.classList.add('ccgame-sdk-tab--active');
                
                // Hiển thị pane tương ứng
                const targetId = this.getAttribute('data-target');
                const targetPane = document.getElementById(targetId);
                if (targetPane) {
                    targetPane.classList.add('ccgame-sdk-pane--active');
                }
            });
        });

        // Xử lý sự kiện click CTA trên tab Tổng quan (chuyển tab nhanh)
        document.addEventListener('click', function (e) {
            if (!e.target) return;
            if (e.target.id === 'ccgame-sdk-btn-play') {
                togglePanel();
            }

        });
    });
})();
