@extends('chat.master')

@section('title','红包管理')

@section('content')
    <div class="table-content">
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%">
            <thead>
                <th width="9%">红包ID</th>
                <th width="9%">房间</th>
                <th width="9%">状态</th>
                <th width="9%">总金额</th>
                <th width="9%">红包个数</th>
                <th width="9%">剩馀馀额</th>
                <th width="13.5%">抢红包条件(最近2天)</th>
                <th width="9%">发送时间</th>
                <th width="9%">操作人</th>
                <th width="13.5%">操作</th>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/hongbaoManage.js"></script>
@endsection