/**
 * Created by zoe on 2018/5/22.
 */
$(function () {
    $('#menu-roomManage').addClass('active');

    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/room',
            data:{}
        },
        columns: [
            {data:'room_name'},              //房间名称
            {data:function(data){            //房间类型
                    var repTxt = "";
                    switch(parseInt(data.roomtype)){
                        case 1:
                            repTxt = "平台聊天室";
                            break;
                    }
                return '<font color="blue">'+repTxt+'</font>';
                }},
            {data:function(){                //在线人数
                return 0
                }},
            {data:function(data){              //是否禁言 is_speaking
                if(data.is_speaking=="1") {
                    txt = '正常';
                    fontcolor = '&#xe652;';
                    clsName = 1;      //绿色
                }else {
                    txt = '禁言';
                    fontcolor = '&#xe672;';
                    clsName = 3;      //红色
                }
                return '<span class="status-'+clsName+'"><i class="iconfont">'+fontcolor+'</i> '+txt+'</span>';
                }},
            {data:function(data){              //发言条件
                return '充值量不少于'+data.recharge +';打码量不少于'+data.bet;
                }},
            {data:function () {              //创建时间
                return "";
                }},
            {data: function (data) {
                    if(parseInt(data.is_speaking)==1){
                        exe = 'un';
                        txt = '禁言';
                    }else{
                        exe = 'on';
                        txt = '恢复';
                    }
                    if(parseInt(data.isTestSpeak)==1){
                        testExe = 'un';
                        testTxt = '关闭测试帐号自动发言';
                    }else{
                        testExe = 'on';
                        testTxt = '开放测试帐号自动发言';
                    }
                    return "<ul class='control-menu'>" +
                        "<li onclick='updRoomInfo("+data.room_id+",\""+data.room_name+"\","+data.recharge+","+data.bet+")'>修改</li>" +
                        "<li onclick='unSpeakRoom("+data.room_id+",\""+exe+"\")'>"+txt+"</li>" +
                        "<li onclick='openTestAccount("+data.room_id+",\""+testExe+"\")'>"+testTxt+"</li>" +
                        "</ul>";
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
});

//修改房间发言条件
function updRoomInfo(id,name,rech,bet) {
    jc = $.confirm({
        theme: 'material',
        title: '修改房间信息',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editRoomLimit/'+id+'&'+name+'&'+rech+'&'+bet,

        buttons: {
            confirm: {
                text: '确定提交',
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

//执行禁言
function unSpeakRoom(id,status) {
    if(status=="un"){
        txt = '禁言';
    }else{
        txt = '恢复发言';
    }
    jc = $.confirm({
        title: '提示',
        theme: 'material',
        type: 'red',
        boxWidth:'25%',
        content: '确定要对房间进行'+txt+'吗？',
        buttons: {
            confirm: {
                text:'确定',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/unSpeakRoom/'+id+'&'+status,
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

//执行禁言
function openTestAccount(id,status) {
    if(status=="on"){
        txt = '开放';
    }else{
        txt = '关闭';
    }
    jc = $.confirm({
        title: '提示',
        theme: 'material',
        type: 'red',
        boxWidth:'25%',
        content: '确定要对房间'+txt+'测试帐号聊天吗？',
        buttons: {
            confirm: {
                text:'确定',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/onTestSpeakRoom/'+id+'&'+status,
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