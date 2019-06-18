@extends('chat.master')

@section('title','计划设定-管理后台')

@section('content')
    <div class="content-top">
        <div class="breadcrumb">
            <b>位置：</b>计划设定
            <button style="line-height: 20px;border:0;margin-left: 10px;cursor:pointer;" onclick="javascript:history.go(-1)">返回</button>
        </div>
        <div class="content-top-buttons">
            <span onclick="add('添加计划','/SelfOpen/PlanTask/add')">添加计划</span>
        </div>
    </div>
    <div class="table-content">
        <div class="table-quick-bar">
            <div class="ui mini form">
                <div class="fields">
                    <div class="one wide field" style="width: initial!important">
                        <select class="ui dropdown" id="gameId" style='height:32px !important;'>
                            <option value="">选择游戏</option>
                            @foreach($aData['game'] as $iGame)
                                <option value="{{ $iGame->game_id }}">{{ $iGame->game_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="one wide field" style="width:5%!important;">
                        <button id="btn_search" class="fluid ui mini  icon teal button" ><i class="search icon"></i> 查询 </button>
                    </div>
                    <input type="hidden" id="isSearch" value="">
                </div>
            </div>
        </div>
        <table id="tableData" class="ui small table" cellspacing="0" width="100%">
            <thead>
            <tr>
            </tr>
            </thead>
        </table>
    </div>
@endsection

@section('page-js')
    <link rel="stylesheet" href="/vendor/layui/css/layui.css">
    <script src="/vendor/layui/layui.all.js"></script>
    <script>
        $('#menu-Open').addClass('nav-show');
        $('#menu-SelfOpen-planTask-index').addClass('active');

        var layer,laydate;
        layui.use('layer', function(){
            layer = layui.layer;
        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        var columns = [
            {data:'game_id',title:'游戏ID'},
            {data:'game_name',title:'游戏名称'},
            {data:'play_name',title:'游戏玩法'},
            {data:'num_digits',title:'选取的号码位数'},
            {data:'planned_probability',title:'计划中奖概率'},
            {data:'Winning_count',title:'中奖次数'},
            {data:'total_count',title:'开奖总次数'},
            {data:'fact_probability',title:'实际中奖概率'},
            {data:'count_date',title:'开奖更新时间'},
            {data:'created_at',title:'新增时间'},
            {data:'updated_at',title:'修改时间'},
            {data:'control',title:'操作'},
        ];

        var dataTable;

        function createTable(columns) {
            return $('#tableData').DataTable({
                searching: false,
                bLengthChange: false,
                processing: true,
                serverSide: true,
                ordering: false,
                destroy: true,
                aLengthMenu: [[100]],
                ajax: {
                    url:'{{ route('chat.planTask.index') }}',
                    type:'post',
                    data:function (d) {
                        d.game_id = $('#gameId').val();
                    }
                },
                columns: columns,
                language: {
                    "zeroRecords": "暂无数据",
                    "info": "当前显示第 _PAGE_ 页，共 _PAGES_ 页",
                    "infoEmpty": "没有记录",
                    "loadingRecords": "请稍后...",
                    "processing":     "读取中...",
                    "paginate": {
                        "first":      "首页",
                        "last":       "尾页",
                        "next":       "下一页",
                        "previous":   "上一页"
                    }
                },
            });

        }

        $(function () {

            $('#btn_search').on('click',function () {
                dataTable.ajax.reload();
            });

            dataTable = createTable(columns);


        });

        function add(title,url) {
            jc = $.confirm({
                theme: 'material',
                title: title,
                closeIcon:true,
                boxWidth:'20%',
                content: 'url:'+url,
                buttons: {
                    formSubmit: {
                        text:'确定提交',
                        btnClass: 'btn-blue',
                        action: function () {
                            var form = this.$content.find('#dataForm').data('formValidation').validate().isValid();
                            if(!form){
                                return false;
                            }
                            return false;
                        }
                    }
                },
                contentLoaded: function(data, status, xhr){
                    if(data.status == 403)
                    {
                        this.setContent('<div class="modal-error"><span class="error403">403</span><br><span>您无权进行此操作</span></div>');
                        $('.jconfirm-buttons').hide();
                    }
                }
            });
        }

        function edit(title,url) {
            jc = $.confirm({
                theme: 'material',
                title: title,
                closeIcon:true,
                boxWidth:'20%',
                content: 'url:'+url,
                buttons: {
                    formSubmit: {
                        text:'确定提交',
                        btnClass: 'btn-blue',
                        action: function () {
                            var form = this.$content.find('#dataForm').data('formValidation').validate().isValid();
                            if(!form){
                                return false;
                            }
                            return false;
                        }
                    }
                },
                contentLoaded: function(data, status, xhr){
                    if(data.status == 403)
                    {
                        this.setContent('<div class="modal-error"><span class="error403">403</span><br><span>您无权进行此操作</span></div>');
                        $('.jconfirm-buttons').hide();
                    }
                }
            });
        }

        function del(title,url) {
            jc= $.confirm({
                title: '确定删除？',
                theme: 'material',
                type: 'red',
                boxWidth:'20%',
                content: '删除后账号将无法登录，且无法恢复，请谨慎操作！',
                buttons: {
                    confirm: {
                        text:'确定提交',
                        btnClass: 'btn-red',
                        action: function(){
                            $.ajax({
                                url:url,
                                type:'post',
                                dataType:'json',
                                success:function (data) {
                                    if(data.status == true)
                                    {
                                        jc.close();
                                        $('#tableData').DataTable().ajax.reload(null,false);
                                    } else {
                                        Calert(data.msg,'red');
                                    }
                                }
                            });
                            return false;
                        }
                    },
                    cancel:{
                        text:'取消'
                    }
                }
            });
        }

    </script>
@endsection