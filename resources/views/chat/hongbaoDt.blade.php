@extends('chat.master')

@section('title','红包明细')

@section('content')
    <div class="table-content">
        <div class="table-quick-bar">
            <div class="ui mini form">
                <div class="fields">
                    <div class="one wide field">
                        <select class="ui dropdown" id="recharge_type" style='height:32px !important'>
                            <option value="">全部</option>
                            <option value="">待处理</option>
                            <option value="">成功</option>
                            <option value="">失败</option>
                        </select>
                    </div>
                    <div class="one wide field">
                        <div class="ui calendar" id="rangestart">
                            <div class="ui input left">
                                <input type="text" id="timeStart" placeholder="起始日期">
                            </div>
                        </div>
                    </div>
                    <div class="one wide field">
                        <div class="ui calendar" id="rangeend">
                            <div class="ui input left">
                                <input type="text" id="timeEnd" placeholder="结束日期">
                            </div>
                        </div>
                    </div>
                    <div class="one wide field">
                        <button id="btn_search" class="fluid ui mini labeled icon teal button"><i class="search icon"></i> 查询 </button>
                    </div>
                </div>
            </div>
        </div>
        <table id="dtTable" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
            <thead>
                <th>用户名</th>
                <th>红包ID</th>
                <th>订单号</th>
                <th>金额</th>
                <th>领取时间</th>
                <th>状态</th>
                <th>操作</th>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/hongbaoDt.js"></script>
@endsection