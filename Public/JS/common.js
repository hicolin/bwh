
/**
 * 根据屏幕大小判断html的font-size大小
 */
(function (doc, win) {
    var docEl = doc.documentElement,
        resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
        recalc = function () {
            var clientWidth = docEl.clientWidth;
            if (!clientWidth) return;
            if(clientWidth>=375){
                docEl.style.fontSize = '100px';
            }else{
                docEl.style.fontSize = 100 * (clientWidth / 375) + 'px';
            }
        };
    if (!doc.addEventListener) return;
    win.addEventListener(resizeEvt, recalc, false);
    doc.addEventListener('DOMContentLoaded', recalc, false);
})(document, window);

/**
 * fastclick
 */
// $(function() {
//     FastClick.attach(document.body);
// });

/**
 * Nprogress
 */
$(document).ready(function () {
    NProgress.start();
});
$(window).load(function () {
    NProgress.done();
});

/**
 * 返回键处理
 */
document.addEventListener('plusready', function() {
    var webview = plus.webview.currentWebview();
    plus.key.addEventListener('backbutton', function() {
        webview.canBack(function(e) {
            if(e.canBack) {
                webview.back();
            } else {
                //webview.close(); //hide,quit
                //plus.runtime.quit();
                mui.plusReady(function() {
                    //首页返回键处理
                    //处理逻辑：1秒内，连续两次按返回键，则退出应用；
                    var first = null;
                    plus.key.addEventListener('backbutton', function() {
                        //首次按键，提示‘再按一次退出应用’
                        if(!first) {
                            first = new Date().getTime();
                            mui.toast('再按一次退出应用');
                            setTimeout(function() {
                                first = null;
                            }, 1000);
                        } else {
                            if(new Date().getTime() - first < 1500) {
                                plus.runtime.quit();
                            }
                        }
                    }, false);
                });
            }
        })
    });
});

/**
 * 图片上传
 * @param imgSelector
 * @param url
 */
function uploadPic(imgSelector,url){
    layer.load(3);
    $.ajax({
        url: url,
        type: 'post',
        cache: false,
        data: new FormData($('#uploadForm')[0]),
        dataType: 'json',
        processData: false,
        contentType: false
    }).done(function (res) {
        layer.closeAll();
        if(res.result === 200){
            $(imgSelector).attr('src',res.des);
        }else{
            layer.msg(res.message,{time:1500});
        }
    })
}

/**
 * tab 切换效果
 * @param els
 */
function tabSwitch(els) {
    $(document).ready(function () {
        $(els).each(function(k,v){
            if ($(this)[0].href == String(window.location) && $(this).attr('href')!="") {
                $(this).parent().addClass("active");
            }
        });
    });
}

