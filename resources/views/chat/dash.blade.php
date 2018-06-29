@extends('chat.master')

@section('title','控制台首页')

@section('content')
    <div class="dash-content">
        <div class="line">
            <span class="icon1"><img src="{{ asset('chat/img/cloud.png') }}"> </span>
            <span class="txt1"><b>{{ Session::get('account_name') }} 您好，欢迎使用彩票网聊天室管理系统</b></span>
        </div>
        <div class="line">
            <span class="icon2"><img src="{{ asset('chat/img/time.png') }}"></span>
            <span class="txt2">您上次登录时间：，上次登录IP：</span>
        </div>
    </div>

    <div class="dash-tables">
        <div class="ui grid">
            <div class="six wide column">
                <div class="outline">
                    <div class="title">最新公告</div>
                    <div class="content">
                        {{--<div class="ui active inverted dimmer" id="loader">--}}
                            {{--<div class="ui text loader">加载</div>--}}
                        {{--</div>--}}
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
                    <div class="content">

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/dash.js"></script>
@endsection