// Merge CCGame /play iframe query params into legacy urlParamDefault (CDN main.min.js).
(function () {
    var raw = window.__muh5UrlParams || {};
    if (typeof urlParamDefault === 'undefined') {
        return;
    }
    if (raw.roleCount !== undefined && raw.roleCount !== '') {
        urlParamDefault.roleCount = String(raw.roleCount);
    }
    if (raw.nickName !== undefined && raw.nickName !== '') {
        try {
            urlParamDefault.nickName = decodeURIComponent(String(raw.nickName));
        }
        catch (e) {
            urlParamDefault.nickName = String(raw.nickName);
        }
    }
})();
