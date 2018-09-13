/**
 * Created by zoe on 2018/5/22.
 */
$(function () {
    $('#menu-hongbaoManage').addClass('active');

    var today = new Date();
    $('#rangestart').calendar({
        type: 'date',
        endCalendar: $('#rangeend'),
        formatter: {
            date: function (date, settings) {
                if (!date) return '';
                var day = date.getDate();
                var month = date.getMonth() + 1;
                var year = date.getFullYear();
                return year+'-'+month+'-'+day;
            }
        },
        text: {
            days: ['日', '一', '二', '三', '四', '五', '六'],
            months: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            monthsShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            today: '今天',
            now: '现在',
            am: 'AM',
            pm: 'PM'
        },
        minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate() - 99),
        maxDate: new Date(today.getFullYear(), today.getMonth(), today.getDate())
    });
    $('#rangeend').calendar({
        type: 'date',
        startCalendar: $('#rangestart'),
        formatter: {
            date: function (date, settings) {
                if (!date) return '';
                var day = date.getDate();
                var month = date.getMonth() + 1;
                var year = date.getFullYear();
                return year+'-'+month+'-'+day;
            }
        },
        text: {
            days: ['日', '一', '二', '三', '四', '五', '六'],
            months: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            monthsShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            today: '今天',
            now: '现在',
            am: 'AM',
            pm: 'PM'
        },
        minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate() - 99),
        maxDate: new Date(today.getFullYear(), today.getMonth(), today.getDate())
    });

    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/hongbao',
            data:function (d) {
                d.timeStart = $('#timeStart').val();
                d.timeEnd = $('#timeEnd').val();
                d.id = $('#id').val();              //红包id
                d.status = $('#status').val();      //状态
            }
        },
        columns: [
            {data:'chat_hongbao_idx'},              //红包ID
            {data:'room_name'},                     //房间
            {data:function (data) {                 //状态
                    switch (parseInt(data.hongbao_status)){
                        case 1:
                            var txt = "抢疯中";
                            clsName = 1;      //绿色
                            break;
                        case 2:
                            var txt = "已抢完";
                            clsName = 3;      //红色
                            break;
                        case 3:
                            var txt = "已关闭";
                            clsName = 3;      //红色
                            break;
                    }
                    return '<span class="status-'+clsName+'">'+txt+'</span>';
                }},
            {data:'hongbao_total_amount'},         //总金额
            {data:function (data) {                    //红包个数 hongbao_total_num
                    return data.hongbao_remain_num+'/'+data.hongbao_total_num;
                }},
            {data:'hongbao_remain_amount'},        //剩馀馀额
            {data:function(data){                  //抢红包条件(最近2天)  recharge  bet
                return "充值量不少于"+data.recharge+";打码量不少于"+data.bet;
                }},
            {data:'posttime'},                      //发送时间
            {data:'account'},                       //操作人
            {data:function(data){                       //操作
                    if(data.hongbao_status==2)          //----重发
                        delEnabel = "class='disabled'";
                    else
                        delEnabel = "onclick='reHongbao("+data.room_id+","+data.chat_hongbao_idx+")'";
                    litxt = "<li "+delEnabel+" >重发</li>";

                    if(data.hongbao_status==1)          //----关闭
                        litxt = litxt + "<li onclick='closeHongbao("+data.chat_hongbao_idx+")'>关闭</li>";
                    else if(data.hongbao_status==2)
                        litxt = litxt + "<li "+delEnabel+">关闭</li>";
                    else if(data.hongbao_status==3)
                        litxt = litxt + "<li onclick='openHongbao("+data.chat_hongbao_idx+")'><span class='status-1'>开启</span></li>";

                    litxt = litxt + "<a href='/chat/hongbaoDt?id="+data.chat_hongbao_idx+"&start="+$('#timeStart').val()+"&end="+$('#timeEnd').val()+"'><li>查看明细</li></a>";
                    return "<ul class='control-menu'>" + litxt + "</ul>";
                }}
        ],
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
        }
    });
    $('#btn_search').on('click',function () {
        dataTable.ajax.reload();
    });
    $('#reset').on('click',function () {
        $('#id').val("");
        $('#status').val("");
        dataTable.ajax.reload();
    });
});

//发红包
function addHongbao() {
    jc = $.confirm({
        theme: 'material',
        title: '发红包',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/addHongbao',
        buttons: {
            confirm: {
                text: '提交',
                btnClass: 'btn-blue',
                action: function () {
                    var form = this.$content.find('#updUserForm').data('formValidation').validate().isValid();
                    if(!form){
                        return false;
                    }
                    return false;
                }
            },
            cancel: {
                text:'关闭'
            }
        },
        contentLoaded: function(data, status, xhr){
            $('.jconfirm-content').css('overflow','hidden');
            if(xhr == 'Forbidden')
            {
                this.setContent('<div class="modal-error"><span class="error403">403</span><br><span>您无权进行此操作</span></div>');
                $('.jconfirm-buttons').hide();
            }
        }
    });
}

//重发红包
function reHongbao(room,id) {
    jc = $.confirm({
        title: '提示',
        theme: 'material',
        type: 'red',
        boxWidth:'25%',
        content: '确定要重发这个红包吗?',
        buttons: {
            confirm: {
                text:'确定重发',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/reHongbao/'+room+'&'+id,
                        type:'post',
                        dataType:'json',
                        success:function (data) {
                            if(data.status == true){
                                $.ajax({
                                    url: '/dows',
                                    type: 'POST',
                                    data: {
                                        type :result.type,
                                        room :result.data,
                                    },
                                    success: function(result) {

                                    }
                                });
                                $('#dtTable').DataTable().ajax.reload(null,false)
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
//关闭红包
function closeHongbao(id) {
    jc = $.confirm({
        title: '提示',
        theme: 'material',
        type: 'red',
        boxWidth:'25%',
        content: '确定要关闭这个红包吗?',
        buttons: {
            confirm: {
                text:'确定关闭',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/closeHongbao/'+id,
                        type:'post',
                        dataType:'json',
                        success:function (data) {
                            if(data.status == true){
                                $('#dtTable').DataTable().ajax.reload(null,false)
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

//开启红包
function openHongbao(id) {
    jc = $.confirm({
        title: '提示',
        theme: 'material',
        type: 'red',
        boxWidth:'25%',
        content: '确定要开启这个红包吗?',
        buttons: {
            confirm: {
                text:'确定开启',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/openHongbao/'+id,
                        type:'post',
                        dataType:'json',
                        success:function (data) {
                            if(data.status == true){
                                $('#dtTable').DataTable().ajax.reload(null,false)
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