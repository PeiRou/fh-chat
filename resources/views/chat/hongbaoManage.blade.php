@extends('chat.master')

@section('top-buttons')
    <span class="ui green button" onclick="addHongbao()">发红包</span>
@endsection

@section('title','红包管理')

@section('content')
    <script src="/vendor/Semantic-UI-Calendar/dist/calendar.min.js"></script>
    <link rel="stylesheet" href="/vendor/Semantic-UI-Calendar/dist/calendar.min.css">
    <div class="table-content">
        <div class="table-quick-bar">
            <div class="ui mini form">
                <div class="fields">
                    <div style="line-height: 32px;">时间：</div>

                    <div class="one wide field">
                        <div class="ui calendar" id="rangestart">
                            <div class="ui input left">
                                <input type="text" id="timeStart" placeholder="起始日期" value="{{ date('Y-m-d',time()) }}">
                            </div>
                        </div>
                    </div>
                    <div style="line-height: 32px;">-</div>
                    <div class="one wide field">
                        <div class="ui calendar" id="rangeend">
                            <div class="ui input left">
                                <input type="text" id="timeEnd" placeholder="结束日期" value="{{ date('Y-m-d',time()) }}">
                            </div>
                        </div>
                    </div>
                    <div class="one wide field">
                        <input type="text" id="id" placeholder="红包ID">
                    </div>
                    <div style="line-height: 32px;">房间</div>
                    <div class="one wide field">
                        <select class="ui dropdown" id="room" style='height:32px !important'>
                            <option value="">全部</option>
                        </select>
                    </div>
                    <div style="line-height: 32px;">状态</div>
                    <div class="one wide field">
                        <select class="ui dropdown" id="status" style='height:32px !important'>
                            <option value="">全部</option>
                            <option value="1">疯抢中</option>
                            <option value="2">已抢完</option>
                            <option value="3">已关闭</option>
                        </select>
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