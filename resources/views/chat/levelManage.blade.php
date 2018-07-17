@extends('chat.master')

@section('top-buttons')
    {{--<span class="ui green button" onclick="addRole()">添加角色</span>--}}
@endsection

@section('title','层级管理')

@section('content')
    <div class="table-content">
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%">
            <thead>
                <th width="16.66%">层级名称</th>
                <th width="16.66%">充值量</th>
                <th width="16.66%">打码量</th>
                <th width="16.66%">新增日期</th>
                <th width="16.66%">修改日期</th>
                <th width="16.66%">操作</th>
            </tr>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/levelManage.js"></script>
    <script src="/vendor/color-picker/jscolor.js"></script>
@endsection