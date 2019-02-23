<!DOCTYPE html>
<html>
<head lang="en">
    <title>我的</title>
    <meta name="keywords" content=" "/>
    <meta name="author" content="order by www.lision.cn"/>
    <meta name="description" content=" "/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no">

    <link rel="stylesheet" type="text/css" href="__PUBLIC__/css/basic.css">
    <link rel="stylesheet" type="text/css" href="__PUBLIC__/css/animotion.css">
    <link rel="stylesheet" type="text/css" href="__PUBLIC__/css/user.css">

    <link rel="stylesheet" type="text/css" href="//at.alicdn.com/t/font_943224_hzfvr2agb2p.css">
    <script src="__PUBLIC__/js/jquery-1.8.3.min.js"></script>
    <script src="__PUBLIC__/js/mobile/layer.js"></script>
    <script src="__PUBLIC__/js/common.js"></script>
</head>
<body class="mb60">

<!--head-->
<div class="user_head">
    <span>我的</span>
</div>
<!--head end-->

<!--user_msg-->
<div class="user_msg">
    <div class="user_msg_text">

        <img src="__PUBLIC__/images/tx.jpg"/>

        <p>小主您好，请先<a href="../login/login.html">登录</a></p>

    </div>
</div>
<!--user_msg end-->


<!--nav-->
<div class="user_nav">

    <div class="user_nav_con">

        <ul>
            <li>
                <a href="loan_record.html">
                    <i class="iconfont icon-6-copy"></i>
                    <p>借款记录</p>

                </a>
            </li>
            <li>
                <a href="my_bank_card.html">
                    <i class="iconfont icon-yinxingqia"></i>
                    <p>我的银行卡</p>
                </a>
            </li>
            <div class="clear"></div>
        </ul>

    </div>

</div>
<!--nav end-->



<!--main -->
<div class="user_main">

    <ul>
        <li>
            <a href="basic_msg.html">
                <i class="iconfont icon-jibenxinxi "></i>
                <span>基本信息</span>
                <i class="iconfont icon-youjiantou arrow fr"></i>
            </a>
        </li>
        <li>
            <a href="my_news.html">
                <i class="iconfont icon-wodexiaoxi "></i>
                <span>我的消息</span>
                <i class="iconfont icon-youjiantou arrow fr"></i>
            </a>
        </li>
        <li>
            <a class="dial">
                <i class="iconfont icon-lianxiwomen "></i>
                <span>联系我们</span>
                <i class="iconfont icon-youjiantou arrow fr"></i>
            </a>
        </li>
        <li>
            <a href="urual_question.html">
                <i class="iconfont icon-bangzhuzhongxin "></i>
                <span>帮助中心</span>
                <i class="iconfont icon-youjiantou arrow fr"></i>
            </a>
        </li>
        <li>
            <a href="setting.html">
                <i class="iconfont icon-tubiaozhizuomoban_fuzhi "></i>
                <span>设置</span>
                <i class="iconfont icon-youjiantou arrow fr"></i>
            </a>
        </li>
    </ul>

</div>
<!--main end-->



<!--foot-->
<div class="foot">
    <ul>
        <li>
            <a href="__PUBLIC__/index.html">
                <i class="iconfont icon-jiekuan"></i>
                <p>借款</p>
            </a>
        </li>
        <li>
            <a href="__PUBLIC__/renewal.html">
                <i class="iconfont icon-huankuan"></i>
                <p>还款</p>
            </a>
        </li>
        <li>
            <a href="../certification/certificate.html">
                <i class="iconfont icon-renzheng"></i>
                <p>认证</p>
            </a>
        </li>
        <li class="active">
            <a href="person.html">
                <i class="iconfont icon-renwu"></i>
                <p>我的</p>
            </a>
        </li>



        <div class="clear"></div>
    </ul>
</div>
<!--foot end-->



<!--遮罩层-->
<div id="cover"></div>

<!--提示框-->
<div class="notice_box animated bounceInDown" style="height: 170px;">

    <div class="notice_box_top">
        <span>提示</span>
        <em></em>
        <i class="iconfont icon-guanbi notice_box_close"></i>
    </div>

    <div class="notice_box_text" style="line-height: 30px;height: 90px;padding: 10px 0;">
        <i class="iconfont icon-bodadianhua"></i>
        <p>0551-63361306</p>
    </div>

    <div class="notice_box_bot">
        <button class="notice_box_close">取消</button>
        <button class="sure">拨打</button>
        <div class="clear"></div>
    </div>

</div>


<script>

    $(function(){
        $(".dial").click(function(){
            $("#cover").show();
            $(".notice_box").show();
        });
        $(".notice_box_close").click(function(){
            $("#cover").hide();
            $(".notice_box").hide();
        });
    })

</script>


</body>
</html>