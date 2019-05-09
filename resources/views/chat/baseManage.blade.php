@extends('chat.master')

@section('title','平台配置')

@section('content')
    <script src="/vendor/Semantic-UI-Calendar/dist/calendar.min.js"></script>
    <link rel="stylesheet" href="/vendor/Semantic-UI-Calendar/dist/calendar.min.css">
    <div class="table-content">
        <form id="updUserForm" class="ui form" action="{{ url('/chat/action/updBaseInfo') }}">
            <div class="ui block header inline fields">
                <label style="width :150px;text-align: right;">是否开启聊天室</label>
                <div class="ui toggle checkbox">
                    <input type="checkbox" id="chkOpenStatus" name="openStatus" @if($base->open_status == "1") checked="checked" @endif>
                    <label></label>
                </div>
                <div id="dvOpenStatusOn" class="green" @if($base->open_status != "1")style="display: none"@endif>开启中</div>
                <div id="dvOpenStatusUn" class="red" @if($base->open_status == "1")style="display: none"@endif>关闭中</div>
            </div>

            <div class="inline fields">
                <label style="width :119px;text-align: right;">是否开启推送跟单</label>
                <div class="ui toggle checkbox">
                    <input type="checkbox" id="bet_push_status" name="bet_push_status" @if($base->bet_push_status == "1") checked="checked" @endif>
                    <label></label>
                </div>
                <div id="bet_push_statusOn" class="green" @if($base->bet_push_status != "1")style="display: none"@endif>开启中</div>
                <div id="bet_push_statusUn" class="red" @if($base->bet_push_status == "1")style="display: none"@endif>关闭中</div>
            </div>

            <div class="inline fields">
                <div class="six wide field">
                    <label style="width :150px;text-align: right;" class="notEmpty">计划发布方式</label>
                    <select class="ui dropdown" name="planSendMode" id="planSendMode">
                        <option value="1" @if($base->plan_send_mode == "1") selected="selected" @endif>软件发布</option>
                        <option value="0" @if($base->plan_send_mode != "1") selected="selected" @endif>手动发布</option>
                    </select>
                </div>
                <div class="two wide field">
                    <a class="ui teal button" id="dspPlan" style="display: none">点击显示输入框</a>
                </div>
            </div>
            <div class="inline fields">
                <label style="width :119px;text-align: right;">计划推送游戏</label>
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" name="planSendGamePK10" @if($PK10 == "1") checked="checked" @endif>
                    <label>北京赛车&nbsp;</label>
                </div>
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" name="planSendGameCQSSC" @if($CQSSC == "1") checked="checked" @endif>
                    <label>重庆时时彩&nbsp;</label>
                </div>
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" name="planSendGameJSKS" @if($JSKS == "1") checked="checked" @endif>
                    <label>江苏快三&nbsp;</label>
                </div>
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" name="planSendGameXYFT" @if($XYFT == "1") checked="checked" @endif>
                    <label>幸运飞艇&nbsp;</label>
                </div>
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" name="planSendGamePCDD" @if($PCDD == "1") checked="checked" @endif>
                    <label>PC蛋蛋&nbsp;</label>
                </div>
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" name="planSendGameKSSC" @if($KSSC == "1") checked="checked" @endif>
                    <label>快速赛车&nbsp;</label>
                </div>
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" name="planSendGameKSFT" @if($KSFT == "1") checked="checked" @endif>
                    <label>快速飞艇&nbsp;</label>
                </div>
                <div class="ui checkbox"style="margin-right: 5px">
                    <input type="checkbox" name="planSendGameKSSSC" @if($KSSSC == "1") checked="checked" @endif>
                    <label>快速时时彩&nbsp;</label>
                </div>
            </div>
            <div class="inline fields">
                <div class="six wide field">
                    <label style="width :150px;text-align: right;">计划底部信息</label>
                    <div class="ui input icon">
                        <textarea name="planMsg" rows="3">{{ $base->plan_msg }}</textarea>
                    </div>
                </div>
            </div>
            <div class="inline fields">
                <label style="width :119px;text-align: right;" class="notEmpty">发布时段</label>
                <div class="ui calendar" id="starttime">
                    <div class="ui input left icon">
                        <i class="time icon"></i>
                        <input type="text" placeholder="" name="starttime" value="{{ $base->send_starttime }}">
                    </div>
                </div>
                <label style="width :50px;text-align: right;">~次日</label>
                <div class="ui calendar" id="endtime">
                    <div class="ui input left icon">
                        <i class="time icon"></i>
                        <input type="text" placeholder="" name="endtime" value="{{ $base->send_endtime }}">
                    </div>
                </div>
            </div>
            <div class="inline fields">
                <div class="six wide field">
                    <label style="width :150px;text-align: right;" class="notEmpty">是否展开聊天室</label>
                    <select class="ui dropdown" name="isOpenAuto">
                        <option value="1" @if($base->is_open_auto == "1") selected="selected" @endif>自动展开(适合用户量少的平台)</option>
                        <option value="0" @if($base->is_open_auto != "1") selected="selected" @endif>不自动展开</option>
                    </select>
                </div>
            </div>
            <div class="inline fields">
                <div class="six wide field">
                    <label style="width :150px;text-align: right;" class="notEmpty">下注最低推送额</label>
                    <input type="text" placeholder="" name="betMin" value="{{ $base->bet_min_amount }}">
                </div>
            </div>
            <div class="inline fields">
                <div class="six wide field">
                    <label style="width :150px;text-align: right;">IP黑名单</label>
                    <input type="text" placeholder="" name="ipBlacklist" value="{{ $base->ip_blacklist }}">
                </div>
            </div>
            <div class="inline fields">
                <div class="three wide field">
                    <label style="width :114px;"></label>
                    <button class="ui primary button" id="save" type="submit">
                        保存配置
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('page-js')
    <script src="/chat/js/pages/baseManage.js"></script>
@endsection