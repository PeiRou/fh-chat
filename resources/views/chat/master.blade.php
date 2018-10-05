<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title') - 管理后台</title>

    <link rel="stylesheet" href="/vendor/Semantic/semantic.min.css">
    <link rel="stylesheet" href="/vendor/formvalidation/dist/css/formValidation.min.css">
    <link rel="stylesheet" href="/vendor/confirm/dist/jquery-confirm.min.css">
    <link rel="stylesheet" href="/vendor/dataTables/DataTables-1.10.16/css/dataTables.semanticui.min.css">
    <link rel="stylesheet" href="/chat/css/core.css">
    @yield('page-css')
    <script src="/js/jquery.min.js"></script>
    <script src="/vendor/Semantic/semantic.min.js"></script>
    <script src="/vendor/confirm/dist/jquery-confirm.min.js"></script>
    <script src="/vendor/dataTables/DataTables-1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="/vendor/dataTables/DataTables-1.10.16/js/dataTables.semanticui.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
</head>
<body class="dash">

<div class="loading-mask">
    <div class="loading-spinner"></div>
</div>
<div class="nav-top">
    <div class="nav-logo">
        <a href="{{ url('/chat/dash') }}"><img style="width: {{ env('BACK_LOGO_WIDTH') }}px;" src="{{ env('BACK_LOGO','') }}"></a>
    </div>
    <div class="nav-user-info">
        <ul style="margin-top: 20px;">
            <li>当前在线人数：<b id="onlineCount">0</b></li>
            <li>修改密码</li>
            <li onclick="logout()">退出</li>
        </ul>
        <div class="user-info">
            <span class="name">{{ Session::get('account_name') }}</span>
        </div>
    </div>
</div>
<div class="nav">
    <ul>
        <li class="lefttop">
            <span></span> 菜单
        </li>
        <li id="menu-dash" class="nav-item">
            <a href="{{ url('/chat/dash') }}"><span><i class="icon-book"></i></span>控制台首页</a>
        </li>
        <li id="menu-userManage" class="nav-item">
            <a href="{{ url('/chat/userManage') }}"><span><i class="icon-book"></i></span>用户管理</a>
        </li>
        <li id="menu-levelManage" class="nav-item">
            <a href="{{ url('/chat/levelManage') }}"><span><i class="icon-book"></i></span>层级管理</a>
        </li>
        <li id="menu-roleManage" class="nav-item">
            <a href="{{ url('/chat/roleManage') }}"><span><i class="icon-book"></i></span>角色管理</a>
        </li>
        <li id="menu-roomManage" class="nav-item">
            <a href="{{ url('/chat/roomManage') }}"><span><i class="icon-book"></i></span>房间管理</a>
        </li>
        <li id="menu-noteManage" class="nav-item">
            <a href="{{ url('/chat/noteManage') }}"><span><i class="icon-book"></i></span>公告管理</a>
        </li>
        <li id="menu-adminManage" class="nav-item">
            <a href="{{ url('/chat/adminManage') }}"><span><i class="icon-book"></i></span>管理员管理</a>
        </li>
        <li id="menu-forbidManage" class="nav-item">
            <a href="{{ url('/chat/forbidManage') }}"><span><i class="icon-book"></i></span>违禁词管理</a>
        </li>
        <li id="menu-hongbaoManage" class="nav-item">
            <a href="{{ url('/chat/hongbaoManage') }}"><span><i class="icon-book"></i></span>红包管理</a>
        </li>
        <li id="menu-hongbaoDt" class="nav-item">
            <a href="{{ url('/chat/hongbaoDt') }}"><span><i class="icon-book"></i></span>红包明细</a>
        </li>
        <li id="menu-baseManage" class="nav-item">
            <a href="{{ url('/chat/baseManage') }}"><span><i class="icon-book"></i></span>平台配置</a>
        </li>
    </ul>
</div>
<div class="main-content">
    <div class="content-top">
        <div class="breadcrumb">
            <b>位置：</b>@yield('title')
        </div>
        <div class="content-top-buttons">
            @yield('top-buttons')
        </div>
    </div>
    @yield('content')
</div>

<script src="/vendor/Semantic/semantic.min.js"></script>
<script src="/vendor/formvalidation/dist/js/formValidation.min.js"></script>
<script src="/vendor/formvalidation/dist/js/framework/semantic.min.js"></script>
<script src="/chat/js/core.js"></script>
@yield('page-js')
<script>
    var sess = "{{ Session::get('account_id') }}";
    if(sess == "")
        autoLogout();
</script>
</body>
</html>