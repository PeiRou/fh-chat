@extends('chat.master')

@section('top-buttons')
    <span class="ui green button" onclick="addNote()">添加公告</span>
@endsection

@section('title','公告管理')

@section('content')
    <div class="table-content">
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
            <thead>
                <th>公告内容</th>
                <th>房间</th>
                <th>添加时间</th>
                <th>添加人</th>
                <th>修改时间</th>
                <th>修改人</th>
                <th>操作</th>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/noteManage.js"></script>
@endsection