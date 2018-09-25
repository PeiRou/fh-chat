@extends('chat.master')

@section('title','用户管理')

@section('content')
    <div class="table-content">
        <div class="table-quick-bar">
            <div class="ui mini form">
                <div class="fields">
                    <div class="two wide field">
                        <input type="text" id="account" placeholder="用户名/呢称">
                    </div>
                    <div style="line-height: 32px;">角色</div>
                    <div class="one wide field">
                        <select class="ui dropdown" id="role" style='height:32px !important'>
                            <option value="">所有会员</option>
                        </select>
                    </div>
                    <div style="line-height: 32px;">状态</div>
                    <div class="one wide field">
                        <select class="ui dropdown" id="statusOnline" style='height:32px !important'>
                            <option value="">全部</option>
                            <option value="1" selected="selected">在线</option>
                            <option value="0">离线</option>
                        </select>
                    </div>
                    <div style="line-height: 32px;">状态</div>
                    <div class="one wide field">
                        <select class="ui dropdown" id="status" style='height:32px !important'>
                            <option value="">全部</option>
                            <option value="0">正常</option>
                            <option value="1">禁言</option>
                        </select>
                    </div>
                    <div class="one wide field">
                        <input type="text" id="ip" placeholder="登陆IP">
                    </div>
                    <div class="one wide field">
                        <button id="btn_search" class="fluid ui mini labeled icon teal button"><i class="search icon"></i> 查询 </button>
                    </div>
                    <div class="one wide field">
                        <button id="reset" class="fluid ui mini labeled icon button"><i class="undo icon"></i> 重置 </button>
                    </div>
                </div>
            </div>
        </div>
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th width="11.11%">用户ID</th>
                <th width="11.11%">用户名</th>
                <th width="11.11%">呢称</th>
                <th width="11.11%">IP</th>
                <th width="11.11%">角色</th>
                <th width="11.11%">状态</th>
                <th width="11.11%">最近2天充值</th>
                <th width="11.11%">最近2天下注</th>
                <th width="11.11%">操作</th>
            </tr>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/user.js"></script>
@endsection