@extends('chat.master')

@section('top-buttons')
    {{--<span class="ui green button" onclick="addRole()">添加角色</span>--}}
@endsection

@section('title','角色管理')

@section('content')
    <div class="table-content">
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%">
            <thead>
                <th width="16.66%">角色名</th>
                <th width="16.66%">聊天信息效果</th>
                {{--<th width="16.66%">消息最大长度</th>--}}
                <th width="16.66%">权限</th>
                {{--<th width="16.66%">描述</th>--}}
                <th width="16.66%">操作</th>
            </tr>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/roleManage.js"></script>
    <script src="/vendor/color-picker/jscolor.js"></script>
@endsection