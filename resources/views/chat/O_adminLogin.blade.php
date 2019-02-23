<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="_token" content="{{ csrf_token() }}"/>
    <title>欢迎登录聊天室管理系统</title>

    <link rel="shortcut icon" type="image/png" href="{{ env('ICON') }}"/>
    <script src="/js/jquery.min.js"></script>
    <script src="/chat/js/pages/cloud.js"></script>
    <script src="/chat/js/pages/login.js"></script>
    <link rel="stylesheet" href="/chat/css/login.css">
</head>
<body id="login">
<div id="mainBody">
    <div id="cloud1" class="cloud"></div>
    <div id="cloud2" class="cloud"></div>
</div>
<div class="logintop">
    <span>欢迎登录聊天室管理系统</span>
    <ul>
        <li><a href="#">回首页</a></li>
        <li><a href="#">帮助</a></li>
        <li><a href="#">关于</a></li>
    </ul>
</div>

<div class="loginbody">
    <span class="systemlogo"></span>

    <div class="loginbox">
        <div style="position: absolute;width: 220px;height: 315px">
            <img style="width: 150px;position: absolute;left: 15px;right: 0;top: 0;bottom: 0;margin: auto" src="{{ env('FRONT_LOGO') }}">
        </div>
        <ul>
            <li><input id="userName" type="text" class="loginuser" value="" onclick="javascript:this.value=''" /></li>
            <li><input id="userPwd" type="password" class="loginpwd" value="" onclick="javascript:this.value=''" /></li>
            <li>
                <input type="text" placeholder="OTP随机码" id="otp" required class="logincode">
            </li>
            <li>
                <input id="loginBtn" type="button" class="loginbtn" value="登录" onclick="login();" />
                <label><input id="remember" type="checkbox" checked="checked" />记住密码</label>
                <label><a href="#">忘记密码？</a></label>
            </li>
        </ul>
    </div>
</div>
<script>
    $( ".loginbox" ).keypress(function( event ) {
        if (event.which == 13) {
            login();
        }
    });
    $('.captcha').click(function () {
        $.ajax({
            url:'/web/getCaptcha',
            type:'get',
            success:function (result) {
                $('.captcha').attr("src",result);
            }
        })
    });
</script>
</body>
</html>