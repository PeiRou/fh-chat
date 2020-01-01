@extends('chat.master')

@section('title','计划设定-管理后台')

@section('content')
    <div class="content-top">
        <div class="breadcrumb">
            <b>位置：</b>计划设定
            <button style="line-height: 20px;border:0;margin-left: 10px;cursor:pointer;" onclick="javascript:history.go(-1)">返回</button>
        </div>
        <div class="content-top-buttons">
            <span onclick="setMoney()">批量修改金额</span>
            <span onclick="add('添加计划','/chat/planTask/add')">添加计划</span>
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
                                {{--@if(!($iGame->category == 'lhc'))--}}
                                 <option value="{{ $iGame->game_id }}">{{ $iGame->game_name }}</option>
                                {{--@endif--}}
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
        $('#menu-Open').addClass('active');

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
            {data:'lotteryType',title:'彩种类型'},
            {data:'game_id',title:'游戏ID'},
            {data:'game_name',title:'游戏名称'},
            {data:'type',title:'计划玩法类型'},
            {data:'play_name',title:'计划名称显示'},
            {data:'num_digits',title:'选取的号码位数'},
            {data:'plan_num',title:'计划个数'},
            // {data:'money',title:'跟投金额'},
            {
                data:'money',
                title:'跟投金额'+' <input type="text" style="width:60px;height:25px;" id="dataId">'+'<br/>'+'<span style="color: red">(跟投金额为0时不显示跟投)</span>',
            },
            {data:'created_at',title:'新增时间'},
            {data:'updated_at',title:'修改时间'},
            {data:'control',title:'操作'},
        ];

        var dataTable;

        function createTable(columns) {
            var groupColumn = 0;
            return $('#tableData').DataTable({
                searching: false,
                bLengthChange: false,
                processing: true,
                serverSide: true,
                ordering: false,
                destroy: true,
                aLengthMenu: [[100]],
                columnDefs: [
                    { "visible": false, "targets": groupColumn }
                ],
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
                    "info": "当前显示第 _START_ 到 _END_ 笔数，总 _TOTAL_ 笔数，当前显示第 _PAGE_ 页，共 _PAGES_ 页",
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
                "drawCallback": function ( settings ) {
                    var api = this.api();
                    var rows = api.rows( {page:'current'} ).nodes();
                    var last=null;

                    api.column(groupColumn, {page:'current'} ).data().each( function ( group, i ) {
                        if ( last !== group ) {
                            $(rows).eq( i ).before(
                                '<tr class="selectable positive"><td colspan="9">'+group+'</td></tr>'
                            );

                            last = group;
                        }
                    } );
                }
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
                content: '删除后将无法恢复，请谨慎操作！',
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

        function setStatus(dataId,status) {
            var data = {
                dataId:dataId,
                status:status,
            };
            var lading = layer.load(1, {
                shade: [0.1,'#fff'] //0.1透明度的白色背景
            });
            $.ajax({
                url:'/chat/planTask/setStatus',
                type:'post',
                data:data,
                dataType:'json',
                timeout:'5000',
                success:function(e){
                    layer.close(lading);
                    console.log(e);
                    if(e.status == true){
                        $('#tableData').DataTable().ajax.reload()
                    }else{
                        layer.msg(e.msg);
                    }

                },
                error:function(e){
                    layer.close(lading);
                },
            })
        }
        //批量修改金额
        function setMoney() {
            var allMoney = $('.allMoney');
            var ids = [];
            var moneys = [];
            allMoney.each(function(index,element){
               ids.push($(element).attr('data-id'));
               moneys.push($(element).val());
            });
            var data = {
                ids:ids,
                moneys:moneys
            };
            jc = $.confirm({
                theme: 'material',
                title: '批量修改金额',
                closeIcon:true,
                boxWidth:'20%',
                url: '/chat/planTask/setMoney',
                content: '确定批量修改金额？',
                buttons: {
                    confirm: {
                        text:'确定',
                        btnClass: 'btn-red',
                        action: function(){
                            $.ajax({
                                url:'/chat/planTask/setMoney',
                                type:'post',
                                data:data,
                                dataType:'json',
                                success:function (data) {
                                    if(data.status == true){
                                        Calert("操作成功",'green','操作提示');
                                    } else {
                                        Calert(data.msg,'red');
                                    }
                                },
                                error:function (e) {
                                    if(e.status == 403)
                                    {
                                        Calert('您没有此项权限！无法继续！','red')
                                    }
                                }
                            });
                        }
                    },
                    cancel:{
                        text:'取消'
                    }
                }
            });
        }
        $("#tableData").on('keyup', '#dataId', function (e) {
            var money = $('#dataId').val();
            var data = {
                moneys:money
            };
            $.ajax({
                url: '/chat/planTask/setMoney',
                type: 'POST',
                data: data,
                success: function(data) {
                    if(data.status == true){
                        $('#tableData').DataTable().ajax.reload()
                    } else {
                        Calert(data.msg,'red');
                    }
                }
            });
        });
    </script>
@endsection