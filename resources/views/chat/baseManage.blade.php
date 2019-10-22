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
                    <label style="width :150px;text-align: right;">引导群欢迎语</label>
                    <div class="ui input icon">
                        <textarea name="guan_msg" rows="3">{{ $base->guan_msg }}</textarea>
                    </div>
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
            <div  class="inline fields" style="color:#ff0000;padding-left :132px;">发布时段不设置为不发计划，如果想24小时请设置0:00 - 23:59</div>
            <div class="inline fields" >
                <label style="width :119px;text-align: right;" class="notEmpty">发布时段</label>
                <div id="SendConfig">
                    @foreach($chat_send_config as $vavue)
                        <div style="display: flex">
                            <div class="ui calendar starttime">
                                <div class="ui input left icon">
                                    <i class="time icon"></i>
                                    <input type="text" placeholder="" name="starttime[]" value="{{ $vavue->send_starttime }}">
                                </div>
                            </div>
                            <label style="font-weight: 700; text-align: right; display: flex; align-items: center"> - </label>
                            <div class="ui calendar endtime">
                                <div class="ui input left icon">
                                    <i class="time icon"></i>
                                    <input type="text" placeholder="" name="endtime[]" value="{{ $vavue->send_endtime }}">
                                </div>
                            </div>
                            <button class="ui button" id=""  type="button" onclick="delSendConfig(this)">
                                删除
                            </button>
                        </div>
                    @endforeach
                </div>
                <script type="text/html" id="SendConfigText">
                    <div style="display: flex">
                        <div class="ui calendar starttime" >
                            <div class="ui input left icon">
                                <i class="time icon"></i>
                                <input type="text" placeholder="" name="starttime[]" value="">
                            </div>
                        </div>
                        <label style="font-weight: 700; text-align: right; display: flex; align-items: center"> - </label>
                        <div class="ui calendar endtime">
                            <div class="ui input left icon">
                                <i class="time icon"></i>
                                <input type="text" placeholder="" name="endtime[]" value="">
                            </div>
                        </div>
                        <button class="ui button" type="button" onclick="delSendConfig(this)">
                            删除
                        </button>
                    </div>
                </script>
                <button class="ui primary button" style="" id="addSendConfig" type="button">
                    添加
                </button>
            </div>
            {{--<div class="inline fields">--}}
                {{--<div class="six wide field">--}}
                    {{--<label style="width :150px;text-align: right;" class="notEmpty">是否展开聊天室</label>--}}
                    {{--<select class="ui dropdown" name="isOpenAuto">--}}
                        {{--<option value="1" @if($base->is_open_auto == "1") selected="selected" @endif>自动展开(适合用户量少的平台)</option>--}}
                        {{--<option value="0" @if($base->is_open_auto != "1") selected="selected" @endif>不自动展开</option>--}}
                    {{--</select>--}}
                {{--</div>--}}
            {{--</div>--}}
            <div class="inline fields">
                <div class="six wide field">
                    <label style="width :150px;text-align: right;" class="notEmpty">下注最低推送额</label>
                    <input type="text" placeholder="" name="betMin" value="{{ $base->bet_min_amount }}">
                </div>
            </div>
            {{--<div class="inline fields">--}}
                {{--<div class="six wide field">--}}
                    {{--<label style="width :150px;text-align: right;">IP黑名单</label>--}}
                    {{--<input type="text" placeholder="" name="ipBlacklist" value="{{ $base->ip_blacklist }}">--}}
                {{--</div>--}}
            {{--</div>--}}
            @if($ISROOMS)
            <div class="inline fields">
                <div class="six wide field">
                    <label style="width :150px;text-align: right;" class="notEmpty">会员建群</label>
                    <select class="ui dropdown" name="is_build_room">
                        <option value="1" @if($base->is_build_room == "1") selected="selected" @endif>开</option>
                        <option value="0" @if($base->is_build_room != "1") selected="selected" @endif>关</option>
                    </select>
                </div>
            </div>
            <div class="inline fields">
                <div class="six wide field">
                    <label style="width :150px;text-align: right;" class="notEmpty">非管理员搜索好友/加好友</label>
                    <select class="ui dropdown" name="is_add_friends">
                        <option value="1" @if($base->is_add_friends == "1") selected="selected" @endif>开</option>
                        <option value="0" @if($base->is_add_friends != "1") selected="selected" @endif>关</option>
                    </select>
                </div>
            </div>
            @endif
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