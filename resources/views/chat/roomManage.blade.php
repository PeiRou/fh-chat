@extends('chat.master')

@section('top-buttons')
    <span class="ui green button" onclick="addRoom()">添加房间</span>
@endsection

@section('title','房间管理')

@section('content')
    <style>
        .layui-layer-msg, .layui-laydate{
            z-index: 999999999999!important;
        }
    </style>
    <div class="table-content">
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
            <thead>
                <th width="10%">房间名称</th>
                <th width="7%">房间类型</th>
                <th width="9%">在线/总数/管理</th>
                <th width="6%">是否禁言</th>
                <th width="6%">快速加入</th>
                <th width="6%">是否关闭</th>
                <th width="12%">发言条件</th>
                <th width="20%">计划推送游戏</th>
                <th width="10%">修改时间</th>
                <th width="20%">操作</th>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/vendor/layui/layui.js"></script>
    <link rel="stylesheet" href="/vendor/layui/css/layui.css">
    <script src="/chat/js/pages/roomManage.js"></script>
    <script>
        !function(){
            layui.use('layer', function(){
                layer = layui.layer;
            });
        }()
    </script>
@endsection