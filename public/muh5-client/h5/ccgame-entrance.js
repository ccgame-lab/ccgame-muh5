// Merge CCGame /play iframe query params into legacy urlParamDefault (CDN main.min.js).
(function () {
    function deriveNickFromGuestAccount(user) {
        if (!user) {
            return '';
        }
        var match = String(user).match(/^guest_([a-f0-9]{5,})/i);
        if (!match) {
            return '';
        }
        return 'g' + match[1].slice(0, 5);
    }

    function applyUrlParams() {
        if (typeof urlParamDefault === 'undefined') {
            return false;
        }

        var raw = window.__muh5UrlParams || {};

        var nick = raw.nickName ? String(raw.nickName) : '';
        if (!nick) {
            nick = deriveNickFromGuestAccount(raw.user);
        }
        if (nick) {
            urlParamDefault.nickName = nick;
        }

        if (typeof EntranceParam !== 'undefined' && EntranceParam.urlParam && nick) {
            EntranceParam.urlParam.nickName = nick;
        }

        return true;
    }

    if (!applyUrlParams()) {
        var tries = 0;
        // Cho urlParamDefault (CDN main.min.js) xuat hien. Poll 10ms toi da 2s thay vi
        // interval 0 chay burst CPU ~4s khi CDN cham. Co urlParamDefault la dung ngay.
        var timer = setInterval(function () {
            if (applyUrlParams() || ++tries > 200) {
                clearInterval(timer);
            }
        }, 10);
    }
})();
