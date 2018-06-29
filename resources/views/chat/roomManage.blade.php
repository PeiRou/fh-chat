@extends('chat.master')

@section('title','房间管理')

@section('content')
    <div class="table-content">
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
            <thead>
                <th width="10%">房间名称</th>
                <th width="10%">房间类型</th>
                <th width="10%">在线人数</th>
                <th width="10%">是否禁言</th>
                <th width="30%">发言条件</th>
                <th width="10%">创建时间</th>
                <th width="20%">操作</th>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/roomManage.js"></script>
@endsection