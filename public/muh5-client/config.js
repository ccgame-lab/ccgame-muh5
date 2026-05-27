// Game Configuration for MUH5 Legacy Client
(function () {
    var urlParam = {};
    var paraUrl = window.location.href;
    var whIndex = paraUrl.indexOf("?");
    if (whIndex != -1) {
        var param = paraUrl.slice(whIndex + 1).split("&");
        var strArr;
        for (var i = 0; i < param.length; i++) {
            strArr = param[i].split("=");
            urlParam[strArr[0]] = strArr[1];
        }
    }

    // Full query map for ccgame-entrance.js (nickName, roleCount, …)
    window["__muh5UrlParams"] = urlParam;

    // Assign global variables required by Egret engine
    window["uid"] = urlParam.user || "quocquoc";
    window["sid"] = urlParam.srvid || "1";
    window["spverify"] = urlParam.spverify || "portal-auth";
    window["svrip"] = urlParam.srvaddr || "muh5-ws.ccgame.org/s1/";
    window["port"] = urlParam.srvport || "443";
    window["loginre"] = "./login_bt.json";
    window["showurl"] = false;
})();
