@extends('chat.master')

@section('title','控制台首页')

@section('content')
    <div class="dash-content">
        <div class="line">
            <span class="icon1"><img src="/chat/img/cloud.png"> </span>
            <span class="txt1"><b>{{ Session::get('account_name') }} 您好，欢迎使用彩票网聊天室管理系统</b></span>
        </div>
        <div class="line">
            <span class="icon2"><img src="/chat/img/time.png"></span>
            <span class="txt2">
                {{ $accountInfo->last_login_time ? '您上次登录时间：'.$accountInfo->last_login_time.',' : '' }}
                {{--您上次登录时间：，--}}
                {{ $accountInfo->last_login_ip ? '上次登录IP：'.$accountInfo->last_login_ip : '' }}

            </span>
        </div>
    </div>

    <div class="dash-tables">
        <div class="ui grid">
            <div class="six wide column">
                <div class="outline">
                    <div class="title">最新公告</div>
                    <div class="content">
                    </div>
                </div>
            </div>
            <div class="six wide column">
                <div class="outline">
                    <div class="title">系统更新日志</div>
                    <div class="content">

                    </div>
                </div>
            </div>
            <div class="four wide column">
                <div class="outline">
                    <div class="title">相关下载</div>
                    <div class="content" style="padding: 10px 0px 10px 3px;">
                        <a class="dash_link" href='/download/chatAdmin-v1.4.pdf' target='_blank'>聊天室使用说明v1.3.pdf</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/dash.js"></script>
@endsection