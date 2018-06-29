@extends('chat.master')

@section('top-buttons')
    <span class="ui green button" onclick="addAdmin()">添加管理员</span>
@endsection

@section('title','管理员管理')

@section('content')
    <div class="table-content">
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
            <thead>
                <th>管理员帐号</th>
                <th>名称</th>
                <th>添加时间</th>
                <th>操作</th>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/adminManage.js"></script>
@endsection