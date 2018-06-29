@extends('chat.master')

@section('top-buttons')
    <span class="ui green button" onclick="addForbid()">添加违禁词</span>
@endsection

@section('title','违禁词管理')

@section('content')
    <div class="table-content">
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
            <thead>
                <th>违禁词</th>
                <th>平台</th>
                <th>更新时间</th>
                <th>操作</th>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/forbidManage.js"></script>
@endsection