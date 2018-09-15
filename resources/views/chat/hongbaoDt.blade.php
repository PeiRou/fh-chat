@extends('chat.master')

@section('title','红包明细')

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
                                <input type="text" id="timeStart" placeholder="起始日期" value="@if($start == "") {{ date('Y-m-d',time()) }} @else {{ $start }} @endif">
                            </div>
                        </div>
                    </div>
                    <div style="line-height: 32px;">-</div>
                    <div class="one wide field">
                        <div class="ui calendar" id="rangeend">
                            <div class="ui input left">
                                <input type="text" id="timeEnd" placeholder="结束日期" value="@if($end == "") {{ date('Y-m-d',time()) }} @else {{ $end }} @endif">
                            </div>
                        </div>
                    </div>
                    <div class="one wide field">
                        <input type="text" id="id" placeholder="红包ID" value="{{ $id }}">
                    </div>
                    <div class="one wide field">
                        <input type="text" id="or_id" placeholder="订单号">
                    </div>
                    <div class="one wide field">
                        <input type="text" id="account" placeholder="用户名">
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
                        <input type="text" id="min_amount" placeholder="最小金额">
                    </div>
                    <div style="line-height: 32px;">~</div>
                    <div class="one wide field">
                        <input type="text" id="max_amount" placeholder="最大金额">
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